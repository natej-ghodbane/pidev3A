<?php

namespace App\Entity;

use App\Repository\HistoriqueAbonnementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueAbonnementRepository::class)]
class HistoriqueAbonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $action = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateAction = null;

    #[ORM\Column(type: 'integer')]
    private ?int $abonnementId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    // Getters & Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getDateAction(): ?\DateTimeInterface
    {
        return $this->dateAction;
    }

    public function setDateAction(\DateTimeInterface $dateAction): static
    {
        $this->dateAction = $dateAction;
        return $this;
    }

    public function getAbonnementId(): ?int
    {
        return $this->abonnementId;
    }

    public function setAbonnementId(int $abonnementId): static
    {
        $this->abonnementId = $abonnementId;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }
}
