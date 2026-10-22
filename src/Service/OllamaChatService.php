<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaChatService
{
    // Fonction qui initialise l'objet
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly bool $enabled,
        private readonly string $baseUrl,
        private readonly string $model,
    ) {
    }

    // Fonction qui indique si Ollama est activé dans la config
    public function isEnabled(): bool
    {
        return $this->enabled && $this->baseUrl !== '' && $this->model !== '';
    }

    // Fonction qui vérifie si Ollama répond
    public function isAvailable(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = $this->httpClient->request('GET', rtrim($this->baseUrl, '/').'/api/tags', [
                'timeout' => 3,
            ]);

            return $response->getStatusCode() === 200;
        } catch (TransportExceptionInterface) {
            return false;
        }
    }

    // Fonction qui envoie un message à Ollama et récupère la réponse
    public function chat(array $messages, bool $jsonOnly = false): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            // Température à 0 : la même question donne toujours la même réponse
            'options' => ['temperature' => 0],
        ];
        if ($jsonOnly) {
            // Ollama est obligé de répondre avec un JSON valide, sans texte autour
            $payload['format'] = 'json';
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/').'/api/chat', [
                'json' => $payload,
                'timeout' => 90,
            ]);

            $payload = $response->toArray(false);
            $content = $payload['message']['content'] ?? null;

            return \is_string($content) && trim($content) !== '' ? trim($content) : null;
        } catch (TransportExceptionInterface) {
            return null;
        }
    }
}
