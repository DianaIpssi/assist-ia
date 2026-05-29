<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends DatabaseWebTestCase
{
    public function testAgentCanLogin(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->createAgent('agent@assist-ia.fr', 'Agent123!', ['ROLE_AGENT']);
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'agent@assist-ia.fr',
            '_password' => 'Agent123!',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/agent');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testPropertyAreaRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/property');

        $this->assertResponseRedirects('/login');
    }

    /** @param list<string> $roles */
    private function createAgent(string $email, string $plainPassword, array $roles): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail($email)
            ->setFullName('Test Agent')
            ->setRoles($roles);
        $agent->setPassword($hasher->hashPassword($agent, $plainPassword));

        $entityManager->persist($agent);
        $entityManager->flush();
    }
}
