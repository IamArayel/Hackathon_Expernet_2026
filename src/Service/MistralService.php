<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\SettingRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MistralService
{
    private const API_URL = 'https://api.mistral.ai/v1/chat/completions';
    private const DEFAULT_MODEL = 'mistral-small-latest';
    private const DEFAULT_SYSTEM_PROMPT = <<<PROMPT
        Tu es un assistant pédagogique pour la plateforme Academ'Île. Tu aides les apprenants
        à comprendre les concepts de leurs formations, tu poses des questions pour évaluer leur
        compréhension et tu adaptes tes explications à leur niveau.
        Réponds toujours en français, de façon concise et encourageante.
        PROMPT;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingRepository $settingRepository,
        private readonly string $fallbackApiKey = '',
    ) {}

    public function chat(string $userMessage, ?User $user = null): string
    {
        $apiKey = $this->settingRepository->getValue('mistral_api_key') ?: $this->fallbackApiKey;
        $model = $this->settingRepository->getValue('mistral_model') ?: self::DEFAULT_MODEL;
        $systemPrompt = $this->settingRepository->getValue('mistral_system_prompt') ?: self::DEFAULT_SYSTEM_PROMPT;

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
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
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
