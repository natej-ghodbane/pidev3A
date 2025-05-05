<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\CategorieRepository;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Service\TestTwilioCommand;

#[Route('/reclamation')]
final class ReclamationController extends AbstractController
{
    private $twilioService;

    public function __construct(TestTwilioCommand $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        // ✅ Only fetch the reclamations for the logged-in user
        $reclamations = $reclamationRepository->findBy([
            'user' => $this->getUser()
        ]);

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CategorieRepository $categorieRepository,
        ReclamationRepository $reclamationRepository
    ): Response {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);
        $categories = $categorieRepository->findAll();

        $debugInfo = [
            'sms_status' => 'not_attempted',
            'phone_number' => '+21658664146',
            'message_content' => null,
            'error' => null,
            'message_id' => null,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // ✅ Associate Reclamation with current user
                $reclamation->setUser($this->getUser());

                $entityManager->persist($reclamation);
                $entityManager->flush();

                // Send SMS (optional debug part you had)
                $debugInfo['message_content'] = sprintf(
                    "Une nouvelle réclamation (#%d) a été enregistrée. Veuillez la traiter dans les plus brefs délais.",
                    $reclamation->getIdRec()
                );

                $this->twilioService->sendSms($debugInfo['phone_number'], $debugInfo['message_content']);

                $debugInfo['sms_status'] = 'success';
                $this->addFlash('success', 'Réclamation enregistrée et SMS envoyé.');
            } catch (\Exception $e) {
                $debugInfo['sms_status'] = 'error';
                $debugInfo['error'] = $e->getMessage();
                $this->addFlash('warning', 'Réclamation enregistrée, mais échec d\'envoi du SMS.');
            }

