<?php

namespace App\Service;

use App\Entity\ChatMessage;
use Doctrine\ORM\EntityManagerInterface;

class ChatStorageService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /** Fonction qui enregistre un message de chat en base de données. */
    public function save(string $sessionId, string $role, string $content, ?string $clientName = null, ?string $clientEmail = null): void
    {
        $message = (new ChatMessage())
            ->setSessionId($sessionId)
            ->setRole($role)
            ->setContent($content)
            ->setClientName($clientName)
            ->setClientEmail($clientEmail);

        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }
}
