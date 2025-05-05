<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_rec')]
    #[Groups(['reclamations'])]
    private ?int $idRec = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Groups(['reclamations'])]
    private ?string $descriptionRec = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "La date ne peut pas être vide")]
    private ?\DateTimeImmutable $dateRec = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type ne peut pas être vide")]
    #[Assert\Choice(
        choices: ['Problème technique', 'Problème de paiement', 'Problème de réservation', 'Autre'],
        message: "Le type de réclamation n'est pas valide"
    )]
    private ?string $typeRec = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'état ne peut pas être vide")]
    #[Assert\Choice(
        choices: ['En cours', 'Résolue', 'Rejetée'],
        message: "L'état de la réclamation n'est pas valide"
    )]
    private ?string $etatRec = null;

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: 'reclamation')]
    private Collection $reponses;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)] // User is required
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }


    public function __construct()
    {
        $this->dateRec = new \DateTimeImmutable();
        $this->etatRec = 'En cours';
        $this->reponses = new ArrayCollection();
    }

    public function getIdRec(): ?int
    {
        return $this->idRec;
    }

    public function getDescriptionRec(): ?string
    {
        return $this->descriptionRec;
    }

    public function setDescriptionRec(string $descriptionRec): static
    {
        $this->descriptionRec = $descriptionRec;
        return $this;
    }

    public function getDateRec(): ?\DateTimeImmutable
    {
        return $this->dateRec;
    }

    public function setDateRec(\DateTimeImmutable $dateRec): static
    {
        $this->dateRec = $dateRec;
        return $this;
    }

    public function getTypeRec(): ?string
    {
        return $this->typeRec;
    }

    public function setTypeRec(string $typeRec): static
    {
        $this->typeRec = $typeRec;
        return $this;
    }

    public function getEtatRec(): ?string
    {
        return $this->etatRec;
    }

    public function setEtatRec(string $etatRec): static
    {
        $this->etatRec = $etatRec;
        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setReclamation($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getReclamation() === $this) {
                $reponse->setReclamation(null);
            }
        }
        return $this;
    }
}
