<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    // ... existing code ...

    #[Route('/user/search', name: 'app_user_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
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
} 