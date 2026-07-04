<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Agent;
use App\Entity\AvailabilitySlot;
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

        $crawler = $client->request('GET', '/rdv');
        $form = $crawler->selectButton('Confirmer le rendez-vous')->form([
            'appointment[slot]' => (string) $slot->getId(),
            'appointment[clientName]' => 'Client Test',
            'appointment[clientEmail]' => 'client@test.fr',
            'appointment[clientPhone]' => '0100000000',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/rdv');

        $client->followRedirect();
        $this->assertSelectorTextContains('.bg-green-50', 'enregistré');
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
