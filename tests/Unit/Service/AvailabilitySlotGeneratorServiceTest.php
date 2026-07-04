<?php

namespace App\Tests\Unit\Service;

use App\Entity\Agent;
use App\Repository\AgentRepository;
use App\Repository\AvailabilitySlotRepository;
use App\Service\AvailabilitySlotGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AvailabilitySlotGeneratorServiceTest extends TestCase
{
    public function testEnsureUpcomingSlotsCreatesSlotsForAgents(): void
    {
        $agent = (new Agent())
            ->setEmail('gen@assist-ia.fr')
            ->setFullName('Agent Gen')
            ->setRoles(['ROLE_AGENT']);

        $agentRepository = $this->createMock(AgentRepository::class);
        $agentRepository->method('findAll')->willReturn([$agent]);

        $slotRepository = $this->createMock(AvailabilitySlotRepository::class);
        $slotRepository->method('existsForAgentAt')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = new AvailabilitySlotGeneratorService($agentRepository, $slotRepository, $entityManager);
        $created = $service->ensureUpcomingSlots();

        $this->assertGreaterThan(0, $created);
    }
}
