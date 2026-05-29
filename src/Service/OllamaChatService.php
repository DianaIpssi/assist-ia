<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaChatService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly bool $enabled,
        private readonly string $baseUrl,
        private readonly string $model,
    ) {
    }

    /** Fonction qui vérifie si Ollama est activé dans la config. */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->baseUrl !== '' && $this->model !== '';
    }

    /** Fonction qui teste si le serveur Ollama répond. */
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

    /**
     * Fonction qui envoie des messages à Ollama et récupère la réponse.
     *
     * @param list<array{role: string, content: string}> $messages
     */
    public function chat(array $messages): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/').'/api/chat', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ],
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
