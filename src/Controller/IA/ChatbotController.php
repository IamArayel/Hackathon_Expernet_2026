<?php

namespace App\Controller\IA;

use App\Service\MistralService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chatbot', name: 'chatbot_')]
class ChatbotController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('ia/chatbot.html.twig');
    }

    #[Route('/ask', name: 'ask', methods: ['POST'])]
    public function ask(Request $request, MistralService $mistral): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return $this->json(['error' => 'Message vide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $reply = $mistral->chat($message, $user);

        return $this->json(['reply' => $reply]);
    }
}
