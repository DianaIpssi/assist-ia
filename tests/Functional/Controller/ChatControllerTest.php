<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChatControllerTest extends DatabaseWebTestCase
{
    public function testChatAsksForBudgetWhenMissing(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request(
            'POST',
            '/api/chat',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['message' => 'bonjour'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Quel est ton budget maximum', $payload['reply']);
        $this->assertNotEmpty($payload['sessionId']);
    }

    public function testChatReturnsPublishedPropertiesWithinBudget(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->seedPublishedProperty('Appartement test', 100000);
        $client->request(
            'POST',
            '/api/chat',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['message' => '150000 euros'], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Appartement test', $payload['reply']);
    }

    private function seedPublishedProperty(string $title, int $price): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail('seed-agent@assist-ia.fr')
            ->setFullName('Seed Agent')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $property = (new Property())
            ->setTitle($title)
            ->setPrice($price)
            ->setStatus(Property::STATUS_PUBLISHED)
            ->setAgent($agent);

        $entityManager->persist($agent);
        $entityManager->persist($property);
        $entityManager->flush();
    }
}
