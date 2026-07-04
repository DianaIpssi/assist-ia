<?php

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $price = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Agent $agent = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $postalCode = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $surface = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $rooms = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $bedrooms = null;

    #[ORM\Column(length: 30)]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 255)]
    private ?string $draft = 'yes';

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(requireTld: false)]
    private ?string $imageUrl = null;

    // Fonction qui retourne l'identifiant
    public function getId(): ?int
    {
        return $this->id;
    }

    // Fonction qui retourne le titre
    public function getTitle(): ?string
    {
        return $this->title;
    }

    // Fonction qui définit le titre
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    // Fonction qui retourne le prix
    public function getPrice(): ?int
    {
        return $this->price;
    }

    // Fonction qui définit le prix
    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    // Fonction qui retourne la description
    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Fonction qui définit la description
    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    // Fonction qui retourne la ville
    public function getCity(): ?string
    {
        return $this->city;
    }

    // Fonction qui définit la ville
    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    // Fonction qui retourne le code postal
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    // Fonction qui définit le code postal
    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    // Fonction qui retourne Surface
    public function getSurface(): ?int
    {
        return $this->surface;
    }

    // Fonction qui définit Surface
    public function setSurface(?int $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    // Fonction qui retourne Rooms
    public function getRooms(): ?int
    {
        return $this->rooms;
    }

    // Fonction qui définit Rooms
    public function setRooms(?int $rooms): static
    {
        $this->rooms = $rooms;

        return $this;
    }

    // Fonction qui retourne Bedrooms
    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    // Fonction qui définit Bedrooms
    public function setBedrooms(?int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;

        return $this;
    }

    // Fonction qui retourne Status
    public function getStatus(): ?string
    {
        return $this->status;
    }

    // Fonction qui définit Status
    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->draft = $status === self::STATUS_PUBLISHED ? 'no' : 'yes';

        return $this;
    }
    // Fonction qui indique si le bien est publié. 
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    // Fonction qui retourne Draft
    public function getDraft(): ?string
    {
        return $this->draft;
    }

    // Fonction qui définit Draft
    public function setDraft(string $draft): static
    {
        $this->draft = $draft;

        return $this;
    }

    // Fonction qui retourne Image Url
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    // Fonction qui définit Image Url
    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }
    // Fonction qui retourne l'URL d'image à afficher (avec fallback). 
    public function getDisplayImageUrl(): string
    {
        return $this->imageUrl ?? 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80';
    }
}
