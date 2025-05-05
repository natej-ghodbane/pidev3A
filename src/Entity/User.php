<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée par un autre compte')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(min: 2, max: 50, 
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(min: 2, max: 50,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(length: 255, name: 'mot_de_passe')]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo_profil = null;

    #[ORM\Column(length: 255)]
    private ?string $type_user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo_carte_f1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo_carte_f2 = null;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'last_login')]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'created_at')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'deleted_at')]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    public function __construct()
    {
        $this->isActive = true;
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTypeUser(): ?string
    {
        return $this->type_user;
    }

    public function setTypeUser(string $type_user): static
    {
        $this->type_user = $type_user;
        return $this;
    }

    public function getPhotoProfile(): ?string
    {
        return $this->photo_profil;
    }

    public function setPhotoProfile(?string $photo_profil): static
    {
        $this->photo_profil = $photo_profil;
        return $this;
    }

    public function getPhotoCarteF1(): ?string
    {
        return $this->photo_carte_f1;
    }

    public function setPhotoCarteF1(?string $photo_carte_f1): static
    {
        $this->photo_carte_f1 = $photo_carte_f1;
        return $this;
    }

    public function getPhotoCarteF2(): ?string
    {
        return $this->photo_carte_f2;
    }

    public function setPhotoCarteF2(?string $photo_carte_f2): static
    {
        $this->photo_carte_f2 = $photo_carte_f2;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    public function getRoles(): array
    {
        // Convert type_user to proper Symfony role
        $role = match($this->type_user) {
            'Touriste' => 'ROLE_USER',
            'Admin' => 'ROLE_ADMIN',
            default => 'ROLE_USER'
        };
        
        return [$role];
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isSoftDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }
}
