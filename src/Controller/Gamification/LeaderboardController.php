<?php

namespace App\Controller\Gamification;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/leaderboard', name: 'leaderboard_')]
class LeaderboardController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('gamification/leaderboard.html.twig', [
            'topUsers' => $userRepository->findLeaderboard(20),
            'currentUser' => $this->getUser(),
        ]);
    }

    #[Route('/api', name: 'api', methods: ['GET'])]
    public function api(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findLeaderboard(10);

        return $this->json(array_map(fn($u) => [
            'rank' => null,
            'username' => $u->getUsername(),
            'xp' => $u->getXp(),
            'level' => $u->getLevel(),
            'badges' => $u->getBadges()->count(),
        ], $users));
    }
}
