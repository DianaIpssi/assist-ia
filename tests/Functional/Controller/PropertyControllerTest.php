<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PropertyControllerTest extends DatabaseWebTestCase
{
    // Fonction qui vérifie qu'un agent ne voit que ses propres biens.
    public function testAgentSeesOnlyHisProperties(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $sophie = $this->createAgent('sophie@assist-ia.fr');
        $marc = $this->createAgent('marc@assist-ia.fr');
        $this->createProperty('T2 de Sophie', $sophie);
        $this->createProperty('Loft de Marc', $marc);

        $client->loginUser($sophie);
        $client->request('GET', '/property');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('T2 de Sophie', $client->getResponse()->getContent());
        $this->assertStringNotContainsString('Loft de Marc', $client->getResponse()->getContent());
    }

    // Fonction qui vérifie qu'un agent ne peut pas modifier le bien d'un autre agent.
    public function testAgentCannotEditPropertyOfAnotherAgent(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $sophie = $this->createAgent('sophie@assist-ia.fr');
        $marc = $this->createAgent('marc@assist-ia.fr');
        $property = $this->createProperty('Loft de Marc', $marc);

        $client->loginUser($sophie);
        $client->request('GET', '/property/'.$property->getId().'/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    // Fonction qui vérifie qu'un agent ne peut pas supprimer le bien d'un autre agent.
    public function testAgentCannotDeletePropertyOfAnotherAgent(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $sophie = $this->createAgent('sophie@assist-ia.fr');
        $marc = $this->createAgent('marc@assist-ia.fr');
        $property = $this->createProperty('Loft de Marc', $marc);
        $propertyId = $property->getId();

        $client->loginUser($sophie);
        $client->request('POST', '/property/'.$propertyId, ['_token' => 'invalide']);

        $this->assertResponseStatusCodeSame(403);
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->clear();
        $this->assertNotNull($entityManager->find(Property::class, $propertyId));
    }

    // Fonction qui crée un agent de test en base.
    private function createAgent(string $email): Agent
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail($email)
            ->setFullName('Agent Test')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $entityManager->persist($agent);
        $entityManager->flush();

        return $agent;
    }

    // Fonction qui crée un bien de test rattaché à un agent.
    private function createProperty(string $title, Agent $agent): Property
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $property = (new Property())
            ->setTitle($title)
            ->setPrice(150000)
            ->setStatus(Property::STATUS_PUBLISHED)
            ->setAgent($agent);

        $entityManager->persist($property);
        $entityManager->flush();

        return $property;
    }
}
