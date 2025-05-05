<?php

namespace App\Controller;

use App\Repository\ReclamationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reclamation')]
class ReclamationCalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_reclamation_calendar', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('reclamation/calendar.html.twig');
    }

    #[Route('/calendar-events', name: 'app_reclamation_calendar_events', methods: ['GET'])]
    public function getCalendarEvents(ReclamationRepository $reclamationRepository): JsonResponse
    {
        $reclamations = $reclamationRepository->findAll();
        $events = [];

        foreach ($reclamations as $reclamation) {
            // Format the date properly for FullCalendar
            $date = $reclamation->getDateRec();
            
            $events[] = [
                'id' => $reclamation->getIdRec(),
                'title' => substr($reclamation->getDescriptionRec(), 0, 30) . '...',
                'start' => $date->format('Y-m-d\TH:i:s'),
                'allDay' => true,
                'backgroundColor' => $this->getEventColor($reclamation->getEtatRec()),
                'borderColor' => $this->getEventColor($reclamation->getEtatRec()),
                'textColor' => '#ffffff',
                'url' => $this->generateUrl('app_reclamation_show', ['id_rec' => $reclamation->getIdRec()]),
                'extendedProps' => [
                    'description' => $reclamation->getDescriptionRec(),
                    'type' => $reclamation->getTypeRec(),
                    'etat' => $reclamation->getEtatRec()
                ]
            ];
        }

        return new JsonResponse($events);
    }

    #[Route('/calendar-events-test', name: 'app_reclamation_calendar_events_test', methods: ['GET'])]
    public function testCalendarEvents(ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
        $events = [];

        foreach ($reclamations as $reclamation) {
            $events[] = [
                'id' => $reclamation->getIdRec(),
                'title' => substr($reclamation->getDescriptionRec(), 0, 30) . '...',
                'start' => $reclamation->getDateRec()->format('Y-m-d'),
                'type' => $reclamation->getTypeRec(),
                'backgroundColor' => $this->getEventColor($reclamation->getEtatRec()),
                'borderColor' => $this->getEventColor($reclamation->getEtatRec()),
                'url' => $this->generateUrl('app_reclamation_show', ['id_rec' => $reclamation->getIdRec()]),
                'extendedProps' => [
                    'etat' => $reclamation->getEtatRec(),
                    'description' => $reclamation->getDescriptionRec()
                ]
            ];
        }

        return $this->render('reclamation/calendar_test.html.twig', [
            'events' => json_encode($events)
        ]);
    }

    #[Route('/calendar-simple', name: 'app_reclamation_calendar_simple', methods: ['GET'])]
    public function simpleCalendar(): Response
    {
        return $this->render('reclamation/calendar_simple.html.twig');
    }

    #[Route('/calendar-events-debug', name: 'app_reclamation_calendar_events_debug', methods: ['GET'])]
    public function debugCalendarEvents(ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
        $events = [];

        foreach ($reclamations as $reclamation) {
            $events[] = [
                'id' => $reclamation->getIdRec(),
                'title' => substr($reclamation->getDescriptionRec(), 0, 30) . '...',
                'start' => $reclamation->getDateRec()->format('Y-m-d\TH:i:s'),
                'allDay' => true,
                'backgroundColor' => $this->getEventColor($reclamation->getEtatRec()),
                'borderColor' => $this->getEventColor($reclamation->getEtatRec()),
                'textColor' => '#ffffff',
                'url' => $this->generateUrl('app_reclamation_show', ['id_rec' => $reclamation->getIdRec()]),
                'description' => $reclamation->getDescriptionRec(),
                'type' => $reclamation->getTypeRec(),
                'etat' => $reclamation->getEtatRec()
            ];
        }

        return $this->render('reclamation/calendar_debug.html.twig', [
            'events' => json_encode($events, JSON_PRETTY_PRINT),
            'reclamations' => $reclamations
        ]);
    }

    #[Route('/calendar-minimal', name: 'app_reclamation_calendar_minimal', methods: ['GET'])]
    public function minimalCalendar(): Response
    {
        return $this->render('reclamation/calendar_minimal.html.twig');
    }

    private function getEventColor(string $etat): string
    {
        return match ($etat) {
            'En cours' => '#ffc107',    // warning yellow
            'Résolue' => '#198754',     // success green
            'Rejetée' => '#dc3545',     // danger red
            default => '#6c757d'         // secondary gray
        };
    }
} 