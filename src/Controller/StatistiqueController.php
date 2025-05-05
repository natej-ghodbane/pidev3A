<?php

namespace App\Controller;

use App\Service\StatistiqueMissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiqueController extends AbstractController
{
    private $statistiqueMissionService;

    public function __construct(StatistiqueMissionService $statistiqueMissionService)
    {
        $this->statistiqueMissionService = $statistiqueMissionService;
    }

    #[Route('/admin/statistiques', name: 'admin_statistiques')]
    public function dashboard(): Response
    {
        $missionStats = $this->statistiqueMissionService->collectMissionValidationStats();
        $validationsPerDay = $this->statistiqueMissionService->collectValidationsPerDay();
        $topUsers = $this->statistiqueMissionService->collectTopUsers();

        return $this->render('mission/dashboard.html.twig', [
            'missionStats' => $missionStats,
            'validationsPerDay' => $validationsPerDay,
            'topUsers' => $topUsers,
        ]);
    }
}
