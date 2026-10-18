<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends DatabaseWebTestCase
{
    // Fonction qui vérifie qu'un agent enregistre le nom de son agence
    public function testAgentUpdatesAgencyName(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $agent = $this->createAgent('sophie@assist-ia.fr');

        $client->loginUser($agent);
        $crawler = $client->request('GET', '/agent/profil');
        $client->submit($crawler->selectButton('Enregistrer')->form([
            'profile[fullName]' => 'Sophie Martin',
            'profile[agencyName]' => 'Rhône Habitat',
        ]));

        $this->assertResponseRedirects('/agent/profil');
        $updated = static::getContainer()->get('doctrine')->getRepository(Agent::class)->findOneBy(['email' => 'sophie@assist-ia.fr']);
        $this->assertSame('Rhône Habitat', $updated->getAgencyName());
    }

    // Fonction qui vérifie qu'un visiteur non connecté ne peut pas accéder au profil
    public function testProfileRequiresLogin(): void
    {
        $client = $this->createClientWithFreshDatabase();

        $client->request('GET', '/agent/profil');

        $this->assertResponseRedirects('/login');
    }

    // Fonction qui crée un agent de test
    private function createAgent(string $email): Agent
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail($email)
            ->setFullName('Sophie')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $entityManager->persist($agent);
        $entityManager->flush();

        return $agent;
    }
}
