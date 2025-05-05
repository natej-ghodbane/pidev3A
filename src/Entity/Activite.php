<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['activites'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'Activites')]
    #[ORM\JoinColumn(name: 'id_destination', referencedColumnName: 'id', nullable: true)]
    #[Assert\NotNull(message: "tu dois choisir une destination")]
    private ?Destination $id_destination ;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'activité ne peut pas être vide.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom de l'activité ne peut pas dépasser {{ limit }} caractères.")]
    #[Groups(['activites'])]
    private ?string $nom_activite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "la date ne peut pas être vide.")]
    #[Assert\Range(
        min: "today",
        minMessage: "La date de l'activité doit être après aujourd'hui.",
        invalidMessage: "La date doit être au format valide."
    )]
    #[Groups(['activites'])]
    private ?\DateTimeInterface $date = null;
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'heure ne peut pas être vide.")]
    #[Groups(['activites'])]
    private ?string $heure = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut de l'activité ne peut pas être vide.")]
    #[Assert\Choice(choices: ['active', 'inactive','completed'], message: "Le statut doit être 'active' ou 'inactive' ou 'Completed' .")]
    #[Groups(['activites'])]
    private ?string $statut = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdDestination(): ?Destination
    {
        return $this->id_destination;
    }

    public function setIdDestination(?Destination $id_destination): void
    {
        $this->id_destination = $id_destination;
    }


    public function getNomActivite(): ?string
    {
        return $this->nom_activite;
    }

    public function setNomActivite(?string $nom_activite): static
    {
        $this->nom_activite = $nom_activite;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getHeure(): ?string
    {
        return $this->heure;
    }

    public function setHeure(?string $heure): static
    {
        $this->heure = $heure;

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
}
