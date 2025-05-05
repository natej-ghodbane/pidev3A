<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Partenaire;
use App\Form\PartenaireType;
use App\Repository\PartenaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieRepository;
use App\Entity\Categorie;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\TwilioService;
use Symfony\Component\HttpFoundation\JsonResponse;

class PartenaireController extends AbstractController
{
    #[Route('/partenaire', name: 'app_partenaire')]
    public function index(): Response
    {
        return $this->render('partenaire/index.html.twig', [
            'controller_name' => 'PartenaireController',
        ]);
    }

    #[Route('/Partenaires', name: 'list_partenaire', methods: ['GET'])]
    public function listPartenaires(
        PartenaireRepository $partenaireRepository,
        CategorieRepository $categorieRepository
    ): Response {
        $partenaires = $partenaireRepository->findAll();
        $categories = $categorieRepository->findAll();

        $categorieMap = [];
        foreach ($categories as $categorie) {
            $categorieMap[$categorie->getId()] = $categorie->getNom();
        }

        return $this->render('partenaire/index.html.twig', [
            'partenaires' => $partenaires,
            'categorieMap' => $categorieMap,
        ]);
    }

    #[Route('/partenaire/add', name: 'add_partenaire', methods: ['GET', 'POST'])]
    public function addPartenaire(Request $request, EntityManagerInterface $entityManager, CategorieRepository $categorieRepository): Response
    {
        $partenaire = new Partenaire();
        $partenaire->setDateAjout(new \DateTime());

        $categories = $categorieRepository->findAll();

        $form = $this->createForm(PartenaireType::class, $partenaire, [
            'categories' => $categories,
            'is_edit' => false, // Ajout donc pas édition
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move(
                    $this->getParameter('part_directory'),
                    $newFilename
                );
                $partenaire->setLogo($newFilename);
            }

            $entityManager->persist($partenaire);
            $entityManager->flush();

            $this->addFlash('success', 'Partenaire ajouté avec succès !');

            return $this->redirectToRoute('list_partenaire');
        }

        return $this->render('partenaire/addPartenaire.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/partenaire/edit/{id}', name: 'edit_partenaire', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager, CategorieRepository $categorieRepository): Response
    {
        $partenaire = $entityManager->getRepository(Partenaire::class)->find($id);

        if (!$partenaire) {
            throw $this->createNotFoundException('Partenaire non trouvé');
        }

        $categories = $categorieRepository->findAll();

        $form = $this->createForm(PartenaireType::class, $partenaire, [
            'categories' => $categories,
            'is_edit' => true,
        ]);

        $originalLogo = $partenaire->getLogo(); // ⭐ On sauvegarde l'ancien logo
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();
            if ($logoFile) {
                // Supprimer l'ancien fichier si existe
                if ($originalLogo) {
                    $oldLogoPath = $this->getParameter('part_directory') . '/' . $originalLogo;
                    if (is_file($oldLogoPath)) {
                        @unlink($oldLogoPath);
                    }
                }

                // Upload du nouveau logo
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move(
                    $this->getParameter('part_directory'),
                    $newFilename
                );
                $partenaire->setLogo($newFilename);
            } else {
                // ⭐ Aucun nouveau logo => on remet l'ancien
                $partenaire->setLogo($originalLogo);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Partenaire mis à jour avec succès !');
            return $this->redirectToRoute('list_partenaire');
        }

        return $this->render('partenaire/editPartenaire.html.twig', [
            'partenaire' => $partenaire,
            'form' => $form->createView(),
        ]);
    }





    #[Route('/partenaire/delete/{id}', name: 'delete_partenaire', methods: ['GET'])]
    public function delete(int $id, PartenaireRepository $partenaireRepository, ManagerRegistry $managerRegistry): Response
    {
        $em = $managerRegistry->getManager();
        $partenaire = $partenaireRepository->find($id);

        if (!$partenaire) {
            throw $this->createNotFoundException('Partenaire non trouvé');
        }

        $logo = $partenaire->getLogo();
        if ($logo) {
            $logoPath = $this->getParameter('part_directory').'/'.$logo;
            if (file_exists($logoPath) && is_file($logoPath)) {
                unlink($logoPath);
            }
        }

        $em->remove($partenaire);
        $em->flush();

        $this->addFlash('success', 'Partenaire supprimé avec succès !');
        return $this->redirectToRoute('list_partenaire');
    }

    #[Route('/partenaire/send-sms/{id}', name: 'send_sms_partenaire', methods: ['POST'])]
    public function sendSmsPartenaire(int $id, Request $request, PartenaireRepository $partenaireRepository, TwilioService $twilioService): JsonResponse
    {
        $partenaire = $partenaireRepository->find($id);
        if (!$partenaire || !$partenaire->getNumTel()) {
            return new JsonResponse(['error' => 'Partenaire ou numéro non trouvé.'], 404);
        }

        $message = $request->request->get('message');

        if (!$message) {
            return new JsonResponse(['error' => 'Message vide.'], 400);
        }

        $numero = '+216' . preg_replace('/\D/', '', $partenaire->getNumTel());

        $twilioService->sendSms($numero, $message);

        return new JsonResponse(['success' => 'SMS envoyé avec succès.']);
    }
}
