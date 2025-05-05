<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Reclamation;

#[Route('/reponse')]
final class ReponseController extends AbstractController{
    #[Route('/back', name: 'app_reponse_back', methods: ['GET'])]
    public function back(Request $request, ReponseRepository $reponseRepository, ReclamationRepository $reclamationRepository): Response
    {
        // Récupérer les paramètres de tri et de filtrage
        $sort = $request->query->get('sort', 'dateRep');
        $direction = $request->query->get('direction', 'DESC');
        $searchQuery = $request->query->get('search');
        $reclamationId = $request->query->get('reclamation');

        // Créer le QueryBuilder
        $queryBuilder = $reponseRepository->createQueryBuilder('r')
            ->leftJoin('r.reclamation', 'rec')
            ->addSelect('rec');

        // Appliquer les filtres
        if ($searchQuery) {
            $queryBuilder->andWhere('(r.contenu LIKE :search OR rec.descriptionRec LIKE :search)')
                        ->setParameter('search', '%' . $searchQuery . '%');
        }
        if ($reclamationId) {
            $queryBuilder->andWhere('rec.idRec = :reclamationId')
                        ->setParameter('reclamationId', $reclamationId);
        }

        // Appliquer le tri
        if ($sort === 'reclamation') {
            $queryBuilder->orderBy('rec.descriptionRec', $direction);
        } else {
            $queryBuilder->orderBy('r.' . $sort, $direction);
        }

        // Exécuter la requête
        $reponses = $queryBuilder->getQuery()->getResult();

        // Récupérer toutes les réclamations pour le filtre
        $reclamations = $reclamationRepository->findAll();

        // Calculer les statistiques
        $stats = [
            'total' => count($reponses),
            'parMois' => [],
            'moyenneReponseParReclamation' => 0,
            'tempsReponseParEtat' => [
                'En cours' => 0,
                'Résolue' => 0,
                'Rejetée' => 0
            ],
            'moisActuel' => 0
        ];

        $moisActuel = new \DateTime();
        $moisActuel->modify('first day of this month');

        $reclamationsAvecReponses = [];
        foreach ($reponses as $reponse) {
            // Compter par mois
            $moisReponse = $reponse->getDateRep()->format('Y-m');
            if (!isset($stats['parMois'][$moisReponse])) {
                $stats['parMois'][$moisReponse] = 0;
            }
            $stats['parMois'][$moisReponse]++;

            // Compter pour le mois actuel
            if ($reponse->getDateRep() >= $moisActuel) {
                $stats['moisActuel']++;
            }

            // Calculer le temps de réponse moyen par état
            $reclamation = $reponse->getReclamation();
            $tempsReponse = $reponse->getDateRep()->getTimestamp() - $reclamation->getDateRec()->getTimestamp();
            $stats['tempsReponseParEtat'][$reclamation->getEtatRec()] += $tempsReponse;

            // Compter les réponses par réclamation
            $idRec = $reclamation->getIdRec();
            if (!isset($reclamationsAvecReponses[$idRec])) {
                $reclamationsAvecReponses[$idRec] = 0;
            }
            $reclamationsAvecReponses[$idRec]++;
        }

        // Calculer les moyennes
        foreach ($stats['tempsReponseParEtat'] as $etat => $temps) {
            $count = array_reduce($reponses, function($carry, $reponse) use ($etat) {
                return $carry + ($reponse->getReclamation()->getEtatRec() === $etat ? 1 : 0);
            }, 0);
            if ($count > 0) {
                $stats['tempsReponseParEtat'][$etat] = round($temps / $count / 3600, 1); // Convertir en heures
            }
        }

        if (count($reclamationsAvecReponses) > 0) {
            $stats['moyenneReponseParReclamation'] = round(array_sum($reclamationsAvecReponses) / count($reclamationsAvecReponses), 1);
        }

        // Trier les statistiques par mois
        krsort($stats['parMois']);
        $stats['parMois'] = array_slice($stats['parMois'], 0, 6, true);

        return $this->render('reponse/back.html.twig', [
            'reponses' => $reponses,
            'reclamations' => $reclamations,
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'searchQuery' => $searchQuery,
            'currentReclamation' => $reclamationId,
            'stats' => $stats
        ]);
    }

    #[Route('/new/{id}', name: 'app_reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Reclamation $reclamation): Response
    {
        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);
        $reponse->setDateRep(new \DateTimeImmutable());
        
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reponse);
            
            // Récupérer le nouvel état choisi dans le formulaire
            $nouvelEtat = $form->get('nouvelEtat')->getData();
            $reclamation->setEtatRec($nouvelEtat);
            
            $entityManager->flush();

            $this->addFlash('success', 'La réponse a été ajoutée avec succès.');
            return $this->redirectToRoute('app_reponse_back');
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(ReponseRepository $reponseRepository, int $id): Response
    {
        $reponse = $reponseRepository->find($id);
        
        if (!$reponse) {
            throw $this->createNotFoundException('La réponse n\'existe pas');
        }

        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reponse_back');
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponse_back');
    }
}
