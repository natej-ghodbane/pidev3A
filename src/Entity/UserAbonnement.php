<?php

namespace App\Entity;

use App\Repository\UserAbonnementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAbonnementRepository::class)]
#[ORM\Table(name: 'user_abonnement')]
class UserAbonnement
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id_user = null;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $id_abonnement = null;

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): static
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getIdAbonnement(): ?int
    {
        return $this->id_abonnement;
    }

    public function setIdAbonnement(int $id_abonnement): static
    {
        $this->id_abonnement = $id_abonnement;
        return $this;
    }
}
