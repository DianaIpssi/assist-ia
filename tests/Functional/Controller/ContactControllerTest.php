<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ContactControllerTest extends DatabaseWebTestCase
{
    // Fonction qui vérifie qu'un message général part vers l'agence
    public function testGeneralContactIsSentToAgency(): void
    {
        $client = $this->createClientWithFreshDatabase();

        $crawler = $client->request('GET', '/contact');
        $client->submit($crawler->selectButton('Envoyer')->form([
            'contact[name]' => 'Julie Visiteur',
            'contact[email]' => 'julie@test.fr',
            'contact[message]' => 'Quels sont vos frais d\'agence ?',
        ]));

        $this->assertQueuedEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'To', 'contact@assist-ia.fr');
        $this->assertEmailAddressContains($email, 'Reply-To', 'julie@test.fr');
        $this->assertResponseRedirects('/contact');
    }

    // Fonction qui vérifie qu'un message sur un bien part vers l'agent de ce bien
    public function testContactForPropertyIsSentToItsAgent(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $property = $this->createPublishedPropertyForAgent('sophie@assist-ia.fr');

        $crawler = $client->request('GET', '/contact?bien='.$property->getId());
        $this->assertSelectorTextContains('body', 'Sophie Martin');

        $client->submit($crawler->selectButton('Envoyer')->form([
            'contact[name]' => 'Julie Visiteur',
            'contact[email]' => 'julie@test.fr',
            'contact[message]' => 'Le T2 est-il toujours disponible ?',
        ]));

        $this->assertQueuedEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'To', 'sophie@assist-ia.fr');
        $this->assertEmailHeaderSame($email, 'Subject', 'Nouveau message pour le bien « T2 Lyon centre »');
        $this->assertResponseRedirects('/contact?bien='.$property->getId());
    }

    // Fonction qui crée un agent et un bien publié qui lui appartient
    private function createPublishedPropertyForAgent(string $agentEmail): Property
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail($agentEmail)
            ->setFullName('Sophie Martin')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $property = (new Property())
            ->setTitle('T2 Lyon centre')
            ->setPrice(180000)
            ->setCity('Lyon')
            ->setStatus(Property::STATUS_PUBLISHED)
            ->setAgent($agent);

        $entityManager->persist($agent);
        $entityManager->persist($property);
        $entityManager->flush();

        return $property;
    }
}
