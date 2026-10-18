<?php

namespace App\Entity;

use App\Repository\AgentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
class Agent implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Property>
     */
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'agent')]
    private Collection $properties;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $fullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $agencyName = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = ['ROLE_AGENT'];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    // Fonction qui initialise l'objet
    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Fonction qui retourne l'identifiant
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Property>
     */
    // Fonction qui retourne la liste des biens
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    // Fonction qui associe un bien à l'agent
    public function addProperty(Property $property): static
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setAgent($this);
        }

        return $this;
    }

    // Fonction qui dissocie un bien de l'agent
    public function removeProperty(Property $property): static
    {
        if ($this->properties->removeElement($property)) {
            if ($property->getAgent() === $this) {
                $property->setAgent(null);
            }
        }

        return $this;
    }

    // Fonction qui retourne l'email
    public function getEmail(): ?string
    {
        return $this->email;
    }

    // Fonction qui définit l'email
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    // Fonction qui retourne l'email pour la connexion
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // Fonction qui retourne le nom complet
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    // Fonction qui définit le nom complet
    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    // Fonction qui retourne le nom de l'agence
    public function getAgencyName(): ?string
    {
        return $this->agencyName;
    }

    // Fonction qui définit le nom de l'agence
    public function setAgencyName(?string $agencyName): static
    {
        $this->agencyName = $agencyName;

        return $this;
    }

    // Fonction qui retourne Password
    public function getPassword(): ?string
    {
        return $this->password;
    }

    // Fonction qui définit Password
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /** @return list<string> */
    // Fonction qui retourne le rôle (user ou assistant)s
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!\in_array('ROLE_AGENT', $roles, true)) {
            $roles[] = 'ROLE_AGENT';
        }

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    // Fonction qui définit le rôle du messages
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    // Fonction qui efface les données sensibles temporaires
    public function eraseCredentials(): void
    {
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

    // Fonction qui retourne Reset Token
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    // Fonction qui définit Reset Token
    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    // Fonction qui retourne Reset Token Expires At
    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    // Fonction qui définit Reset Token Expires At
    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }
}
