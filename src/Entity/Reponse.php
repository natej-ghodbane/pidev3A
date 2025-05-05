<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_rep')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(name: 'id_rec', referencedColumnName: 'id_rec')]
    #[Assert\NotNull(message: "La réclamation ne peut pas être vide")]
    private ?Reclamation $reclamation = null;

    #[ORM\Column(name: 'date_rep')]
    #[Assert\NotNull(message: "La date ne peut pas être vide")]
    private ?\DateTimeImmutable $dateRep = null;

    #[ORM\Column(name: 'contenu_rep', length: 255)]
    #[Assert\NotBlank(message: "Le contenu ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le contenu ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $contenu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    public function getDateRep(): ?\DateTimeImmutable
    {
        return $this->dateRep;
    }

    public function setDateRep(\DateTimeImmutable $dateRep): static
    {
        $this->dateRep = $dateRep;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }
}
