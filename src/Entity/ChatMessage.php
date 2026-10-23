<?php

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $sessionId = null;

    #[ORM\Column(length: 20)]
    private ?string $role = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $clientName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $clientEmail = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Fonction qui initialise l'objet
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Fonction qui retourne l'identifiant
    public function getId(): ?int
    {
        return $this->id;
    }

    // Fonction qui retourne l'id de conversation
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    // Fonction qui définit l'id de conversation
    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    // Fonction qui retourne le rôle (user ou assistant)
    public function getRole(): ?string
    {
        return $this->role;
    }

    // Fonction qui définit le rôle du message
    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    // Fonction qui retourne le texte du message
    public function getContent(): ?string
    {
        return $this->content;
    }

    // Fonction qui définit le texte du message
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    // Fonction qui retourne le nom du client
    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    // Fonction qui définit le nom du client
    public function setClientName(?string $clientName): static
    {
        $this->clientName = $clientName;

        return $this;
    }

    // Fonction qui retourne l'email du client
    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    // Fonction qui définit l'email du client
    public function setClientEmail(?string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;

        return $this;
    }

    // Fonction qui retourne Created At
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Fonction qui définit Created At
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
