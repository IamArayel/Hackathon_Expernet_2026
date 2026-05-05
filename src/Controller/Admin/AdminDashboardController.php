<?php

namespace App\Controller\Admin;

use App\Repository\FormationRepository;
use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Administration')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    #[OA\Get(
        summary: 'Tableau de bord',
        description: 'Affiche les statistiques globales (utilisateurs, formations, modules complétés) et le top 5 du classement. Nécessite ROLE_ADMIN.',
        responses: [
            new OA\Response(response: 200, description: 'Page du tableau de bord (HTML)'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function index(
        UserRepository $userRepo,
        FormationRepository $formationRepo,
        UserProgressRepository $progressRepo,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => $userRepo->count([]),
                'formations' => $formationRepo->count([]),
                'completions' => $progressRepo->count(['completed' => true]),
            ],
            'top_users' => $userRepo->findLeaderboard(5),
        ]);
    }
}
