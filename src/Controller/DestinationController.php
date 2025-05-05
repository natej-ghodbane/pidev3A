<?php

namespace App\Controller;

use App\Entity\AvisDestination;
use App\Entity\Destination;
use App\Entity\Whishlist;
use App\Form\DestinationType;
use App\Repository\CategorieRepository;
use App\Repository\DestinationRepository;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DestinationController extends AbstractController
{
    #[Route('/destination', name: 'app_destination')]
    public function index(): Response
    {
        return $this->render('destination/index.html.twig', [
            'controller_name' => 'DestinationController',
        ]);
    }

    #[Route('/DestinationBackShow', name: 'list_destination', methods: ['GET'])]
    public function list_Abonnement(DestinationRepository $destinationRepository): Response
    {
        return $this->render('destination/index.html.twig', [
            'destinations' => $destinationRepository->findAll(),
        ]);
    }
    #[Route('/DestinationFrontShow', name: 'listFrontDestination', methods: ['GET'])]
    public function listDestination(
        DestinationRepository $destinationRepository,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $entityManager,
        WeatherService $weatherService
    ): Response {
        $categories = $categorieRepository->findAll();
        $destinations = $destinationRepository->findAll();

        $wishlist = [];
        if ($this->getUser()) {
            $wishlistItems = $entityManager->getRepository(Whishlist::class)
                ->findBy(['user' => $this->getUser()]);

            foreach ($wishlistItems as $item) {
                $wishlist[] = $item->getDestination()->getId();
            }
        }

        $avisRepository = $entityManager->getRepository(\App\Entity\AvisDestination::class);

        foreach ($destinations as $destination) {
            // Personal user vote
            $vote = $avisRepository->findOneBy([
                'user' => $this->getUser(),
                'destination' => $destination,
            ]);

            $destination->userRating = $vote ? $vote->getScore() : null;

            // Global rating
            $qb = $entityManager->createQueryBuilder()
                ->select('AVG(a.score) as avgScore, COUNT(a.id) as voteCount')
                ->from(\App\Entity\AvisDestination::class, 'a')
                ->where('a.destination = :destination')
                ->setParameter('destination', $destination);

            $result = $qb->getQuery()->getSingleResult();
            $destination->avgRating = $result['avgScore'] ? round($result['avgScore'], 1) : null;
            $destination->voteCount = $result['voteCount'] ?? 0;

            // ⭐ Inject the Weather directly inside Destination object
            $weather = $weatherService->getWeather($destination->getLatitude(), $destination->getLongitude());
            $destination->setWeather($weather);
        }


        return $this->render('destination/showFront.html.twig', [
            'destinations' => $destinations,
            'categories' => $categories,
            'wishlist' => $wishlist,
        ]);
    }



    #[Route('/addDestination', name: 'addDestination', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $manager): Response
    {
        $destination = new Destination();
        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageDestination')->getData();

            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );

                $destination->setImageDestination('/uploads/'.$newFilename);
            }

            $manager->persist($destination);
            $manager->flush();

            return $this->redirectToRoute('list_destination');
        }

        return $this->render('destination/addDest.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/editDestination/{id}', name: 'editDest', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $manager, ValidatorInterface $validator): Response
    {
        $destination = $manager->getRepository(Destination::class)->find($id);

        if (!$destination) {
            throw $this->createNotFoundException('Destination not found');
        }

        $form = $this->createForm(DestinationType::class, $destination);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload if the image is updated
            $imageFile = $form->get('imageDestination')->getData();

            if ($imageFile) {
                // Generate a unique filename
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the uploads directory
                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );

                // Store the file path in the entity
                $destination->setImageDestination('/uploads/'.$newFilename);
            }
            $manager->flush();

            return $this->redirectToRoute('list_destination'); // Redirect to the list after edit
        }

        return $this->render('destination/editDest.html.twig', [
            'form' => $form->createView(),
            'destination' => $destination
        ]);
    }


    #[Route('/deleteDestination/{id}', name: 'deleteDest', methods: ['GET'])]
    public function delete(int $id, DestinationRepository $destinationRepository, EntityManagerInterface $manager): Response
    {
        $destination = $destinationRepository->find($id);

        if (!$destination) {
            throw $this->createNotFoundException('Destination not found');
        }

        $manager->remove($destination);
        $manager->flush();

        return $this->redirectToRoute('list_destination'); // Redirect to the list after deletion
    }
    #[Route('/vote/{destination}', name: 'vote_destination', methods: ['POST'])]
    public function vote(Request $request, Destination $destination, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour voter.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $score = $data['score'] ?? null;

        if ($score === null || $score < 0 || $score > 5) {
            return new JsonResponse(['success' => false, 'message' => 'Score invalide.'], 400);
        }

        $avisRepository = $entityManager->getRepository(\App\Entity\AvisDestination::class);

        // Find if the user already voted for this destination
        $vote = $avisRepository->findOneBy([
            'user' => $user,
            'destination' => $destination,
        ]);

        if (!$vote) {
            $vote = new \App\Entity\AvisDestination();
            $vote->setUser($user);
            $vote->setDestination($destination);
        }

        $vote->setScore($score);

        $entityManager->persist($vote);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
    #[Route('/destination-average/{destination}', name: 'destination_average', methods: ['GET'])]
    public function destinationAverage(Destination $destination, EntityManagerInterface $entityManager): JsonResponse
    {
        $qb = $entityManager->createQueryBuilder()
            ->select('AVG(a.score) as avgScore, COUNT(a.id) as voteCount')
            ->from(\App\Entity\AvisDestination::class, 'a')
            ->where('a.destination = :destination')
            ->setParameter('destination', $destination);

        $result = $qb->getQuery()->getSingleResult();

        return new JsonResponse([
            'success' => true,
            'avgRating' => $result['avgScore'] ? round($result['avgScore'], 1) : 0,
            'voteCount' => $result['voteCount'] ?? 0,
        ]);
    }


}
