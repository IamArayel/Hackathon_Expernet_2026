<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/leaderboard', name: 'admin_leaderboard_')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Administration')]
class AdminLeaderboardController extends AbstractController
{
    #[Route('', name: 'index')]
    #[OA\Get(
        summary: 'Classement complet (admin)',
        description: 'Affiche les 50 premiers utilisateurs triés par XP, avec actions de réinitialisation individuelles.',
        responses: [new OA\Response(response: 200, description: 'Tableau de classement (HTML)')]
    )]
    public function index(UserRepository $userRepo): Response
    {
        return $this->render('admin/leaderboard/index.html.twig', [
            'users' => $userRepo->findLeaderboard(50),
        ]);
    }

    #[Route('/reset-all', name: 'reset_all', methods: ['POST'])]
    #[OA\Post(
        summary: 'Réinitialiser tout le classement',
        description: 'Remet à zéro les XP, le niveau (→ 1) et la série de tous les utilisateurs. Action irréversible, protégée par token CSRF.',
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers le classement admin'),
            new OA\Response(response: 403, description: 'Token CSRF invalide'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Réinitialiser les XP d\'un utilisateur',
        description: 'Remet à zéro les XP, le niveau et la série d\'un utilisateur précis. Protégé par token CSRF.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers le classement admin'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable'),
        ]
    )]
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
