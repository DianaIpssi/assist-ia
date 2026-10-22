<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PublicPropertyControllerTest extends DatabaseWebTestCase
{
    public function testPublicIndexShowsOnlyPublishedProperties(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->seedProperty('Bien publié', Property::STATUS_PUBLISHED);
        $this->seedProperty('Bien brouillon', Property::STATUS_DRAFT);
        $crawler = $client->request('GET', '/biens');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Bien publié', $client->getResponse()->getContent());
        $this->assertStringNotContainsString('Bien brouillon', $client->getResponse()->getContent());
    }

    // Fonction qui vérifie que le catalogue filtre par ville, type et budget
    public function testPublicIndexFiltersByCityTypeAndBudget(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->seedProperty('T2 avec balcon', Property::STATUS_PUBLISHED, 180000, 'Lyon');
        $this->seedProperty('T2 trop cher', Property::STATUS_PUBLISHED, 250000, 'Lyon');
        $this->seedProperty('T2 parisien', Property::STATUS_PUBLISHED, 150000, 'Paris');
        $this->seedProperty('Maison lyonnaise', Property::STATUS_PUBLISHED, 170000, 'Lyon');

        $crawler = $client->request('GET', '/biens?ville=lyon&type=t2&budget=200000');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('T2 avec balcon', $content);
        $this->assertStringNotContainsString('T2 trop cher', $content);
        $this->assertStringNotContainsString('T2 parisien', $content);
        $this->assertStringNotContainsString('Maison lyonnaise', $content);
        $this->assertSame('lyon', $crawler->filter('#filter-ville')->attr('value'));
        $this->assertSame('t2', $crawler->filter('#filter-type option[selected]')->attr('value'));
    }

    // Fonction qui vérifie qu'un filtre invalide est ignoré au lieu de provoquer une erreur
    public function testPublicIndexIgnoresInvalidFilters(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->seedProperty('Bien publié', Property::STATUS_PUBLISHED);

        $client->request('GET', '/biens?type=<script>&budget=abc');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Bien publié', $client->getResponse()->getContent());
    }

    public function testDraftPropertyDetailIsNotAccessible(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $property = $this->seedProperty('Brouillon secret', Property::STATUS_DRAFT);
        $client->request('GET', '/biens/'.$property->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    private function seedProperty(string $title, string $status, int $price = 120000, ?string $city = null): Property
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail(uniqid('agent-', true).'@assist-ia.fr')
            ->setFullName('Agent Test')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $property = (new Property())
            ->setTitle($title)
            ->setPrice($price)
            ->setCity($city)
            ->setStatus($status)
            ->setAgent($agent);

        $entityManager->persist($agent);
        $entityManager->persist($property);
        $entityManager->flush();

        return $property;
    }
}
