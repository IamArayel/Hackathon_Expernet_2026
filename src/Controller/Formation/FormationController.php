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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formations', name: 'formation_')]
class FormationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
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
