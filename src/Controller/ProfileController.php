<?php

namespace App\Controller;

use App\Form\ProfileFormType;
use App\Repository\AbonnementRepository;
use App\Repository\PackRepository;
use App\Repository\UserAbonnementRepository; // Add this repository for user_abonnement
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        AbonnementRepository $abonnementRepository,
        PackRepository $packRepository,
        UserAbonnementRepository $userAbonnementRepository // Inject UserAbonnementRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change if provided
            if ($newPassword = $form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $newPassword)
                );
            }

            // Handle profile picture upload
            if ($photoFile = $form->get('photo_profil')->getData()) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                    $user->setPhotoProfile($newFilename);
                } catch (\Exception $e) {
                    // Handle file upload error
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        // Retrieve abonnements for the current user by checking user_abonnement table
        $userAbonnements = $userAbonnementRepository->findBy(['id_user' => $user->getId()]);
        $abonnements = [];

        foreach ($userAbonnements as $userAbonnement) {
            // Retrieve the associated abonnement details
            $abonnement = $abonnementRepository->find($userAbonnement->getIdAbonnement());
            if ($abonnement) {
                $abonnements[] = $abonnement; // Add the abonnement to the array
            }
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
            'abonnements' => $abonnements,
            'packRepository' => $packRepository,
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Prevent admin account deletion
        if ($user->getTypeUser() === 'Admin') {
            $this->addFlash('error', 'Les comptes administrateurs ne peuvent pas être supprimés.');
            return $this->redirectToRoute('app_profile');
        }

        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-profile', $submittedToken)) {
            // Remove profile picture if exists
            if ($user->getPhotoProfile()) {
                $picturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getPhotoProfile();
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
            }

            // Log out the user
            $this->container->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();

            // Remove the user
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'Token invalide.');
        return $this->redirectToRoute('app_profile');
    }
}
