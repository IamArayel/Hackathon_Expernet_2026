<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserEditType;
use App\Repository\UserRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Administration')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'index')]
    #[OA\Get(
        summary: 'Lister les utilisateurs',
        description: 'Retourne la liste complète des utilisateurs triés par date d\'inscription.',
        responses: [
            new OA\Response(response: 200, description: 'Liste des utilisateurs (HTML)'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function index(UserRepository $userRepo): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Formulaire d\'édition d\'un utilisateur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Formulaire d\'édition (HTML)'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable'),
        ]
    )]
    #[OA\Post(
        summary: 'Mettre à jour un utilisateur',
        description: 'Modifie l\'email, le nom d\'utilisateur, les rôles, les XP et la série. Le niveau est recalculé automatiquement depuis les XP.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste après mise à jour'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
    public function edit(User $user, Request $request, EntityManagerInterface $em, GamificationService $gamification): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gamification->updateLevel($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', ['form' => $form, 'user' => $user]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[OA\Post(
        summary: 'Supprimer un utilisateur',
        description: 'Supprime l\'utilisateur et toute sa progression (cascade). Protégé par token CSRF. L\'admin connecté ne peut pas se supprimer lui-même.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste'),
            new OA\Response(response: 403, description: 'Token CSRF invalide ou tentative d\'auto-suppression'),
        ]
    )]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
