<?php

namespace App\Controller;

use App\Repository\UserAbonnementRepository;
use App\Repository\AbonnementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserAbonnementController extends AbstractController
{
    #[Route('/user/abonnements', name: 'user_abonnements', methods: ['GET'])]
    public function listAbonnements(
        UserAbonnementRepository $userAbonnementRepository,
        AbonnementRepository $abonnementRepository
    ): Response {
        $user = $this->getUser();

        // If user is not connected, show the "please log in" message
        if (!$user) {
            return $this->render('abonnements/showAbon.html.twig', [
                'abonnements' => [],
                'message' => 'Veuillez vous connecter pour voir vos abonnements.'
            ]);
        }

        // Find all user_abonnement records for the connected user
        $userAbonnements = $userAbonnementRepository->findBy(['id_user' => $user->getId()]);

        // If no user_abonnement records found
        if (empty($userAbonnements)) {
            return $this->render('abonnements/showAbon.html.twig', [
                'abonnements' => [],
                'message' => 'Aucun abonnement trouvé.'
            ]);
        }

        // Extract all abonnement IDs
        $abonnementIds = array_map(fn($userAbonnement) => $userAbonnement->getIdAbonnement(), $userAbonnements);

        // Fetch abonnements from IDs
        $abonnements = $abonnementRepository->findBy(['id_abonnement' => $abonnementIds]);

        // If no abonnements found for this user
        if (empty($abonnements)) {
            return $this->render('abonnements/showAbon.html.twig', [
                'abonnements' => [],
                'message' => 'Aucun abonnement trouvé.'
            ]);
        }

        // If abonnements are found
        return $this->render('abonnements/showAbon.html.twig', [
            'abonnements' => $abonnements,
            'message' => null,
        ]);
    }
}
