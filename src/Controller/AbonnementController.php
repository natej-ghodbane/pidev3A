<?php

namespace App\Controller;
use App\Service\SubscriptionPredictionService;  // <-- Correct namespace for the service
use App\Entity\Pack;
use App\Entity\Abonnement;
use App\Form\AbonnementType;
use App\Repository\AbonnementRepository;
use App\Repository\PackRepository;
use App\Repository\CategorieRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


final class AbonnementController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(AbonnementRepository $abonnementRepository, PackRepository $packRepository, CategorieRepository $categorieRepository): Response
    {
        $abonnements = $abonnementRepository->findAll();
        $categories = $categorieRepository->findAll();
        return $this->render('base.html.twig', [
            'abonnements' => $abonnements,
            'packRepository' => $packRepository,
            'categories' => $categories,
        ]);
    }


    //PREDICTION

    private $entityManager;
    private $predictionService;

    // Constructor where we inject both EntityManager and PredictionService
    public function __construct(EntityManagerInterface $entityManager, SubscriptionPredictionService $predictionService)
    {
        $this->entityManager = $entityManager;
        $this->predictionService = $predictionService; // Assign the prediction service here
    }

    #[Route("/subscription/{id}/predict", name:"subscription_predict")]
    public function predict(int $id): Response
    {
        $abonnement = $this->entityManager->getRepository(Abonnement::class)->find($id);



        if (!$abonnement) {
            throw $this->createNotFoundException('Subscription not found.');
        }

        // Get the prediction for the subscription
        $prediction = $this->predictionService->predictNonRenewal($abonnement);

        // Redirect to the abonnements list with the prediction passed in the URL
        return $this->redirectToRoute('list_Abonnement', [
            'prediction' => $prediction,
        ]);
    }


    // HISTORIQUE
    private function enregistrerHistorique(EntityManagerInterface $em, string $action, Abonnement $abonnement, ?string $details = null)
{
    $historique = new \App\Entity\HistoriqueAbonnement();
    $historique->setAction($action);
    $historique->setDateAction(new \DateTime());
    $historique->setAbonnementId($abonnement->getIdAbonnement());
    $historique->setDetails($details);

    $em->persist($historique);
    $em->flush();
}


    #[Route('/pricing', name: 'app_Abonnement')]
    public function indexA(): Response
    {
        return $this->redirectToRoute('pricing');
    }

    #[Route('/frontA', name: 'pricing')]
public function pricing(
    AbonnementRepository $abonnementRepository,
    PackRepository $packRepository,
    CategorieRepository $categorieRepository
): Response {
    $abonnements = $abonnementRepository->findAll();
    $categories = $categorieRepository->findAll();

    $qrCodes = [];

    foreach ($abonnements as $abonnement) {
        $pack = $abonnement->getPack();

        if ($pack) {
            $data = sprintf(
                "Pack Name: %s\nPrice: %s DT\nDuration: %s months\nDetails: %s",
                $pack->getNomPack(),
                $pack->getPrix(),
                $pack->getDuree(),
                $pack->getAvantages()
            );

            $qrCode = Builder::create()
                ->writer(new SvgWriter())
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(180)
                ->margin(10)
                ->build();

            $qrCodes[$abonnement->getIdPack()] = $qrCode->getString();
        }
    }

    return $this->render('base.html.twig', [
        'abonnements' => $abonnements,
        'packRepository' => $packRepository,
        'categories' => $categories,
        'qrCodes' => $qrCodes, // Add this line
    ]);
}
    #[Route('/backA', name: 'app_Abonnements')]
    public function indexBA(AbonnementRepository $abonnementRepository): Response
    {
        return $this->render('base-back.html.twig', [
            'Abonnements' => $abonnementRepository->findAll(),
        ]);
    }

    #[Route('/Abonshow', name: 'list_Abonnement', methods: ['GET'])]
