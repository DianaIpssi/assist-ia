<?php

namespace App\Tests\Unit\Service;

use App\Entity\Property;
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
            ->setCity('Lyon')
            ->setStatus(Property::STATUS_PUBLISHED);

        $repository = $this->createMock(PropertyRepository::class);
        $repository->method('findPublishedByMaxBudget')
            ->with(150000)
            ->willReturn([$property]);

        $service = $this->createService($repository);
        $reply = $service->buildReply('budget 150000');

        $this->assertStringContainsString('T2 centre-ville', $reply);
        $this->assertStringContainsString('120 000', $reply);
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

    // Fonction qui vérifie les réponses à une salutation et à une demande de RDV
    public function testBuildReplyForGreetingAndAppointmentRequest(): void
    {
        $repository = $this->createMock(PropertyRepository::class);
        $repository->expects($this->never())->method('findPublished');
        $service = $this->createService($repository);

        $this->assertStringContainsString('Bonjour', $service->buildReply('Bonjour !'));
        $this->assertStringContainsString('/rdv', $service->buildReply('peux-tu me réserver un créneau avec un agent ?'));
    }

    // Fonction qui vérifie qu'Ollama sans critère ne renvoie pas tout le catalogue
    public function testOllamaReplyWithoutCriteriaAsksBudget(): void
    {
        $repository = $this->createMock(PropertyRepository::class);
        $repository->expects($this->never())->method('findPublished');

        $ollama = $this->createMock(OllamaChatService::class);
        $ollama->method('isAvailable')->willReturn(true);
        $ollama->method('chat')->willReturn('{"budget":null,"city":null,"keyword":null}');

        $service = new PropertyChatService($repository, $ollama);

        $this->assertSame(
            'Quel est ton budget maximum ? (ex : 150000 €)',
            $service->buildReply('je débute ma recherche')
        );
    }

    // Fonction qui vérifie que le chat renvoie vers le catalogue filtré avec les critères compris
    public function testReplyLinksToFilteredCatalogue(): void
    {
        $property = (new Property())
            ->setTitle('T2 avec balcon')
            ->setPrice(180000)
            ->setCity('Lyon')
            ->setStatus(Property::STATUS_PUBLISHED);

        $repository = $this->createMock(PropertyRepository::class);
        $repository->method('findPublishedByFilters')
            ->with(200000, 'Lyon', 't2')
            ->willReturn([$property]);

        $ollama = $this->createMock(OllamaChatService::class);
        $ollama->method('isAvailable')->willReturn(true);
        $ollama->method('chat')->willReturn('{"budget":200000,"city":"Lyon","keyword":"t2"}');

        $service = new PropertyChatService($repository, $ollama);
        $reply = $service->buildReply('Je cherche un T2 à Lyon pour 200000 €');

        $this->assertStringContainsString('/biens?ville=Lyon&type=t2&budget=200000', $reply);
    }

    // Fonction qui vérifie qu'aucun critère donne le lien vers tout le catalogue
    public function testCatalogueLinkWithoutCriteria(): void
    {
        $service = $this->createService($this->createMock(PropertyRepository::class));

        $this->assertSame('/biens', $service->buildCatalogueLink([]));
    }

    private function createService(PropertyRepository $repository): PropertyChatService
    {
        $ollama = $this->createMock(OllamaChatService::class);
        $ollama->method('isAvailable')->willReturn(false);

        return new PropertyChatService($repository, $ollama);
    }
}
