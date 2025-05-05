<?php

namespace App\Controller;

use App\Entity\Recompense;
use App\Form\RecompenseType;
use App\Repository\RecompenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recompense')]
final class RecompenseController extends AbstractController
{
    #[Route(name: 'app_recompense_index', methods: ['GET'])]
    public function index(RecompenseRepository $recompenseRepository): Response
    {
        return $this->render('recompense/index.html.twig', [
            'recompenses' => $recompenseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_recompense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recompense = new Recompense();
        $form = $this->createForm(RecompenseType::class, $recompense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recompense);
            $entityManager->flush();

            return $this->redirectToRoute('app_recompense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recompense/new.html.twig', [
            'recompense' => $recompense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recompense_show', methods: ['GET'])]
    public function show(Recompense $recompense): Response
    {
        return $this->render('recompense/show.html.twig', [
            'recompense' => $recompense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recompense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recompense $recompense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecompenseType::class, $recompense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_recompense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recompense/edit.html.twig', [
            'recompense' => $recompense,
            'form' => $form,
        ]);
    }

    #[Route('/recompense/delete/{id}', name: 'app_recompense_delete', methods: ['GET'])]
    public function delete(int $id, RecompenseRepository $recompenseRepository, ManagerRegistry $managerRegistry): Response
    {
        // Récupérer l'EntityManager
        $em = $managerRegistry->getManager();

        // Trouver la récompense par son ID
        $recompense = $recompenseRepository->find($id);

        // Si la récompense n'existe pas, afficher une erreur
        if (!$recompense) {
            throw $this->createNotFoundException('Récompense non trouvée');
        }

        // Supprimer la récompense
        $em->remove($recompense);
        $em->flush();

        // Rediriger vers la liste des récompenses après la suppression
        return $this->redirectToRoute('app_recompense_index');
    }

}
