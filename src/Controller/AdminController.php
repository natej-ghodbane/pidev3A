<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $stats = [
            'total' => count($users),
            'active' => count(array_filter($users, fn($user) => $user->isActive())),
            'inactive' => count(array_filter($users, fn($user) => !$user->isActive()))
        ];

        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'stats' => $stats
        ]);
    }

    #[Route('/search', name: 'app_admin_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('qu', '');
        $users = $userRepository->search($query);

        $results = array_map(function(User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'typeUser' => $user->getTypeUser(),
                'photoProfile' => $user->getPhotoProfile(),
            ];
        }, $users);

        return $this->json($results);
    }

    #[Route('/user/delete/{id}', name: 'app_admin_delete_user')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Check if trying to delete self
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot delete your own account.');
            return $this->redirectToRoute('app_admin');
        }

        // Check if trying to delete an admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'Admin users cannot be deleted.');
            return $this->redirectToRoute('app_admin');
        }

        try {
            // Delete user's profile picture if it exists
            if ($user->getPhotoProfile()) {
                $profilePicturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getPhotoProfile();
                if (file_exists($profilePicturePath)) {
                    unlink($profilePicturePath);
                }
            }

            // Delete user's ID card photos if they exist
            if ($user->getPhotoCarteF1()) {
                $idCardPath = $this->getParameter('identity_cards_directory') . '/' . $user->getPhotoCarteF1();
                if (file_exists($idCardPath)) {
                    unlink($idCardPath);
                }
            }
            if ($user->getPhotoCarteF2()) {
                $idCardPath = $this->getParameter('identity_cards_directory') . '/' . $user->getPhotoCarteF2();
                if (file_exists($idCardPath)) {
                    unlink($idCardPath);
                }
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'An error occurred while deleting the user: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/user/toggle-status/{id}', name: 'app_admin_toggle_user_status')]
    public function toggleUserStatus(User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot change your own status.');
            return $this->redirectToRoute('app_admin');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'Admin status cannot be changed.');
            return $this->redirectToRoute('app_admin');
        }

        try {
            $user->setIsActive(!$user->isIsActive());
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'User status has been changed to %s.',
                $user->isIsActive() ? 'active' : 'inactive'
            ));
        } catch (\Exception $e) {
            $this->addFlash('danger', 'An error occurred while updating the user status.');
        }

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/user/bulk-delete', name: 'app_admin_bulk_delete_users', methods: ['POST'])]
    public function bulkDeleteUsers(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid token'
            ], Response::HTTP_BAD_REQUEST);
        }

        $userIds = $request->request->all('users');
        if (empty($userIds)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No users selected'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $deletedCount = 0;
            $errors = [];

            foreach ($userIds as $userId) {
                $user = $userRepository->find($userId);
                if (!$user) {
                    continue;
                }

                // Skip if trying to delete themselves
                if ($user === $this->getUser()) {
                    $errors[] = 'You cannot delete your own account';
                    continue;
                }

                // Delete user's files
                if ($user->getPhotoProfile()) {
                    $profilePicPath = $this->getParameter('profile_pictures_directory') . '/' . $user->getPhotoProfile();
                    if (file_exists($profilePicPath)) {
                        unlink($profilePicPath);
                    }
                }
                if ($user->getPhotoCarteF1()) {
                    $idCard1Path = $this->getParameter('uploads_directory') . '/identity_cards/' . $user->getPhotoCarteF1();
                    if (file_exists($idCard1Path)) {
                        unlink($idCard1Path);
                    }
                }
                if ($user->getPhotoCarteF2()) {
                    $idCard2Path = $this->getParameter('uploads_directory') . '/identity_cards/' . $user->getPhotoCarteF2();
                    if (file_exists($idCard2Path)) {
                        unlink($idCard2Path);
                    }
                }

                $entityManager->remove($user);
                $deletedCount++;
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => $deletedCount . ' users have been deleted successfully',
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while deleting users: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/restore/{id}', name: 'app_admin_restore_user', methods: ['POST'])]
    public function restoreUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('restore-user-'.$id, $request->request->get('_token'))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid token'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $user->setDeletedAt(null);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'User has been restored successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while restoring the user'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 