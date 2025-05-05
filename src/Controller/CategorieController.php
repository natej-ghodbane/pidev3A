<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Repository\PartenaireRepository;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Knp\Component\Pager\PaginatorInterface;

final class CategorieController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    // Inject the EntityManagerInterface in the controller's constructor
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/categorie', name: 'app_categorie')]
    public function index(): Response
    {
        return $this->render('categorie/index.html.twig', [
            'controller_name' => 'CategorieController',
        ]);
    }

    #[Route('/Catshow', name: 'list_category', methods: ['GET'])]
    public function listCategories(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        return $this->render('categorie/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/CatshowFront', name: 'list_categoryFront', methods: ['GET'])]
    public function listCategoriesFront(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        return $this->render('categorie/ShowFront.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categorie/add', name: 'add_category', methods: ['GET', 'POST'])]
    public function addCategory(Request $request): Response
    {
        $category = new Categorie();

        // Créer et gérer le formulaire
        $form = $this->createForm(CategorieType::class, $category, [
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoFile = $form->get('logo')->getData();

            // Gérer l'upload du fichier logo
            if ($logoFile) {
                try {
                    // Vérifier l'extension du fichier
                    $logoFileName = uniqid().'.'.$logoFile->guessExtension();

                    // Déplacer le fichier dans le répertoire de destination
                    $logoFile->move(
                        $this->getParameter('logos_directory'), // Assurez-vous que ce paramètre existe dans votre config
                        $logoFileName
                    );

                    // Mettre à jour la propriété logo dans l'entité
                    $category->setLogo($logoFileName);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement du logo.');
                    return $this->render('categorie/addCat.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            // Sauvegarder la catégorie en base de données
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            // Rediriger après ajout réussi
            return $this->redirectToRoute('list_category');
        }

        // Afficher le formulaire dans la vue
        return $this->render('categorie/addCat.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/categorie/{id}/edit', name: 'edit_categorie', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('nbrPartenaire')->getData() === null) {
                $categorie->setNbrPartenaire(0); // Valeur par défaut
            }
            // Gestion du fichier logo
            $logoFile = $form->get('logo')->getData();
            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move(
                    $this->getParameter('logos_directory'),
                    $newFilename
                );
                $categorie->setLogo($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès.');
            return $this->redirectToRoute('list_category'); // Corrigez le nom de la route
        }

        return $this->render('categorie/editCat.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie
        ]);
    }




    #[Route('/Categorie/delete/{id}', name: 'delete_category', methods: ['GET'])]
    public function delete(int $id, CategorieRepository $categorieRepository, ManagerRegistry $managerRegistry): Response
    {
        // Récupérer l'EntityManager
        $em = $managerRegistry->getManager();

        // Trouver la catégorie par son ID
        $category = $categorieRepository->find($id);

        // Si la catégorie n'existe pas, afficher une erreur
        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        // Vérifier si la catégorie est utilisée par d'autres entités avant de la supprimer


        // Supprimer la catégorie
        $em->remove($category);
        $em->flush();

        // Rediriger vers la liste des catégories
        $this->addFlash('success', 'Catégorie supprimée avec succès !');
        return $this->redirectToRoute('list_category');
    }

    #[Route('/categorie/{id}', name: 'categorie_partenaires')]
    public function partenairesParCategorie(
        int $id,
        PartenaireRepository $partenaireRepository,
        CategorieRepository $categorieRepository,
        Request $request,
        PaginatorInterface $paginator,
        EntityManagerInterface $em // 👈 Ajouté pour pouvoir faire flush
    ): Response
    {
        $categorie = $categorieRepository->find($id);
        $categories = $categorieRepository->findAll();

        if (!$categorie) {
            throw $this->createNotFoundException('La catégorie n\'existe pas.');
        }

        // 👁️ Incrémenter les vues de la catégorie
        $categorie->setViews($categorie->getViews() + 1);
        $em->flush(); // très important sinon rien ne sera enregistré !

        // 🔥 Correction : utiliser QueryBuilder au lieu de findBy
        $query = $partenaireRepository->createQueryBuilder('p')
            ->where('p.id_categorie = :id')
            ->setParameter('id', $id)
            ->orderBy('p.id', 'DESC')
            ->getQuery();

        // 🔥 Appliquer la pagination
        $partenaires = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            2 // Combien de partenaires par page
        );

        return $this->render('categorie/partenaires.html.twig', [
            'categorie' => $categorie,
            'partenaires' => $partenaires,
            'categories' => $categories,
        ]);
    }

    #[Route('/categoriesearch', name: 'categorie_search')]
    public function searchCategorie(Request $request, NormalizerInterface $normalizer, CategorieRepository $repository): JsonResponse
    {
        $searchValue = $request->get('searchValue');
        $categories = $repository->findCategorieByNom($searchValue);

        $jsonContent = $normalizer->normalize($categories, 'json', ['groups' => 'categories']);

        return new JsonResponse($jsonContent);
    }
    /*#[Route('/categoriesearch', name: 'categorie_search')]
    public function searchCategorie(Request $request, NormalizerInterface $normalizer, CategorieRepository $repository): JsonResponse
    {
        $searchValue = $request->get('searchValue');
        $categories = $repository->findCategorieByNom($searchValue);

        $jsonContent = $normalizer->normalize($categories, 'json', ['groups' => 'categories']);

        return new JsonResponse($jsonContent);
    }*/
}