public function listAbonnements(AbonnementRepository $abonnementRepository, EntityManagerInterface $em, Request $request): Response
{
    $abonnements = $abonnementRepository->findAll();
    $historiques = $em->getRepository(\App\Entity\HistoriqueAbonnement::class)->findAll();

    // Retrieve prediction if available (passed from the "predict" route)
    $prediction = $request->get('prediction', null);  // Get the prediction value

    return $this->render('abonnements/showAbon.html.twig', [
        'abonnements' => $abonnements,
        'historiques' => $historiques,
        'prediction' => $prediction, // Pass prediction to the view
    ]);
}


    #[Route('/AbonshowF', name: 'list_AbonnementF', methods: ['GET'])]
    public function listAbonnementF(AbonnementRepository $abonnementRepository): Response
    {
        $abonnements = $abonnementRepository->findAll();

        return $this->render('abonnements/showfrontA.html.twig', [
            'abonnements' => $abonnements,
        ]);
    }

    #[Route('/Abonadd', name: 'add_Abonnement', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $managerRegistry): Response
    {
        $abonnement = new Abonnement();
    
    // Fetch only the integer IDs of all packs
    $packIds = $managerRegistry->getRepository(Pack::class)->getAllPackIds();
    
    // Pass the integer pack IDs to the form
    $form = $this->createForm(AbonnementType::class, $abonnement, [
        'pack_ids' => $packIds
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $managerRegistry->persist($abonnement);
        $managerRegistry->flush();

        $this->enregistrerHistorique($managerRegistry, 'ajout', $abonnement, 'Ajout d\'un nouvel abonnement');

        return $this->redirectToRoute('list_Abonnement');
    }


    return $this->render('abonnements/addAbon.html.twig', [
        'abonnement' => $abonnement,
        'form' => $form->createView(),
    ]);
    }

    #[Route('/Abonedit/{id_abonnement}', name: 'edit_Abonnement', methods: ['GET', 'POST'])]
public function edit(int $id_abonnement, Request $request, EntityManagerInterface $managerRegistry): Response
{
    $abonnement = $managerRegistry->getRepository(Abonnement::class)->find($id_abonnement);
    
    if (!$abonnement) {
        throw $this->createNotFoundException('Abonnement not found');
    }

    $packIds = $managerRegistry->getRepository(Pack::class)->getAllPackIds();
    
    $form = $this->createForm(AbonnementType::class, $abonnement, [
        'pack_ids' => $packIds
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $managerRegistry->flush();

        $this->enregistrerHistorique($managerRegistry, 'modification', $abonnement, 'Modification d\'un abonnement existant');

        return $this->redirectToRoute('list_Abonnement');
    }


    return $this->render('abonnements/editAbon.html.twig', [
        'abonnement' => $abonnement,
        'form' => $form->createView(),
    ]);
}


    #[Route('/Abondelete/{id_abonnement}', name: 'delete_Abonnement', methods: ['GET'])]
    public function delete(int $id_abonnement, AbonnementRepository $abonnementRepository, ManagerRegistry $managerRegistry): Response
    {
        $em = $managerRegistry->getManager();
        $abonnement = $abonnementRepository->find($id_abonnement);

        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement non trouvé');
        }

        $this->enregistrerHistorique($em, 'suppression', $abonnement, 'Suppression d\'un abonnement');

        $em->remove($abonnement);
        $em->flush();

        return $this->redirectToRoute('list_Abonnement');

    }
    #[Route('/abonnement/search', name: 'search_abonnement')]
    public function searchAbonnement(Request $request, AbonnementRepository $repo, NormalizerInterface $normalizer): JsonResponse
    {
        $searchValue = $request->get('searchValue');

        $abonnements = $repo->findByPackName($searchValue);

        $jsonContent = $normalizer->normalize($abonnements, 'json', ['groups' => 'abonnements']);

        return new JsonResponse($jsonContent);
    }
}
