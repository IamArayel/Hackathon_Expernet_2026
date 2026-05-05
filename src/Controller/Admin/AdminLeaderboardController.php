<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/leaderboard', name: 'admin_leaderboard_')]
#[IsGranted('ROLE_ADMIN')]
class AdminLeaderboardController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepo): Response
    {
        return $this->render('admin/leaderboard/index.html.twig', [
            'users' => $userRepo->findLeaderboard(50),
        ]);
    }

    #[Route('/reset-all', name: 'reset_all', methods: ['POST'])]
    public function resetAll(Request $request, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('reset_all_xp', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_leaderboard_index');
        }

        foreach ($userRepo->findAll() as $user) {
            $user->setXp(0)->setLevel(1)->setStreak(0);
        }
        $em->flush();
        $this->addFlash('success', 'Classement réinitialisé — tous les XP ont été remis à zéro.');

        return $this->redirectToRoute('admin_leaderboard_index');
    }

    #[Route('/reset/{id}', name: 'reset_user', methods: ['POST'])]
    public function resetUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('reset_user_' . $user->getId(), $request->request->get('_token'))) {
            $user->setXp(0)->setLevel(1)->setStreak(0);
            $em->flush();
            $this->addFlash('success', sprintf('XP de %s réinitialisés.', $user->getUsername()));
        }

        return $this->redirectToRoute('admin_leaderboard_index');
    }
}
