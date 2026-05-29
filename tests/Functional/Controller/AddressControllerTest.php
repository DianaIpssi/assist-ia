<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AddressControllerTest extends DatabaseWebTestCase
{
    public function testAddressSearchRequiresAuthentication(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/api/address/search?q=paris');

        $this->assertResponseRedirects('/login');
    }

    public function testAddressSearchReturnsBadRequestWithoutQuery(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->createAgent();

        $agent = static::getContainer()->get('doctrine')->getRepository(Agent::class)->findOneBy(['email' => 'agent@assist-ia.fr']);
        $client->loginUser($agent);
        $client->request('GET', '/api/address/search');

        $this->assertResponseStatusCodeSame(400);
    }

    private function createAgent(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail('agent@assist-ia.fr')
            ->setFullName('Agent API')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $entityManager->persist($agent);
        $entityManager->flush();
    }
}
