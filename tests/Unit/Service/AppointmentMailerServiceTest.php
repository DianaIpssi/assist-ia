<?php

namespace App\Tests\Unit\Service;

use App\Entity\Appointment;
use App\Entity\AvailabilitySlot;
use App\Service\AppointmentMailerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

class AppointmentMailerServiceTest extends TestCase
{
    public function testSendConfirmationDoesNotThrow(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $service = new AppointmentMailerService($mailer);

        $slot = (new AvailabilitySlot())
            ->setStartAt(new \DateTimeImmutable('2026-06-15 10:00'))
            ->setEndAt(new \DateTimeImmutable('2026-06-15 11:00'));

        $appointment = (new Appointment())
            ->setClientName('Test')
            ->setClientEmail('test@assist-ia.fr')
            ->setSlot($slot);

        $service->sendConfirmation($appointment);
    }
}
