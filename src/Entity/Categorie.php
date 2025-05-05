<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['categories'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire.")]
    #[Groups(['categories'])]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit comporter au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^(?!\d+$).*$/",
        message: "Le nom ne peut pas être composé uniquement de chiffres."
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Groups(['categories'])]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: "La description doit comporter au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^(?!\d+$).*$/",
        message: "La description ne peut pas être uniquement composée de chiffres."
    )]
    private ?string $description = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['categories'])]
    private ?string $logo = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Le nombre de partenaires est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le nombre de partenaires doit être positif ou zéro.")]
    #[Groups(['categories'])]
    private ?int $nbr_partenaire = null;

    #[ORM\Column(type: 'integer')]
    private $views = 0;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getNbrPartenaire(): ?int
    {
        return $this->nbr_partenaire;
    }

    public function setNbrPartenaire(?int $nbr_partenaire): static
    {
        $this->nbr_partenaire = $nbr_partenaire;
        return $this;
    }

    public function getViews(): int
    {
        return $this->views ?? 0; // 👈 si $this->views est null, retourne 0
    }

// + un setter
    public function setViews(int $views): self
    {
        $this->views = $views;
        return $this;
    }

// + une fonction pour incrémenter facilement
    public function incrementViews(): self
    {
        $this->views++;
        return $this;
    }

}