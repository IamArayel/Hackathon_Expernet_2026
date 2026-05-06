<?php

namespace App\Controller\Quizz;


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
}