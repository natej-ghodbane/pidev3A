<?php

namespace App\Entity;

use App\Repository\PartenaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartenaireRepository::class)]
class Partenaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom ne doit pas être vide.")]
    #[Assert\Regex(
        pattern: "/\D/",
        message: "Le nom ne peut pas être composé uniquement de chiffres."
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email ne doit pas être vide.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse ne doit pas être vide.")]
    #[Assert\Regex(
        pattern: "/\D/",
        message: "L'adresse ne peut pas être composée uniquement de chiffres."
    )]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne doit pas être vide.")]
    #[Assert\Regex(
        pattern: "/\D/",
        message: "La description ne peut pas être composée uniquement de chiffres."
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date d'ajout est obligatoire.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date d'ajout ne peut pas être dans le passé.")]
    private ?\DateTimeInterface $date_ajout = null;


    #[Assert\NotNull(message: "La catégorie est obligatoire.")]
    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'Partenaires')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id', nullable: false)]
    private ?Categorie $id_categorie = null;


    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: "Le montant est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le montant doit être positif ou nul.")]
    private ?int $montant = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\File(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png", "image/jpg"],
        mimeTypesMessage: "Veuillez uploader une image valide (JPEG ou PNG)."
    )]
    private ?string $logo = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: "/^\+?[0-9]{8,15}$/",
        message: "Le numéro de téléphone doit être valide."
    )]
    private ?string $num_tel = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
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

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDateAjout(?\DateTimeInterface $date_ajout): static
    {
        $this->date_ajout = $date_ajout;
        return $this;
    }

    public function getIdCategorie(): ?Categorie
    {
        return $this->id_categorie;
    }

    public function setIdCategorie(?Categorie $id_categorie): void
    {
        $this->id_categorie = $id_categorie;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(?int $montant): static
    {
        $this->montant = $montant;
        return $this;
    }


    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->num_tel;
    }

    public function setNumTel(?string $num_tel): static
    {
        $this->num_tel = $num_tel;
        return $this;
    }


}
