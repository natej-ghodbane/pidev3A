<?php

namespace App\Entity;

use App\Repository\MissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
class Mission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['missions'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Recompense::class, inversedBy: 'missions')]
    #[ORM\JoinColumn(name: 'id_recompense', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "Veuillez sélectionner une récompense.")]
    private ?Recompense $id_recompense = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Groups(['missions'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le nombre de points de récompense est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de points de récompense doit être un nombre positif.")]
    #[Groups(['missions'])]
    private ?int $points_recompense = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Groups(['missions'])]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPointsRecompense(): ?int
    {
        return $this->points_recompense;
    }

    public function setPointsRecompense(?int $points_recompense): static
    {
        $this->points_recompense = $points_recompense;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getIdRecompense(): ?Recompense
    {
        return $this->id_recompense;
    }

    public function setIdRecompense(?Recompense $id_recompense): static
    {
        $this->id_recompense = $id_recompense;

        return $this;
    }
}
