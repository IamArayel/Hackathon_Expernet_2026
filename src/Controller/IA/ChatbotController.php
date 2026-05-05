<?php

namespace App\Controller\IA;

use App\Service\MistralService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chatbot', name: 'chatbot_')]
#[OA\Tag(name: 'IA')]
class ChatbotController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Interface du chatbot IA',
        description: 'Page HTML de l\'assistant pédagogique alimenté par Mistral AI.',
        responses: [new OA\Response(response: 200, description: 'Page du chatbot (HTML)')]
    )]
    public function index(): Response
    {
        return $this->render('ia/chatbot.html.twig');
    }

    #[Route('/ask', name: 'ask', methods: ['POST'])]
    #[OA\Post(
        summary: 'Envoyer un message au chatbot (JSON)',
        description: 'Envoie un message à l\'assistant IA (Mistral) et retourne sa réponse. L\'IA adapte ses réponses au niveau de l\'utilisateur connecté.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message'],
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Explique-moi ce qu\'est le machine learning.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Réponse de l\'IA',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'reply', type: 'string',
                            example: 'Le machine learning est une branche de l\'IA...'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Message vide',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Message vide.'),
                    ]
                )
            ),
        ]
    )]
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
