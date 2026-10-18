<?php

namespace App\Service;

use App\Entity\Property;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactMailerService
{
    public const AGENCY_EMAIL = 'contact@assist-ia.fr';

    // Fonction qui initialise l'objet
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    // Fonction qui retourne l'email du destinataire du message
    public function getRecipientEmail(?Property $property): string
    {
        return $property?->getAgent()?->getEmail() ?? self::AGENCY_EMAIL;
    }

    // Fonction qui envoie le message du visiteur à l'agent du bien ou à l'agence
    public function send(string $name, string $email, string $message, ?Property $property = null): void
    {
        $subject = $property !== null
            ? sprintf('Nouveau message pour le bien « %s »', $property->getTitle())
            : 'Nouveau message de contact Assist\'IA';

        $intro = $property !== null
            ? sprintf("Un visiteur vous écrit au sujet du bien « %s » (%s).", $property->getTitle(), $property->getCity() ?? 'ville non précisée')
            : 'Un visiteur a envoyé un message depuis la page Contact.';

        $mail = (new Email())
            ->from('noreply@assist-ia.fr')
            ->to($this->getRecipientEmail($property))
            ->replyTo($email)
            ->subject($subject)
            ->text(sprintf(
                "Bonjour,\n\n%s\n\nNom : %s\nEmail : %s\n\nMessage :\n%s\n\nAssist'IA",
                $intro,
                $name,
                $email,
                $message
            ));

        $this->mailer->send($mail);
    }
}
