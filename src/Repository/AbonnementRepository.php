<?php

namespace App\Repository;

use App\Entity\Abonnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    public function searchByFilters(string $searchTerm, string $statut, string $dateField)
    {
        $queryBuilder = $this->createQueryBuilder('a');

        if ($searchTerm) {
            $queryBuilder->andWhere('a.nom LIKE :searchTerm OR a.prenom LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($statut) {
            $queryBuilder->andWhere('a.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($dateField) {
            $queryBuilder->andWhere('a.date_Souscription = :dateField')
                ->setParameter('dateField', $dateField);
        }

        return $queryBuilder->getQuery()->getResult();
    }


    public function searchByField(string $field, string $term)
    {
        $qb = $this->createQueryBuilder('a');

        // Add conditions based on the field
        if ($field === 'statut') {
            $qb->andWhere('a.statut LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        } elseif ($field === 'date_Souscription') {
            $qb->andWhere('a.date_Souscription LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        } elseif ($field === 'date_Expiration') {
            $qb->andWhere('a.date_Expiration LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->getQuery()->getResult();
    }
    //    /**
    //     * @return Abonnement[] Returns an array of Abonnement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Abonnement
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // src/Repository/AbonnementRepository.php

    public function findByPackName(string $name): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.pack', 'p')
            ->where('p.nom_pack LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }

}
