<?php

namespace App\Entity;

use App\Repository\DestinationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DestinationRepository::class)]
class Destination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Nom de la destination ne peut pas être vide.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom de la destination ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $nom_destination = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $image_destination = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La latitude ne peut pas être nulle.")]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "La latitude doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $latitude = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La longitude ne peut pas être nulle.")]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "La longitude doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $longitude = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La température ne peut pas être nulle.")]
    #[Assert\Range(
        min: -100,
        max: 100,
        notInRangeMessage: "La température doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $temperature = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le taux ne peut pas être nul.")]
    #[Assert\Range(
        min: 0,
        max: 5,
        notInRangeMessage: "Le taux doit être compris entre {{ min }} et {{ max }}."
    )]
    private ?float $rate = null;
    private ?array $weather = null;

    public function setWeather(?array $weather): void
    {
        $this->weather = $weather;
    }

    public function getWeather(): ?array
    {
        return $this->weather;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomDestination(): ?string
    {
        return $this->nom_destination;
    }

    public function setNomDestination(?string $nom_destination): static
    {
        $this->nom_destination = $nom_destination;

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

    public function getImageDestination(): ?string
    {
        return $this->image_destination;
    }

    public function setImageDestination(string $image_destination): static
    {
        $this->image_destination = $image_destination;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(?float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }
}
