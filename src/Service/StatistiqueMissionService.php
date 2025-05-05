<?php

namespace App\Service;

use App\Entity\Mission;
use App\Entity\UserMission;
use Doctrine\ORM\EntityManagerInterface;

class StatistiqueMissionService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function collectMissionValidationStats(): array
    {
        $totalUsers = $this->entityManager->getRepository(UserMission::class)
            ->createQueryBuilder('um')
            ->select('COUNT(DISTINCT um.user)')
            ->getQuery()
            ->getSingleScalarResult();

        $missions = $this->entityManager->getRepository(UserMission::class)
            ->createQueryBuilder('um')
            ->select('m.description AS missionName', 'COUNT(um.id) AS totalValidations')
            ->join('um.mission', 'm')
            ->groupBy('m.id')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($missions as $mission) {
            $percentage = $totalUsers > 0 ? ($mission['totalValidations'] / $totalUsers) * 100 : 0;
            $result[] = [
                'missionName' => $mission['missionName'],
                'percentage' => round($percentage, 2),
            ];
        }

        return $result;
    }


    public function collectValidationsPerDay(): array
    {
        return $this->entityManager->getRepository(UserMission::class)
            ->createQueryBuilder('um')
            ->select('SUBSTRING(um.validatedAt, 1, 10) AS day', 'COUNT(um.id) AS total')
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function collectTopUsers(): array
    {
        $users = $this->entityManager->getRepository(UserMission::class)
            ->createQueryBuilder('um')
            ->select('CONCAT(u.prenom, \' \', u.nom) AS username', 'COUNT(um.id) AS validations')
            ->join('um.user', 'u')
            ->groupBy('u.id')
            ->orderBy('validations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $users;
    }
}
