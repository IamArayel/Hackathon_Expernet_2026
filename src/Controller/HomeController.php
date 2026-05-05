<?php

namespace App\Controller;

use App\Repository\FormationRepository;
use App\Repository\UserProgressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        FormationRepository $formationRepository,
        UserProgressRepository $progressRepository,
    ): Response {
        $user = $this->getUser();
        $recentFormations = $formationRepository->findBy([], ['createdAt' => 'DESC'], 6);
        $completedCount = $user ? $progressRepository->countCompletedByUser($user) : 0;

        return $this->render('home/index.html.twig', [
            'formations' => $recentFormations,
            'completedCount' => $completedCount,
        ]);
    }
}
