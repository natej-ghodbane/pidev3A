<?php

namespace App\Entity;

use App\Repository\UserMissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserMissionRepository::class)]
class UserMission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Mission::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mission $mission = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $validatedAt;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: "Les points gagnés doivent être positifs.")]
    private int $pointsGagnes = 0;

    public function __construct()
    {
        $this->validatedAt = new \DateTime();
    }

    // ----------- GETTERS & SETTERS -----------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): self
    {
        $this->mission = $mission;
        return $this;
    }

    public function getValidatedAt(): \DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(\DateTimeInterface $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getPointsGagnes(): int
    {
        return $this->pointsGagnes;
    }

    public function setPointsGagnes(int $points): self
    {
        $this->pointsGagnes = $points;
        return $this;
    }
}
