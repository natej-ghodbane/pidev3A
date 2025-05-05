<?php

namespace App\Controller;

use App\Entity\Pack;
use App\Form\PackType;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class PackController extends AbstractController
{
    #[Route('/front', name: 'app_pack')]
    public function index(PackRepository $packRepository): Response
    {
        return $this->render('base.html.twig', [
            'packs' => $packRepository->findAll(),
        ]);
    }
    #[Route('/back', name: 'app_packk')]
    public function indexB(PackRepository $packRepository): Response
    {
        return $this->render('base-back.html.twig', [
            'packs' => $packRepository->findAll(),
        ]);
    }

    #[Route('/packshow', name: 'list_packs', methods: ['GET'])]
    public function listPacks(PackRepository $packRepository): Response
    {
        $packs = $packRepository->findAll();

        return $this->render('pack/showPack.html.twig', [
            'packs' => $packs,
        ]);
    }


    #[Route('/packadd', name: 'add_pack', methods: ['GET', 'POST'])]
    public function add(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $em = $managerRegistry->getManager();
        $pack = new Pack();
        $form = $this->createForm(PackType::class, $pack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($pack);
            $em->flush();
            return $this->redirectToRoute('list_packs');
        }

        return $this->renderForm('pack/addPack.html.twig', [
            'formC' => $form,
        ]);
    }

    #[Route('/packedit/{id_pack}', name: 'edit_pack', methods: ['GET', 'POST'])]
    public function edit(string $id_pack, PackRepository $packRepository, Request $request, ManagerRegistry $managerRegistry): Response
    {
        $id_pack = (int) $id_pack; // Ensure it's an integer
        $em = $managerRegistry->getManager();
        $pack = $packRepository->findOneBy(['id_pack' => $id_pack]);

        if (!$pack) {
            throw $this->createNotFoundException('Pack not found');
        }

        $form = $this->createForm(PackType::class, $pack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('list_packs');
        }

        return $this->renderForm('pack/editPack.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/packdelete/{id_pack}', name: 'delete_pack', methods: ['GET'])]
    public function delete(string $id_pack, PackRepository $packRepository, ManagerRegistry $managerRegistry): Response
    {
        $id_pack = (int) $id_pack; 
        $em = $managerRegistry->getManager();
        $pack = $packRepository->findOneBy(['id_pack' => $id_pack]);

        if (!$pack) {
            throw $this->createNotFoundException('Pack not found');
        }

        $em->remove($pack);
        $em->flush();

        return $this->redirectToRoute('list_packs');
    }
}
