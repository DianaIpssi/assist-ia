<?php

namespace App\Entity;

use App\Repository\AvailabilitySlotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilitySlotRepository::class)]
class AvailabilitySlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column]
    private ?bool $isAvailable = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Agent $agent = null;

    // Fonction qui retourne l'identifiant
    public function getId(): ?int
    {
        return $this->id;
    }

    // Fonction qui retourne l'heure de début
    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    // Fonction qui définit l'heure de début
    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;
        return $this;
    }

    // Fonction qui retourne l'heure de fin
    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    // Fonction qui définit l'heure de fin
    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    // Fonction qui indique si le créneau est libre
    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    // Fonction qui définit si le créneau est libre
    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    // Fonction qui retourne Note
    public function getNote(): ?string
    {
        return $this->note;
    }

    // Fonction qui définit Note
    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
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
}
