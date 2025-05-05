<?php

namespace App\Controller;

use App\Entity\Destination;
use App\Entity\Whishlist;
use App\Repository\DestinationRepository;
use App\Service\MarkdownService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\GeminiService;
use Symfony\Component\HttpFoundation\Request;

final class WhishlistController extends AbstractController
{
    #[Route('/wishlist', name: 'app_whishlist')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir votre liste de favoris.');
            return $this->redirectToRoute('app_login');
        }

        $whishlists = $em->getRepository(Whishlist::class)->findBy([
            'user' => $user,
        ]);

        return $this->render('whishlist/index.html.twig', [
            'whishlists' => $whishlists,
        ]);
    }

    #[Route('/wishlist/add/{id}', name: 'add_to_whishlist', methods: ['POST'])]
    public function addToWhishlist(int $id, EntityManagerInterface $em, DestinationRepository $destinationRepo): Response
    {
        $destination = $destinationRepo->find($id);
        $user = $this->getUser();

        if (!$user || !$destination) {
            return $this->redirectToRoute('listFrontDestination');
        }

        // 🔥 Check if the destination is already in the user's wishlist
        $exists = $em->getRepository(Whishlist::class)->findOneBy([
            'user' => $user,
            'destination' => $destination,
        ]);

        if ($exists) {
            $this->addFlash('info', 'Cette destination est déjà dans vos favoris.');
        } else {
            $whishlist = new Whishlist();
            $whishlist->setUser($user);
            $whishlist->setDestination($destination);
            $em->persist($whishlist);
            $em->flush();

            $this->addFlash('success', 'Destination ajoutée à vos favoris !');
        }

        return $this->redirectToRoute('listFrontDestination');
    }
    #[Route('/wishlist/remove/{id}', name: 'remove_from_whishlist', methods: ['POST'])]
    public function removeFromWhishlist(int $id, EntityManagerInterface $em): Response
    {
        $whishlist = $em->getRepository(Whishlist::class)->find($id);

        if (!$whishlist || $whishlist->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_whishlist');
        }

        $em->remove($whishlist);
        $em->flush();

        $this->addFlash('success', 'Destination supprimée de vos favoris.');

        return $this->redirectToRoute('app_whishlist');
    }
    #[Route('/generate-plan', name: 'generate_plan', methods: ['POST'])]
    public function generatePlan(EntityManagerInterface $em, GeminiService $geminiService, MarkdownService $markdownService, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour générer un plan.');
            return $this->redirectToRoute('app_login');
        }

        $whishlists = $em->getRepository(\App\Entity\Whishlist::class)->findBy([
            'user' => $user,
        ]);

        if (empty($whishlists)) {
            $this->addFlash('info', 'Votre liste de favoris est vide.');
            return $this->redirectToRoute('app_whishlist');
        }

        $days = $request->request->get('days', 5);
        $language = $request->request->get('language', 'fr'); // default to French

        $destinationsList = array_map(function($w) {
            return $w->getDestination()->getNomDestination();
        }, $whishlists);

        // ✨ Build a multi-language prompt
        if ($language === 'fr') {
            $prompt = "Je veux visiter la Tunisie pendant $days jours. Voici mes destinations : " . implode(', ', $destinationsList) . ". 
Peux-tu me proposer un plan de voyage détaillé, jour par jour, en français ?";
        } elseif ($language === 'en') {
            $prompt = "I want to visit Tunisia for $days days. Here are my destinations: " . implode(', ', $destinationsList) . ". 
Can you create a detailed travel plan day by day in English?";
        } elseif ($language === 'ar') {
            $prompt = "أريد زيارة تونس لمدة $days أيام. هذه هي الوجهات: " . implode(', ', $destinationsList) . ". 
هل يمكنك إعداد خطة سفر مفصلة لكل يوم باللغة العربية؟";
        } else {
            $prompt = "Je veux visiter la Tunisie pendant $days jours. Voici mes destinations : " . implode(', ', $destinationsList) . ".";
        }

        $plan = $geminiService->generateContent($prompt);

        $planHtml = $markdownService->toHtml($plan);

        return $this->render('whishlist/plan.html.twig', [
            'plan' => $planHtml,
        ]);
    }

    #[Route('/toggle-whishlist/{id}', name: 'toggle_whishlist', methods: ['POST'])]
    public function toggleWhishlist(
        Destination $destination,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $wishlistRepo = $entityManager->getRepository(Whishlist::class);

        $existing = $wishlistRepo->findOneBy([
            'user' => $user,
            'destination' => $destination,
        ]);

        if ($existing) {
            // Already exists → remove it
            $entityManager->remove($existing);
            $entityManager->flush();
            return $this->json(['status' => 'removed']);
        } else {
            // Not exists → add it
            $whishlist = new Whishlist();
            $whishlist->setUser($user);
            $whishlist->setDestination($destination);
            $entityManager->persist($whishlist);
            $entityManager->flush();
            return $this->json(['status' => 'added']);
        }
    }


}
