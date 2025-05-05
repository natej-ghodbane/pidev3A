<?php

namespace App\Entity;

use App\Repository\AbonnementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['abonnements'])]
    private ?int $id_abonnement = null;

    #[ORM\Column(nullable: true)]
    private ?int $id_utilisateur = 0;

    #[ORM\ManyToOne(targetEntity: Pack::class)]
    #[ORM\JoinColumn(name: "id_pack", referencedColumnName: "id_pack", nullable: false)]
    #[Groups(['abonnements'])]
    private ?Pack $pack = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\GreaterThanOrEqual(value: "today", message: "La date de souscription ne peut pas être dans le passé.")]
    #[Groups(['abonnements'])]
    private ?\DateTimeInterface $date_Souscription = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\GreaterThan(propertyPath: "date_Souscription", message: "La date d'expiration ne peut pas être avant la date de souscription.")]
    #[Groups(['abonnements'])]
    private ?\DateTimeInterface $date_Expiration = null;

    #[ORM\Column(type: "string", length: 20)]
    #[Groups(['abonnements'])]
    private ?string $statut = null;

    public function getIdAbonnement(): ?int
    {
        return $this->id_abonnement;
    }

    public function setIdAbonnement(int $id_abonnement): static
    {
        $this->id_abonnement = $id_abonnement;

        return $this;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(?int $id_utilisateur): static
    {
        $this->id_utilisateur = $id_utilisateur;

        return $this;
    }

    public function getPack(): ?Pack
{
    return $this->pack;
}

public function setPack(?Pack $pack): static
{
    $this->pack = $pack;

    return $this;
}

public function getIdPack(): ?int
{
    return $this->pack ? $this->pack-> getIdPack() : null;
}



    public function getDateSouscription(): ?\DateTimeInterface
    {
        return $this->date_Souscription;
    }

    public function setDateSouscription(?\DateTimeInterface $date_Souscription): static
    {
        $this->date_Souscription = $date_Souscription;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->date_Expiration;
    }

    public function setDateExpiration(?\DateTimeInterface $date_Expiration): static
    {
        $this->date_Expiration = $date_Expiration;

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
