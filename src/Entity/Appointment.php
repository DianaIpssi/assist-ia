<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agent $agent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?AvailabilitySlot $slot = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private ?string $clientName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $clientPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

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

    // Fonction qui retourne l'agent
    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    // Fonction qui définit l'agent
    public function setAgent(?Agent $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    // Fonction qui retourne Slot
    public function getSlot(): ?AvailabilitySlot
    {
        return $this->slot;
    }

    // Fonction qui définit Slot
    public function setSlot(?AvailabilitySlot $slot): static
    {
        $this->slot = $slot;

        return $this;
    }

    // Fonction qui retourne le nom du client
    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    // Fonction qui définit le nom du client
    public function setClientName(string $clientName): static
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
    public function setClientEmail(string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;

        return $this;
    }

    // Fonction qui retourne Client Phone
    public function getClientPhone(): ?string
    {
        return $this->clientPhone;
    }

    // Fonction qui définit Client Phone
    public function setClientPhone(?string $clientPhone): static
    {
        $this->clientPhone = $clientPhone;

        return $this;
    }

    // Fonction qui retourne Message
    public function getMessage(): ?string
    {
        return $this->message;
    }

    // Fonction qui définit Message
    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    // Fonction qui retourne Created At
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
