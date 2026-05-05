<?php

namespace App\Controller\Admin;

use App\Repository\FormationRepository;
use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
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
