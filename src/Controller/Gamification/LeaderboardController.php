<?php

namespace App\Controller\Gamification;

use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/leaderboard', name: 'leaderboard_')]
#[OA\Tag(name: 'Gamification')]
class LeaderboardController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Classement général (HTML)',
        description: 'Affiche le top 20 des utilisateurs par XP.',
        responses: [new OA\Response(response: 200, description: 'Page du classement (HTML)')]
    )]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('gamification/leaderboard.html.twig', [
            'topUsers' => $userRepository->findLeaderboard(20),
            'currentUser' => $this->getUser(),
        ]);
    }

    #[Route('/api', name: 'api', methods: ['GET'])]
    #[OA\Get(
        summary: 'Classement général (JSON)',
        description: 'Retourne le top 10 des utilisateurs triés par XP décroissant.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Top 10 des utilisateurs',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'rank', type: 'integer', nullable: true, example: 1),
                            new OA\Property(property: 'username', type: 'string', example: 'Anthony'),
                            new OA\Property(property: 'xp', type: 'integer', example: 350),
                            new OA\Property(property: 'level', type: 'integer', example: 2),
                            new OA\Property(property: 'badges', type: 'integer', example: 3),
                        ]
                    )
                )
            ),
        ]
    )]
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
