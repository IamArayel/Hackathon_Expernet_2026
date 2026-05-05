<?php

namespace App\Controller\Admin;

use App\Form\Admin\AiSettingsType;
use App\Repository\SettingRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ai', name: 'admin_ai_')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Administration')]
class AdminAiController extends AbstractController
{
    #[Route('', name: 'settings', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Configuration de l\'Assistant IA',
        description: 'Affiche les paramètres actuels de l\'IA (clé API masquée, modèle NVIDIA NIM, prompt système).',
        responses: [new OA\Response(response: 200, description: 'Formulaire de configuration (HTML)')]
    )]
    #[OA\Post(
        summary: 'Sauvegarder la configuration IA',
        description: 'Persiste en base les paramètres de l\'IA. Un champ vide revient à la valeur par défaut (variable d\'environnement ou constante). Priorité : base de données > variable d\'environnement > valeur codée en dur.',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'api_key', type: 'string', nullable: true, description: 'Clé API NVIDIA NIM. Vide = utilise NVIDIA_API_KEY'),
            new OA\Property(property: 'model', type: 'string', enum: ['gemma-2-2b-it', 'google/gemma-2-9b-it', 'meta/llama-3.1-8b-instruct']),
            new OA\Property(property: 'system_prompt', type: 'string', nullable: true, description: 'Prompt système personnalisé. Vide = prompt pédagogique par défaut'),
        ])),
        responses: [new OA\Response(response: 302, description: 'Redirection vers le formulaire après sauvegarde')]
    )]
    public function settings(Request $request, SettingRepository $settingRepo): Response
    {
        $data = [
            'api_key' => $settingRepo->getValue('ai_api_key'),
            'model' => $settingRepo->getValue('ai_model', 'gemma-2-2b-it'),
            'system_prompt' => $settingRepo->getValue('ai_system_prompt'),
        ];

        $form = $this->createForm(AiSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saved = $form->getData();
            $settingRepo->setValue('ai_api_key', $saved['api_key'] ?: null);
            $settingRepo->setValue('ai_model', $saved['model'] ?: 'gemma-2-2b-it');
            $settingRepo->setValue('ai_system_prompt', $saved['system_prompt'] ?: null);
            $this->addFlash('success', 'Configuration IA sauvegardée.');
            return $this->redirectToRoute('admin_ai_settings');
        }

        return $this->render('admin/ai/settings.html.twig', ['form' => $form]);
    }
}
