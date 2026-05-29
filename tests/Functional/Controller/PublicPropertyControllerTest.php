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

    public function testDraftPropertyDetailIsNotAccessible(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $property = $this->seedProperty('Brouillon secret', Property::STATUS_DRAFT);
        $client->request('GET', '/biens/'.$property->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    private function seedProperty(string $title, string $status): Property
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
            ->setPrice(120000)
            ->setStatus($status)
            ->setAgent($agent);

        $entityManager->persist($agent);
        $entityManager->persist($property);
        $entityManager->flush();

        return $property;
    }
}
