<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AppointmentMailerService
{
    // Fonction qui initialise l'objet
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    // Fonction qui envoie l'email de confirmation au client
    public function sendConfirmation(Appointment $appointment): void
    {
        $slot = $appointment->getSlot();
        if ($slot === null) {
            return;
        }

        $date = $slot->getStartAt()?->format('d/m/Y H:i') ?? 'date à confirmer';

        $email = (new Email())
            ->from('noreply@assist-ia.fr')
            ->to($appointment->getClientEmail() ?? '')
            ->subject('Confirmation de votre rendez-vous Assist\'IA')
            ->text(sprintf(
                "Bonjour %s,\n\nVotre rendez-vous est bien enregistré pour le %s.\nUn agent vous contactera pour confirmer.\n\nAssist'IA",
                $appointment->getClientName(),
                $date
            ));

        $this->mailer->send($email);
    }

    // Fonction qui prévient l'agent par email
    public function notifyAgent(Appointment $appointment): void
    {
        $agent = $appointment->getAgent();
        $slot = $appointment->getSlot();
        if ($agent === null || $slot === null || $agent->getEmail() === null) {
            return;
        }

        $date = $slot->getStartAt()?->format('d/m/Y H:i') ?? '';
        $message = $appointment->getMessage() ? "\n\nMessage du client : ".$appointment->getMessage() : '';

        $email = (new Email())
            ->from('noreply@assist-ia.fr')
            ->to($agent->getEmail())
            ->subject('Nouveau rendez-vous client Assist\'IA')
            ->text(sprintf(
                "Bonjour %s,\n\n%s (%s) a réservé un créneau le %s.%s\n\nAssist'IA",
                $agent->getFullName(),
                $appointment->getClientName(),
                $appointment->getClientEmail(),
                $date,
                $message
            ));

        $this->mailer->send($email);
    }
}
