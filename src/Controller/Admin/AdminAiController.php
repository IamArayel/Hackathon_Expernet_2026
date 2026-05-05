<?php

namespace App\Controller\Admin;

use App\Form\Admin\AiSettingsType;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ai', name: 'admin_ai_')]
#[IsGranted('ROLE_ADMIN')]
class AdminAiController extends AbstractController
{
    #[Route('', name: 'settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, SettingRepository $settingRepo): Response
    {
        $data = [
            'api_key' => $settingRepo->getValue('mistral_api_key'),
            'model' => $settingRepo->getValue('mistral_model', 'mistral-small-latest'),
            'system_prompt' => $settingRepo->getValue('mistral_system_prompt'),
        ];

        $form = $this->createForm(AiSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $saved = $form->getData();
            $settingRepo->setValue('mistral_api_key', $saved['api_key'] ?: null);
            $settingRepo->setValue('mistral_model', $saved['model'] ?: 'mistral-small-latest');
            $settingRepo->setValue('mistral_system_prompt', $saved['system_prompt'] ?: null);
            $this->addFlash('success', 'Configuration IA sauvegardée.');
            return $this->redirectToRoute('admin_ai_settings');
        }

        return $this->render('admin/ai/settings.html.twig', ['form' => $form]);
    }
}
