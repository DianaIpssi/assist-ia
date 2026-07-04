<?php

namespace App\Service;

use App\Entity\Agent;
use App\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetService
{
    // Fonction qui initialise l'objet
    public function __construct(
        private readonly AgentRepository $agentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    // Fonction qui crée un lien de réinitialisation mot de passe
    public function requestReset(string $email): bool
    {
        $agent = $this->agentRepository->findOneBy(['email' => $email]);
        if (!$agent) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $agent
            ->setResetToken($token)
            ->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $emailMessage = (new Email())
            ->from('noreply@assist-ia.fr')
            ->to($agent->getEmail() ?? $email)
            ->subject('Réinitialisation de votre mot de passe Assist\'IA')
            ->text(sprintf(
                "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe (valable 1h) :\n%s\n\nAssist'IA",
                $resetUrl
            ));

        $this->mailer->send($emailMessage);

        return true;
    }

    // Fonction qui trouve un agent via son token
    public function findAgentByToken(string $token): ?Agent
    {
        $agent = $this->agentRepository->findOneBy(['resetToken' => $token]);
        if (!$agent || $agent->getResetTokenExpiresAt() === null) {
            return null;
        }

        if ($agent->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return null;
        }

        return $agent;
    }

    // Fonction qui enregistre le nouveau mot de passe
    public function resetPassword(Agent $agent, string $plainPassword): void
    {
        $agent->setPassword($this->passwordHasher->hashPassword($agent, $plainPassword));
        $agent->setResetToken(null);
        $agent->setResetTokenExpiresAt(null);

        $this->entityManager->flush();
    }
}
