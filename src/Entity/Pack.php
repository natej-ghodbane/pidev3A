<?php

namespace App\Entity;

use App\Repository\PackRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PackRepository::class)]
class Pack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['abonnements'])]
    private ?int $id_pack = null;
    
    #[ORM\Column(type: 'integer')]
    private ?int $id_utilisateur = 0; 

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom du pack est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^[A-Z].*/",
        message: "Le nom du pack doit commencer par une majuscule."
    )]
    #[Groups(['abonnements'])]
    private ?string $nom_pack = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        max: 20,
        maxMessage: "La description ne doit pas dépasser 20 caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Le prix ne peut pas être négatif.")]
    private ?float $prix = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "La durée est obligatoire.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "La durée ne peut pas être négative.")]
    private ?int $duree = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Les avantages sont obligatoires.")]
    #[Assert\Choice(
        choices: ["Aventure", "Nature", "Gastronomie"],
        message: "Les avantages doivent être 'Aventure', 'Nature' ou 'Gastronomie'."
    )]
    private ?string $avantages = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ["Expiré", "Actif"],
        message: "Le statut doit être 'Expiré' ou 'Actif'."
    )]
    private ?string $statut = null;

    public function getIdPack(): ?int
    {
        return $this->id_pack;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(int $id_utilisateur): static
    {
        $this->id_utilisateur = $id_utilisateur;
        return $this;
    }

    public function getNomPack(): ?string
    {
        return $this->nom_pack;
    }

    public function setNomPack(?string $nom_pack): static
    {
        $this->nom_pack = $nom_pack;
        return $this;
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getAvantages(): ?string
    {
        return $this->avantages;
    }

    public function setAvantages(?string $avantages): static
    {
        $this->avantages = $avantages;
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
    public function getPrixInUSD(): ?float
{
    return $this->pack ? $this->pack->getPrixInUSD() : null;
}

}
