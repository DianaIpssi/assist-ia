<?php

namespace App\Tests\Unit\Service;

use App\Service\OllamaChatService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OllamaChatServiceTest extends TestCase
{
    // Fonction qui vérifie qu'Ollama est forcé à répondre en JSON avec une température à 0
    public function testChatAsksJsonWithTemperatureZero(): void
    {
        $sentBody = null;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$sentBody): MockResponse {
            $sentBody = json_decode($options['body'], true);

            return new MockResponse(json_encode(['message' => ['content' => '{"budget":200000,"city":"Lyon","keyword":"t2"}']]));
        });

        $service = new OllamaChatService($client, true, 'http://ollama:11434', 'llama3.2');
        $reply = $service->chat([['role' => 'user', 'content' => 'un T2 à Lyon pour 200000 €']], true);

        $this->assertSame('{"budget":200000,"city":"Lyon","keyword":"t2"}', $reply);
        $this->assertSame('json', $sentBody['format']);
        $this->assertSame(0, $sentBody['options']['temperature']);
    }

    // Fonction qui vérifie qu'Ollama désactivé ne fait aucun appel
    public function testChatReturnsNullWhenDisabled(): void
    {
        $client = new MockHttpClient(function (): MockResponse {
            $this->fail('Aucun appel ne doit partir quand Ollama est désactivé.');
        });

        $service = new OllamaChatService($client, false, 'http://ollama:11434', 'llama3.2');

        $this->assertNull($service->chat([['role' => 'user', 'content' => 'bonjour']], true));
    }
}
