<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MistralService
{
    private const API_URL = 'https://api.mistral.ai/v1/chat/completions';
    private const MODEL = 'mistral-small-latest';
    private const SYSTEM_PROMPT = <<<PROMPT
        Tu es un assistant pédagogique pour la plateforme Academ'Île. Tu aides les apprenants
        à comprendre les concepts de leurs formations, tu poses des questions pour évaluer leur
        compréhension et tu adaptes tes explications à leur niveau.
        Réponds toujours en français, de façon concise et encourageante.
        PROMPT;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
    ) {}

    public function chat(string $userMessage, ?User $user = null): string
    {
        $systemPrompt = self::SYSTEM_PROMPT;
        if ($user) {
            $systemPrompt .= sprintf(
                "\nL'apprenant s'appelle %s, il est au niveau %d avec %d XP.",
                $user->getUsername(),
                $user->getLevel(),
                $user->getXp(),
            );
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => 512,
                    'temperature' => 0.7,
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de réponse.';
        } catch (\Throwable) {
            return 'Le service IA est temporairement indisponible. Veuillez réessayer.';
        }
    }
}
