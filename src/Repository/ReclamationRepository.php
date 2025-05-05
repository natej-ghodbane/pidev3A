<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

//    /**
//     * @return Reclamation[] Returns an array of Reclamation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reclamation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function findByDate(\DateTimeInterface $date): array
{
    $start = (clone $date)->setTime(0, 0, 0);
    $end = (clone $date)->setTime(23, 59, 59);

    return $this->createQueryBuilder('r')
        ->where('r.date_rec BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('r.date_rec', 'ASC')
        ->getQuery()
        ->getResult();
}
public function findReclamationByDescriptionRec(string $description): array
{
    return $this->createQueryBuilder('r')
        ->where('r.descriptionRec LIKE :description')
        ->setParameter('description', '%' . $description . '%')
        ->getQuery()
        ->getResult();
}
}
