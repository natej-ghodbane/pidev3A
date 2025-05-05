<?php

namespace App\Repository;

use App\Entity\HistoriqueAbonnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueAbonnement>
 */
class HistoriqueAbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueAbonnement::class);
    }

    // Tu peux ajouter ici des fonctions personnalisées plus tard
}
