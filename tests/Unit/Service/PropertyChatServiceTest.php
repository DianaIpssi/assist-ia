<?php

namespace App\Tests\Unit\Service;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use App\Service\PropertyChatService;
use PHPUnit\Framework\TestCase;

class PropertyChatServiceTest extends TestCase
{
    public function testExtractBudgetFromMessage(): void
    {
        $repository = $this->createMock(PropertyRepository::class);
        $service = new PropertyChatService($repository);

        $this->assertSame(150000, $service->extractBudget('mon budget est 150000 euros'));
        $this->assertNull($service->extractBudget('bonjour'));
    }

    public function testBuildReplyWithoutBudget(): void
    {
        $repository = $this->createMock(PropertyRepository::class);
        $service = new PropertyChatService($repository);

        $this->assertSame(
            'Quel est ton budget maximum ?',
            $service->buildReply('je cherche un appartement')
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

        $service = new PropertyChatService($repository);
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

        $service = new PropertyChatService($repository);

        $this->assertSame(
            'Je n\'ai trouvé aucun bien publié sous 50000 €.',
            $service->buildReply('50000 max')
        );
    }
}
