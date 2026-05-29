<?php

namespace App\Tests\Unit\Service;

use App\Entity\Property;
use App\Repository\ChatMessageRepository;
use App\Repository\PropertyRepository;
use App\Service\OllamaChatService;
use App\Service\PropertyChatService;
use PHPUnit\Framework\TestCase;

class PropertyChatServiceTest extends TestCase
{
    public function testExtractBudgetFromMessage(): void
    {
        $service = $this->createService($this->createMock(PropertyRepository::class));

        $this->assertSame(150000, $service->extractBudget('mon budget est 150000 euros'));
        $this->assertNull($service->extractBudget('bonjour'));
        $this->assertNull($service->extractBudget('je cherche un T2'));
    }

    public function testBuildReplyForTypologySearch(): void
    {
        $property = (new Property())
            ->setTitle('T2 avec balcon')
            ->setPrice(180000)
            ->setStatus(Property::STATUS_PUBLISHED);

        $repository = $this->createMock(PropertyRepository::class);
        $repository->method('findPublishedByTitleKeyword')
            ->with('t2')
            ->willReturn([$property]);

        $service = $this->createService($repository);
        $reply = $service->buildReply('je cherche un T2');

        $this->assertStringContainsString('T2 avec balcon', $reply);
    }

    public function testBuildReplyWithoutBudget(): void
    {
        $repository = $this->createMock(PropertyRepository::class);
        $service = $this->createService($repository);

        $this->assertSame(
            'Quel est ton budget maximum ? (ex : 150000 €)',
            $service->buildReply('bonjour, je débute ma recherche')
        );
    }

    public function testBuildReplyWithMatchingProperties(): void
    {
        $property = (new Property())
            ->setTitle('T2 centre-ville')
            ->setPrice(120000)
            ->setStatus(Property::STATUS_PUBLISHED);

        $repository = $this->createMock(PropertyRepository::class);
        $repository->method('findPublishedByMaxBudget')
            ->with(150000)
            ->willReturn([$property]);

        $service = $this->createService($repository);
        $reply = $service->buildReply('budget 150000');

        $this->assertStringContainsString('T2 centre-ville', $reply);
        $this->assertStringContainsString('120000', $reply);
    }

    public function testBuildReplyWhenNoPropertyMatches(): void
    {
        $repository = $this->createMock(PropertyRepository::class);
        $repository->method('findPublishedByMaxBudget')
            ->with(50000)
            ->willReturn([]);

        $service = $this->createService($repository);

        $this->assertSame(
            'Je n\'ai trouvé aucun bien publié sous 50000 €.',
            $service->buildReply('50000 max')
        );
    }

    private function createService(PropertyRepository $repository): PropertyChatService
    {
        $ollama = $this->createMock(OllamaChatService::class);
        $ollama->method('isAvailable')->willReturn(false);

        $chatRepository = $this->createMock(ChatMessageRepository::class);

        return new PropertyChatService($repository, $ollama, $chatRepository);
    }
}