            $request->getSession()->set('sms_debug_info', $debugInfo);
            return $this->redirectToRoute('app_reclamation_new');
        }

        $sessionDebugInfo = $request->getSession()->get('sms_debug_info', []);
        $debugInfo = array_merge([
            'sms_status' => 'not_attempted',
            'phone_number' => null,
            'message_content' => null,
            'error' => null,
            'message_id' => null,
            'timestamp' => null
        ], $sessionDebugInfo);

        $request->getSession()->remove('sms_debug_info');

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            // ✅ Again: fetch only user's reclamations
            'reclamations' => $reclamationRepository->findBy(['user' => $this->getUser()]),
            'categories' => $categories,
            'debug_info' => $debugInfo
        ]);
    }

    #[Route('/back', name: 'reclamationBack', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function showback(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        // ADMIN view: show all reclamations (no filtering by user here)

        $sort = $request->query->get('sort', 'dateRec');
        $direction = $request->query->get('direction', 'DESC');
        $typeFilter = $request->query->get('type');
        $etatFilter = $request->query->get('etat');
        $searchQuery = $request->query->get('search');

        $queryBuilder = $reclamationRepository->createQueryBuilder('r');

        if ($typeFilter) {
            $queryBuilder->andWhere('r.typeRec = :type')
                ->setParameter('type', $typeFilter);
        }
        if ($etatFilter) {
            $queryBuilder->andWhere('r.etatRec = :etat')
                ->setParameter('etat', $etatFilter);
        }
        if ($searchQuery) {
            $queryBuilder->andWhere('(r.descriptionRec LIKE :search OR r.typeRec LIKE :search OR r.etatRec LIKE :search)')
                ->setParameter('search', '%' . $searchQuery . '%');
        }

        $queryBuilder->orderBy('r.' . $sort, $direction);
        $reclamations = $queryBuilder->getQuery()->getResult();

        $types = $reclamationRepository->createQueryBuilder('r')
            ->select('DISTINCT r.typeRec')
            ->getQuery()
            ->getResult();

        $etats = $reclamationRepository->createQueryBuilder('r')
            ->select('DISTINCT r.etatRec')
            ->getQuery()
            ->getResult();

        return $this->render('reclamation/back.html.twig', [
            'reclamations' => $reclamations,
            'types' => array_column($types, 'typeRec'),
            'etats' => array_column($etats, 'etatRec'),
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'currentType' => $typeFilter,
            'currentEtat' => $etatFilter,
            'searchQuery' => $searchQuery,
            'stats' => $this->computeStats($reclamations)
        ]);
    }

    private function computeStats(array $reclamations): array
    {
        $stats = [
            'total' => count($reclamations),
            'enCours' => 0,
            'resolues' => 0,
            'rejetees' => 0,
            'parType' => [],
            'parMois' => []
        ];

        $moisActuel = new \DateTime();
        $moisActuel->modify('first day of this month');
        $stats['moisActuel'] = 0;

        foreach ($reclamations as $reclamation) {
            switch ($reclamation->getEtatRec()) {
                case 'En cours':
                    $stats['enCours']++;
                    break;
                case 'Résolue':
                    $stats['resolues']++;
                    break;
                case 'Rejetée':
                    $stats['rejetees']++;
                    break;
            }

            $type = $reclamation->getTypeRec();
            $stats['parType'][$type] = ($stats['parType'][$type] ?? 0) + 1;

            $moisReclamation = $reclamation->getDateRec()->format('Y-m');
            $stats['parMois'][$moisReclamation] = ($stats['parMois'][$moisReclamation] ?? 0) + 1;

            if ($reclamation->getDateRec() >= $moisActuel) {
                $stats['moisActuel']++;
            }
        }

        krsort($stats['parMois']);
        $stats['parMois'] = array_slice($stats['parMois'], 0, 6, true);

        return $stats;
    }

    #[Route('/back/{id_rec}', name: 'app_reclamation_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(ReclamationRepository $reclamationRepository, string $id_rec): Response
    {
        $reclamation = $reclamationRepository->findOneBy(['idRec' => (int)$id_rec]);

        if (!$reclamation) {
            throw $this->createNotFoundException('La réclamation n\'existe pas');
        }

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/back/{id_rec}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, ReclamationRepository $reclamationRepository, int $id_rec, EntityManagerInterface $entityManager): Response
    {
        $reclamation = $reclamationRepository->findOneBy(['idRec' => $id_rec]);

        if (!$reclamation) {
            throw $this->createNotFoundException('La réclamation n\'existe pas');
        }

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La réclamation a été modifiée avec succès.');
            return $this->redirectToRoute('reclamationBack');
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/back/{id_rec}', name: 'app_reclamation_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, ReclamationRepository $reclamationRepository, int $id_rec, EntityManagerInterface $entityManager): Response
    {
        $reclamation = $reclamationRepository->findOneBy(['idRec' => $id_rec]);

        if (!$reclamation) {
            throw $this->createNotFoundException('La réclamation n\'existe pas');
        }

        if ($this->isCsrfTokenValid('delete'.$reclamation->getIdRec(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'La réclamation a été supprimée avec succès.');
        }

        return $this->redirectToRoute('reclamationBack');
    }

    #[Route('/back/calendar', name: 'app_reclamation_calendar', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function calendar(ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
        $events = [];

        foreach ($reclamations as $reclamation) {
            $events[] = [
                'title' => $reclamation->getTypeRec(),
                'start' => $reclamation->getDateRec()->format('Y-m-d H:i:s'),
                'description' => $reclamation->getDescriptionRec(),
                'type' => $reclamation->getTypeRec(),
                'etat' => $reclamation->getEtatRec(),
                'backgroundColor' => $this->getEventColor($reclamation->getEtatRec()),
                'borderColor' => $this->getEventColor($reclamation->getEtatRec()),
                'textColor' => '#ffffff'
            ];
        }

        return $this->render('reclamation/calendar_view.html.twig', [
            'reclamations' => $events
        ]);
    }

    private function getEventColor(string $etat): string
    {
        return match($etat) {
            'En cours' => '#ffc107',
            'Résolue' => '#28a745',
            'Rejetée' => '#dc3545',
            default => '#6c757d'
        };
    }

    #[Route('/reclamations/by-date', name: 'reclamation_by_date', methods: ['GET'])]
    public function reclamationsByDate(Request $request, ReclamationRepository $repo): JsonResponse
    {
        $dateString = $request->query->get('datee');

        if (!$dateString) {
            return new JsonResponse(['error' => 'Missing date'], 400);
        }

        $date = \DateTime::createFromFormat('Y-m-d', $dateString);

        if (!$date) {
            return new JsonResponse(['error' => 'Invalid date'], 400);
        }

        $reclamations = $repo->findByDate($date);

        $data = array_map(fn($rec) => [
            'id' => $rec->getIdRec(),
            'description' => $rec->getDescriptionRec(),
            'type' => $rec->getTypeRec(),
            'date' => $rec->getDateRec()->format('Y-m-d H:i:s'),
        ], $reclamations);

        return new JsonResponse($data);
    }

    #[Route('/search', name: 'reclamation_search')]
    public function search(Request $request, NormalizerInterface $normalizer, ReclamationRepository $reclamationRepository): JsonResponse
    {
        $searchValue = $request->get('searchValue');

        $reclamations = $reclamationRepository->findReclamationByDescriptionRec($searchValue);

        $jsonContent = $normalizer->normalize($reclamations, 'json', ['groups' => 'reclamations']);

        return new JsonResponse($jsonContent);
    }
}
