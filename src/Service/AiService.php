<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\SettingRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiService
{
    private const API_URL = 'https://integrate.api.nvidia.com/v1/chat/completions';
    private const DEFAULT_MODEL = 'google/gemma-2-2b-it';
    private const DEFAULT_SYSTEM_PROMPT = <<<PROMPT
        Tu es un assistant pédagogique pour la plateforme Academ'Île. Tu aides les apprenants
        à comprendre les concepts de leurs formations, tu poses des questions pour évaluer leur
        compréhension et tu adaptes tes explications à leur niveau.
        Réponds toujours en français, de façon concise et encourageante.
        PROMPT;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingRepository $settingRepository,
        private readonly LoggerInterface $logger,
        private readonly string $fallbackApiKey = '',
    ) {}

    public function chat(string $userMessage, ?User $user = null): string
    {
        $apiKey = $this->settingRepository->getValue('ai_api_key') ?: $this->fallbackApiKey;
        $model = $this->settingRepository->getValue('ai_model') ?: self::DEFAULT_MODEL;
        $systemPrompt = $this->settingRepository->getValue('ai_system_prompt') ?: self::DEFAULT_SYSTEM_PROMPT;

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
                        ['role' => 'user', 'content' => $systemPrompt . "\n\n" . $userMessage],
                    ],
                    'max_tokens' => 512,
                    'temperature' => 0.7,
                ],
                'timeout' => 15,
            ]);

            $data = $response->toArray();

            $this->logger->debug('AI request completed', [
                'user' => $user?->getEmail(),
                'model' => $model,
            ]);

            return $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de réponse.';
        } catch (\Throwable $e) {
            $this->logger->error('AI service error', [
                'error' => $e->getMessage(),
                'user' => $user?->getEmail(),
                'model' => $model ?? 'unknown',
            ]);

            return 'Le service IA est temporairement indisponible. Veuillez réessayer.';
        }
    }
}
