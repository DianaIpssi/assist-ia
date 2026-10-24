<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\Appointment;
use App\Entity\AvailabilitySlot;
use App\Entity\Property;
use App\Tests\DatabaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppointmentControllerTest extends DatabaseWebTestCase
{
    public function testAppointmentPageIsAccessible(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->seedAvailableSlot();

        $client->request('GET', '/rdv');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Prendre rendez-vous');
    }

    public function testAppointmentBookingReservesSlot(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $slot = $this->seedAvailableSlot();

        $agentId = $slot->getAgent()->getId();
        $crawler = $client->request('GET', '/rdv?agent='.$agentId);
        $form = $crawler->selectButton('Confirmer le rendez-vous')->form([
            'appointment[slot]' => (string) $slot->getId(),
            'appointment[clientName]' => 'Client Test',
            'appointment[clientEmail]' => 'client@test.fr',
            'appointment[clientPhone]' => '0100000000',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/rdv?agent='.$agentId);

        $client->followRedirect();
        $this->assertSelectorTextContains('.bg-green-50', 'enregistré');
    }

    // Fonction qui vérifie que le visiteur choisit un agent et que l'administrateur n'apparaît pas
    public function testAgentListShowsAgentsWithoutAdministrator(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $this->createAgent('sophie@assist-ia.fr', 'Sophie Martin', 'Rhône Habitat', ['ROLE_AGENT']);
        $this->createAgent('admin@assist-ia.fr', 'Administrateur', null, ['ROLE_ADMIN', 'ROLE_AGENT']);

        $client->request('GET', '/rdv');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Sophie Martin');
        $this->assertSelectorTextContains('body', 'Rhône Habitat');
        $this->assertSelectorTextNotContains('body', 'Administrateur');
    }

    // Fonction qui vérifie que seuls les créneaux de l'agent choisi sont proposés
    public function testSlotsAreFilteredByChosenAgent(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $sophie = $this->createAgent('sophie@assist-ia.fr', 'Sophie Martin', 'Rhône Habitat', ['ROLE_AGENT']);
        $paul = $this->createAgent('paul@assist-ia.fr', 'Paul Bernard', 'Nord Habitat', ['ROLE_AGENT']);

        $crawler = $client->request('GET', '/rdv?agent='.$sophie->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Rendez-vous avec Sophie Martin');

        $paulSlotIds = array_map(
            static fn (AvailabilitySlot $slot): string => (string) $slot->getId(),
            static::getContainer()->get('doctrine')->getRepository(AvailabilitySlot::class)->findBy(['agent' => $paul])
        );
        $optionValues = $crawler->filter('select[name="appointment[slot]"] option[value]:not([value=""])')->extract(['value']);

        $this->assertNotEmpty($optionValues);
        $this->assertEmpty(array_intersect($optionValues, $paulSlotIds));
    }

    // Fonction qui vérifie que, depuis la fiche d'un bien, le message est prérempli et enregistré avec le rendez-vous
    public function testMessageIsPrefilledFromPropertyAndSaved(): void
    {
        $client = $this->createClientWithFreshDatabase();
        $slot = $this->seedAvailableSlot();
        $agent = $slot->getAgent();
        $property = (new Property())
            ->setTitle('T2 avec balcon')
            ->setPrice(180000)
            ->setStatus(Property::STATUS_PUBLISHED)
            ->setAgent($agent);
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($property);
        $entityManager->flush();

        $crawler = $client->request('GET', '/rdv?agent='.$agent->getId().'&bien='.$property->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('T2 avec balcon', $crawler->filter('textarea[name="appointment[message]"]')->text());

        $client->submit($crawler->selectButton('Confirmer le rendez-vous')->form([
            'appointment[slot]' => (string) $slot->getId(),
            'appointment[clientName]' => 'Client Test',
            'appointment[clientEmail]' => 'client@test.fr',
            'appointment[message]' => 'Je souhaite visiter le bien « T2 avec balcon », plutôt en fin de journée.',
        ]));

        $this->assertResponseRedirects('/rdv?agent='.$agent->getId());
        $appointment = static::getContainer()->get('doctrine')->getRepository(Appointment::class)->findOneBy(['clientEmail' => 'client@test.fr']);
        $this->assertStringContainsString('fin de journée', $appointment->getMessage());
    }

    // Fonction qui crée un agent de test
    private function createAgent(string $email, string $fullName, ?string $agencyName, array $roles): Agent
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail($email)
            ->setFullName($fullName)
            ->setAgencyName($agencyName)
            ->setRoles($roles);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $entityManager->persist($agent);
        $entityManager->flush();

        return $agent;
    }

    private function seedAvailableSlot(): AvailabilitySlot
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $agent = (new Agent())
            ->setEmail('rdv-agent@assist-ia.fr')
            ->setFullName('Agent RDV')
            ->setRoles(['ROLE_AGENT']);
        $agent->setPassword($hasher->hashPassword($agent, 'Agent123!'));

        $start = (new \DateTimeImmutable())->modify('+2 days')->setTime(14, 0);
        $slot = (new AvailabilitySlot())
            ->setAgent($agent)
            ->setStartAt($start)
            ->setEndAt($start->modify('+1 hour'))
            ->setIsAvailable(true)
            ->setNote('Visite');

        $entityManager->persist($agent);
        $entityManager->persist($slot);
        $entityManager->flush();

        return $slot;
    }
}
