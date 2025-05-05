<?php

namespace App\Entity;

use App\Repository\UserPartenaireRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: UserPartenaireRepository::class)]
class UserPartenaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $partenaireId = null;

    #[ORM\Column(length: 255)]
    private ?string $commentaire = null;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPartenaireId(): ?int
    {
        return $this->partenaireId;
    }

    public function setPartenaireId(int $partenaireId): static
    {
        $this->partenaireId = $partenaireId;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }
}
