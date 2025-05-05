<?php

namespace App\Repository;

use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

    public function getAllPackIds(): array
{
    return $this->createQueryBuilder('p')
        ->select('p.id_pack') 
        ->getQuery()
        ->getSingleColumnResult(); 
}

public function getPriceById(int $id): ?float
{
    $pack = $this->find($id);
    return $pack ? $pack->getPrix() : null;
}
public function getNomById(int $id): ?string
{
    $pack = $this->find($id);
    return $pack ? $pack->getNomPack() : null;
}
public function getDescriptionById(int $id): ?string
{
    $pack = $this->find($id);
    return $pack ? $pack->getDescription() : null;
}
public function getAvantagesById(int $id): ?string
{
    $pack = $this->find($id);
    return $pack ? $pack->getAvantages() : null;
}
public function getDureeById(int $id): ?string
{
    $pack = $this->find($id);
    return $pack ? $pack->getDuree() : null;
}

public function getPrixInUSD(): ?float
{
    $exchangeRate = 3.00;
    return $this->prix / $exchangeRate;
}

    //    /**
    //     * @return Pack[] Returns an array of Pack objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Pack
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
