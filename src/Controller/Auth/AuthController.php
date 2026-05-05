<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Form\Auth\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/auth', name: 'auth_')]
#[OA\Tag(name: 'Authentification')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Formulaire de connexion',
        responses: [new OA\Response(response: 200, description: 'Page de connexion (HTML)')]
    )]
    #[OA\Post(
        summary: 'Connexion utilisateur',
        description: 'Authentifie l\'utilisateur via le formulaire (géré par le firewall Symfony).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'anthonycda@test.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'azerty01'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers l\'accueil après connexion'),
            new OA\Response(response: 401, description: 'Identifiants invalides'),
        ]
    )]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'logout')]
    #[OA\Post(
        summary: 'Déconnexion',
        description: 'Déconnecte l\'utilisateur (géré par le firewall Symfony).',
        responses: [new OA\Response(response: 302, description: 'Redirection vers la page de connexion')]
    )]
    public function logout(): never
    {
        throw new \LogicException('Ce chemin est géré par le firewall Symfony.');
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Formulaire d\'inscription',
        responses: [new OA\Response(response: 200, description: 'Page d\'inscription (HTML)')]
    )]
    #[OA\Post(
        summary: 'Créer un compte',
        description: 'Crée un nouvel utilisateur avec email, nom d\'utilisateur et mot de passe.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'nouveau@example.com'),
                    new OA\Property(property: 'username', type: 'string', example: 'MonPseudo'),
                    new OA\Property(property: 'plainPassword', type: 'string', example: 'motdepasse8'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 302, description: 'Compte créé, redirection vers la connexion'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé ! Bienvenue sur Academ\'Île.');
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/register.html.twig', ['form' => $form]);
    }
}
