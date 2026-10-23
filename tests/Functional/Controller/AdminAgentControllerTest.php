<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminAgentControllerTest extends DatabaseWebTestCase
{
    // Fonction qui vérifie que l'admin crée un agent avec un mot de passe haché
    public function testAdminCreatesAgentWithHashedPassword(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $admin = $this->createAgent('admin@assist-ia.fr', ['ROLE_ADMIN', 'ROLE_AGENT']);

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/admin/agent/new');
        $form = $crawler->filter('form[name="Agent"]')->form();
        $form['Agent[fullName]'] = 'Lucas Bernard';
        $form['Agent[email]'] = 'lucas@assist-ia.fr';
        $form['Agent[agencyName]'] = 'Rhône Habitat';
        $form['Agent[roles]'][0]->tick();
        $form['Agent[plainPassword]'] = 'Agent123!';
        $client->submit($form);

        $this->assertResponseRedirects();
        $agent = static::getContainer()->get('doctrine')->getRepository(Agent::class)->findOneBy(['email' => 'lucas@assist-ia.fr']);
        $this->assertNotNull($agent);
        $this->assertSame('Rhône Habitat', $agent->getAgencyName());
        $this->assertNotSame('Agent123!', $agent->getPassword());
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($agent, 'Agent123!'));
    }

    // Fonction qui vérifie qu'un agent simple ne peut pas gérer les agents
    public function testAgentCannotAccessAgentManagement(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $agent = $this->createAgent('sophie@assist-ia.fr', ['ROLE_AGENT']);

        $client->loginUser($agent);
        $client->request('GET', '/admin/agent');

        $this->assertResponseStatusCodeSame(403);
    }

    // Fonction qui vérifie que l'admin voit tous les biens avec leur agent et leur statut
    public function testAdminPropertyListShowsAgentAndStatus(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $admin = $this->createAgent('admin@assist-ia.fr', ['ROLE_ADMIN', 'ROLE_AGENT']);
        $agent = $this->createAgent('sophie@assist-ia.fr', ['ROLE_AGENT'])->setFullName('Sophie Martin');
        $property = (new Property())
            ->setTitle('T2 avec balcon')
            ->setPrice(180000)
            ->setCity('Lyon')
            ->setStatus(Property::STATUS_PUBLISHED)
            ->setAgent($agent);
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($property);
        $entityManager->flush();

        $client->loginUser($admin);
        $client->request('GET', '/admin/property');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tous les biens');
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Sophie Martin', $content);
        $this->assertStringContainsString('Publié', $content);
    }

    // Fonction qui crée un agent de test
    private function createAgent(string $email, array $roles): Agent
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail($email)
            ->setFullName('Sophie')
            ->setRoles($roles);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $entityManager->persist($agent);
        $entityManager->flush();

        return $agent;
    }
}
