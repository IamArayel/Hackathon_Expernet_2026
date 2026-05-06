<?php

namespace App\Controller\Quizz;

use App\Entity\Formation;
use App\Entity\Module;
use App\Entity\Question;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Repository\UserProgressRepository;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuizzController extends AbstractController
{

    #[Route('/quiz/test', name: 'app_quiz_test')]
    public function test(): Response
    {
        return $this->render('test/question.html.twig');
    }


    #[Route('/quiz/generate/{moduleId}', name: 'app_quiz_generate', methods: ['POST', 'GET'])]
    public function generate(
        int $moduleId,
        EntityManagerInterface $em,
        UserProgressRepository $userProgressRepo,
        AiService $aiService,
        LoggerInterface $logger
    ): Response {
        $logger->info('[QUIZ] Début de génération', ['moduleId' => $moduleId]);

        // 1. Récupération du Module
        $module = $em->getRepository(Module::class)->find($moduleId);
        if (!$module) {
            return $this->json(['error' => 'Module non trouvé'], 404);
        }

        $user = $this->getUser();

        // 2. Calcul de la difficulté basée sur la progression
        $userProgress = $userProgressRepo->findOneBy(['user' => $user, 'module' => $module]);
        $diff = 1;
        if ($userProgress) {
            $score = $userProgress->getScore();
            $diff = ($score > 80) ? 3 : (($score > 40) ? 2 : 1);
        }

        // 3. Préparation du Prompt
        $prompt = sprintf(
            "Thème : %s. Difficulté : %d/3. Génère 5 questions QCM en JSON. " .
                "Format : [{\"question\": \"...\", \"reponses\": [\"A\", \"B\", \"C\"], \"correct\": 0}]. " .
                "Réponds uniquement le JSON, sans texte avant ou après.",
            $module->getTitle(),
            $diff
        );

        // 4. Appel IA et Nettoyage
        try {
            $content = $aiService->chat($prompt, $user);
            $cleanContent = preg_replace('/^```json\s*|\s*```$/m', '', $content);
            $data = json_decode($cleanContent, true);

            if (!$data || !is_array($data)) {
                throw new \Exception("JSON invalide reçu de l'IA");
            }
        } catch (\Exception $e) {
            $logger->error('[QUIZ] Erreur IA: ' . $e->getMessage());
            return $this->json(['error' => 'Erreur lors de la génération des questions'], 500);
        }

        // 5. Enregistrement en Base de Données
        $newQuestions = [];
        foreach ($data as $item) {
            // Vérification sommaire pour éviter les doublons exacts dans le même module
            $exists = $em->getRepository(Question::class)->findOneBy([
                'content' => $item['question'],
                'module' => $module
            ]);

            if (!$exists) {
                $question = new Question();
                $question->setContent($item['question']);
                $question->setOptions($item['reponses']);

                // On définit la réponse correcte à partir de l'index
                $index = $item['correct'] ?? 0;
                $question->setCorrectAnswer($item['reponses'][$index] ?? $item['reponses'][0]);

                $question->setType('mcq');
                $question->setDifficulty($diff);

                // LIAISON CRUCIALE AU MODULE
                $question->setModule($module);

                $em->persist($question);
                $newQuestions[] = $item;
            }
        }

        $em->flush();

        return $this->json([
            'status' => 'success',
            'module' => $module->getTitle(),
            'count' => count($newQuestions),
            'questions' => $data // On renvoie tout le tableau pour l'affichage immédiat
        ]);
    }


    #[Route('/module/generate/{formationId}', name: 'app_module_generate', methods: ['POST', 'GET'])]
    public function generateModule(
        int $formationId,
        Request $request,
        EntityManagerInterface $em,
        AiService $aiService,
        LoggerInterface $logger
    ): Response {
        // 1. Récupération de la Formation parente
        $formation = $em->getRepository(Formation::class)->find($formationId);
        if (!$formation) {
            return $this->json(['error' => 'Formation non trouvée'], 404);
        }

        // 2. Récupération du thème choisi (via bouton ou paramètre)
        $theme = $request->query->get('theme') ?? $request->request->get('theme') ?? 'Informatique générale';
        $user = $this->getUser();

        // 3. Préparation du Prompt pour créer le CONTENU du module
        $prompt = sprintf(
            "Thème : %s. Pour la formation '%s', génère un module de cours complet. " .
                "Format JSON : {\"titre\": \"...\", \"contenu_pedagogique\": \"...\", \"position\": 1}. " .
                "Le contenu pédagogique doit être détaillé et formateur. " .
                "Réponds uniquement le JSON, sans texte superflu.",
            $theme,
            $formation->getTitle()
        );

        try {
            $contentIA = $aiService->chat($prompt, $user);
            $cleanContent = preg_replace('/^```json\s*|\s*```$/m', '', $contentIA);
            $data = json_decode($cleanContent, true);

            if (!$data || !isset($data['titre'])) {
                throw new \Exception("JSON de module invalide");
            }
        } catch (\Exception $e) {
            $logger->error('[MODULE_GEN] Erreur : ' . $e->getMessage());
            return $this->json(['error' => 'Impossible de générer le module'], 500);
        }

        // 4. Création de l'entité Module
        $module = new Module();
        $module->setTitle($data['titre']);
        $module->setContent($data['contenu_pedagogique']);

        // Gestion de la position (soit dictée par l'IA, soit à la suite des autres)
        $currentModulesCount = count($formation->getModules());
        $module->setPosition($data['position'] ?? ($currentModulesCount + 1));

        // LIAISON CRUCIALE À LA FORMATION
        $module->setFormation($formation);

        $em->persist($module);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Module généré avec succès',
            'module' => [
                'id' => $module->getId(),
                'title' => $module->getTitle(),
                'formation' => $formation->getTitle()
            ]
        ]);
    }
}
