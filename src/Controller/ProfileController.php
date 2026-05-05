<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Calcul du pourcentage de progression vers le prochain niveau (exemple simple)
        // Disons que chaque niveau demande 1000 XP
        $xpNextLevel = $user->getLevel() * 1000;
        $xpPercentage = min(($user->getXp() % 1000) / 10, 100);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'xp_percentage' => $xpPercentage,
            'xp_next_level' => $xpNextLevel,
        ]);
    }
}