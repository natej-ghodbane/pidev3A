<?php

namespace App\Entity;

use App\Repository\RecompenseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecompenseRepository::class)]
class Recompense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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
    #[Assert\NotNull(message: "Le coût en points est obligatoire.")]
    #[Assert\Positive(message: "Le coût en points doit être un nombre positif.")]
    private ?int $cout_en_points = null;



    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La disponibilité est obligatoire.")]
    private ?string $disponibilite = null;


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

    public function getCoutEnPoints(): ?int
    {
        return $this->cout_en_points;
    }

    public function setCoutEnPoints(?int $cout_en_points): static
    {
        $this->cout_en_points = $cout_en_points;
        return $this;
    }

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?string $disponibilite): static
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }
}
