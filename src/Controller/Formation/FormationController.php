<?php

namespace App\Controller\Formation;

use App\Entity\Formation;
use App\Entity\Module;
use App\Entity\UserProgress;
use App\Repository\FormationRepository;
use App\Repository\ModuleRepository;
use App\Repository\UserProgressRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formations', name: 'formation_')]
#[OA\Tag(name: 'Formations')]
class FormationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lister les formations',
        description: 'Retourne la liste des formations, filtrables par difficulté ou catégorie.',
        parameters: [
            new OA\Parameter(name: 'difficulty', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['beginner', 'intermediate', 'advanced'])),
            new OA\Parameter(name: 'category', in: 'query', required: false,
                schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liste des formations (HTML)')]
    )]
    public function index(FormationRepository $repository, Request $request): Response
    {
        $difficulty = $request->query->get('difficulty');
        $category = $request->query->get('category');

        $formations = match(true) {
            $difficulty !== null => $repository->findByDifficulty($difficulty),
            $category !== null => $repository->findByCategory($category),
            default => $repository->findAll(),
        };

        return $this->render('formation/index.html.twig', [
            'formations' => $formations,
            'difficulty' => $difficulty,
            'category' => $category,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Détail d\'une formation',
        description: 'Affiche la formation et la liste de ses modules avec le statut de progression.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Page de détail de la formation (HTML)'),
            new OA\Response(response: 404, description: 'Formation introuvable'),
        ]
    )]
    public function show(Formation $formation, UserProgressRepository $progressRepository): Response
    {
        $user = $this->getUser();
        $progress = $user ? $progressRepository->findByUser($user) : [];
        $completedModuleIds = array_map(
            fn(UserProgress $p) => $p->getModule()->getId(),
            array_filter($progress, fn(UserProgress $p) => $p->isCompleted())
        );

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
            'completedModuleIds' => $completedModuleIds,
        ]);
    }

    #[Route('/{id}/start', name: 'start', methods: ['POST'])]
    #[OA\Post(
        summary: 'Démarrer une formation',
        description: 'Crée un enregistrement de progression pour le premier module et redirige vers celui-ci.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers le premier module'),
            new OA\Response(response: 404, description: 'Formation introuvable'),
        ]
    )]
    public function start(
        Formation $formation,
        EntityManagerInterface $em,
        UserProgressRepository $progressRepository,
        GamificationService $gamification,
    ): Response {
        $user = $this->getUser();
        $firstModule = $formation->getModules()->first();

        if (!$firstModule) {
            $this->addFlash('error', 'Cette formation ne contient pas encore de modules.');
            return $this->redirectToRoute('formation_show', ['id' => $formation->getId()]);
        }

        $existing = $progressRepository->findOneBy(['user' => $user, 'module' => $firstModule]);
        if (!$existing) {
            $progress = (new UserProgress())->setUser($user)->setModule($firstModule);
            $em->persist($progress);
            $em->flush();
        }

        return $this->redirectToRoute('formation_module_show', [
            'id'       => $formation->getId(),
            'moduleId' => $firstModule->getId(),
        ]);
    }

    #[Route('/{id}/module/{moduleId}', name: 'module_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        summary: 'Afficher un module',
        description: 'Affiche le contenu du module et son quiz. Nécessite d\'être authentifié.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Page du module avec contenu et questions (HTML)'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Module introuvable'),
        ]
    )]
    public function moduleShow(
        Formation $formation,
        int $moduleId,
        ModuleRepository $moduleRepository,
        UserProgressRepository $progressRepository,
    ): Response {
        $module = $moduleRepository->find($moduleId);
        if (!$module || $module->getFormation() !== $formation) {
            throw $this->createNotFoundException('Module introuvable.');
        }

        $user = $this->getUser();

        $allModules = $formation->getModules()->toArray();
        usort($allModules, fn(Module $a, Module $b) => $a->getPosition() <=> $b->getPosition());

        $currentIndex = array_search($module, $allModules, true);
        $prevModule   = $currentIndex > 0 ? $allModules[$currentIndex - 1] : null;
        $nextModule   = $currentIndex < count($allModules) - 1 ? $allModules[$currentIndex + 1] : null;

        $progress = $progressRepository->findOneBy(['user' => $user, 'module' => $module]);

        return $this->render('formation/module.html.twig', [
            'formation'   => $formation,
            'module'      => $module,
            'progress'    => $progress,
            'prevModule'  => $prevModule,
            'nextModule'  => $nextModule,
        ]);
    }

    #[Route('/{id}/module/{moduleId}/submit', name: 'module_submit', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        summary: 'Soumettre les réponses d\'un module',
        description: 'Corrige les réponses MCQ, calcule le score, attribue les XP et redirige vers le module suivant.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers le module suivant ou la formation'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function moduleSubmit(
        Formation $formation,
        int $moduleId,
        Request $request,
        ModuleRepository $moduleRepository,
        UserProgressRepository $progressRepository,
        EntityManagerInterface $em,
        GamificationService $gamification,
    ): Response {
        $module = $moduleRepository->find($moduleId);
        if (!$module || $module->getFormation() !== $formation) {
            throw $this->createNotFoundException('Module introuvable.');
        }

        $user = $this->getUser();

        $questions = $module->getQuestions()->toArray();
        $total     = count($questions);
        $correct   = 0;

        foreach ($questions as $question) {
            $submitted = $request->request->get('question_' . $question->getId(), '');
            if ($submitted === $question->getCorrectAnswer()) {
                $correct++;
            }
        }

        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 100;

        $progress = $progressRepository->findOneBy(['user' => $user, 'module' => $module])
            ?? (new UserProgress())->setUser($user)->setModule($module);

        if (!$progress->isCompleted()) {
            $progress->setScore($score)->setCompleted(true);
            $em->persist($progress);
            $xpGained = $gamification->rewardModuleCompletion($user, $module, $score);
            $em->flush();
            $this->addFlash('success', sprintf('Module complété ! Vous avez obtenu %d%% et gagné %d XP.', $score, $xpGained));
        } else {
            $this->addFlash('info', 'Vous avez déjà complété ce module.');
        }

        $allModules = $formation->getModules()->toArray();
        usort($allModules, fn(Module $a, Module $b) => $a->getPosition() <=> $b->getPosition());
        $currentIndex = array_search($module, $allModules, true);
        $nextModule   = $currentIndex < count($allModules) - 1 ? $allModules[$currentIndex + 1] : null;

        if ($nextModule) {
            $nextProgress = $progressRepository->findOneBy(['user' => $user, 'module' => $nextModule]);
            if (!$nextProgress) {
                $np = (new UserProgress())->setUser($user)->setModule($nextModule);
                $em->persist($np);
                $em->flush();
            }
            return $this->redirectToRoute('formation_module_show', [
                'id'       => $formation->getId(),
                'moduleId' => $nextModule->getId(),
            ]);
        }

        $this->addFlash('success', 'Félicitations ! Vous avez terminé la formation.');
        return $this->redirectToRoute('formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/module/{id}/complete', name: 'module_complete', methods: ['POST'])]
    #[OA\Post(
        summary: 'Valider un module (JSON)',
        description: 'Marque le module comme complété et retourne les XP gagnés. Endpoint JSON pour intégrations tierces.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'score', type: 'integer', example: 85,
                        description: 'Score obtenu (0-100)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Progression mise à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'xpGained', type: 'integer', example: 42),
                        new OA\Property(property: 'totalXp', type: 'integer', example: 392),
                        new OA\Property(property: 'level', type: 'integer', example: 2),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function completeModule(
        \App\Entity\Module $module,
        Request $request,
        EntityManagerInterface $em,
        UserProgressRepository $progressRepository,
        GamificationService $gamification,
    ): JsonResponse {
        $user = $this->getUser();
        $score = (int) $request->request->get('score', 0);

        $progress = $progressRepository->findOneBy(['user' => $user, 'module' => $module])
            ?? (new UserProgress())->setUser($user)->setModule($module);

        $progress->setScore($score)->setCompleted(true);
        $em->persist($progress);

        $xpGained = $gamification->rewardModuleCompletion($user, $module, $score);
        $em->flush();

        return $this->json(['xpGained' => $xpGained, 'totalXp' => $user->getXp(), 'level' => $user->getLevel()]);
    }
}
