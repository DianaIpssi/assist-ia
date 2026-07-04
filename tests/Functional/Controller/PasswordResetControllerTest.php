<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetControllerTest extends DatabaseWebTestCase
{
    public function testForgotPasswordPageIsAccessible(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $client->request('GET', '/forgot-password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mot de passe oublié');
    }

    public function testForgotPasswordShowsSuccessEvenForUnknownEmail(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $crawler = $client->request('GET', '/forgot-password');

        $form = $crawler->selectButton('Envoyer le lien')->form([
            'forgot_password[email]' => 'inconnu@test.fr',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/forgot-password');
        $client->followRedirect();
        $this->assertSelectorExists('.bg-green-50');
    }

    public function testResetPasswordWithValidToken(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $token = $this->seedAgentWithToken();

        $crawler = $client->request('GET', '/reset-password/'.$token);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'reset_password[plainPassword][first]' => 'Nouveau123!',
            'reset_password[plainPassword][second]' => 'Nouveau123!',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    private function seedAgentWithToken(): string
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $token = bin2hex(random_bytes(16));
        $agent = (new Agent())
            ->setEmail('reset-agent@assist-ia.fr')
            ->setFullName('Agent Reset')
            ->setRoles(['ROLE_AGENT'])
            ->setResetToken($token)
            ->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $entityManager->persist($agent);
        $entityManager->flush();

        return $token;
    }
}
