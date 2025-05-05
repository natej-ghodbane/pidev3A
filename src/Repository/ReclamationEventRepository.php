<?php

namespace App\Repository;

use App\Entity\ReclamationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReclamationEvent>
 *
 * @method ReclamationEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReclamationEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReclamationEvent[]    findAll()
 * @method ReclamationEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReclamationEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReclamationEvent::class);
    }

    public function findEventsByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.start >= :start')
            ->andWhere('e.start <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(ReclamationEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReclamationEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 