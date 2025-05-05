<?php

namespace App\Repository;

use App\Entity\UserMission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserMission>
 *
 * @method UserMission|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserMission|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserMission[]    findAll()
 * @method UserMission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserMissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMission::class);
    }

    // 📚 Tu peux ajouter ici des méthodes personnalisées si tu veux plus tard

    /**
     * Récupérer la somme totale des points gagnés par un utilisateur
     */
    public function getTotalPointsByUser($user): int
    {
        $qb = $this->createQueryBuilder('um')
            ->select('SUM(um.pointsGagnes)')
            ->where('um.user = :user')
            ->setParameter('user', $user);

        $total = $qb->getQuery()->getSingleScalarResult();

        return (int) $total;
    }

    public function countUsersForMission(int $missionId): int
    {
        return $this->createQueryBuilder('um')
            ->select('COUNT(um.id)')
            ->where('um.mission = :missionId')
            ->setParameter('missionId', $missionId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
