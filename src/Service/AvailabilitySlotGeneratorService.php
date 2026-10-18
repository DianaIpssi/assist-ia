<?php

namespace App\Service;

use App\Entity\Agent;
use App\Entity\AvailabilitySlot;
use App\Repository\AgentRepository;
use App\Repository\AvailabilitySlotRepository;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilitySlotGeneratorService
{
    private const HORIZON_DAYS = 14;

    /** @var list<int> */
    private const SLOT_HOURS = [10, 14];

    // Fonction qui initialise l'objet
    public function __construct(
        private readonly AgentRepository $agentRepository,
        private readonly AvailabilitySlotRepository $slotRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    // Fonction qui génère les créneaux RDV manquants
    public function ensureUpcomingSlots(): int
    {
        $created = 0;

        foreach ($this->agentRepository->findAll() as $agent) {
            if (!\in_array('ROLE_AGENT', $agent->getRoles(), true) || \in_array('ROLE_ADMIN', $agent->getRoles(), true)) {
                continue;
            }

            $created += $this->ensureSlotsForAgent($agent);
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

 
    // Fonction qui génère les créneaux pour un agent
    private function ensureSlotsForAgent(Agent $agent): int
    {
        $created = 0;
        $today = new \DateTimeImmutable('today');

        for ($dayOffset = 1; $dayOffset <= self::HORIZON_DAYS; ++$dayOffset) {
            $day = $today->modify(sprintf('+%d days', $dayOffset));

            if ((int) $day->format('N') >= 6) {
                continue;
            }

            foreach (self::SLOT_HOURS as $hour) {
                $start = $day->setTime($hour, 0);
                if ($start <= new \DateTimeImmutable()) {
                    continue;
                }

                if ($this->slotRepository->existsForAgentAt($agent, $start)) {
                    continue;
                }

                $slot = (new AvailabilitySlot())
                    ->setAgent($agent)
                    ->setStartAt($start)
                    ->setEndAt($start->modify('+1 hour'))
                    ->setIsAvailable(true)
                    ->setNote('Créneau automatique');

                $this->entityManager->persist($slot);
                ++$created;
            }
        }

        return $created;
    }
}
