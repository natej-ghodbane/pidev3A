<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

class LoginSuccessListener
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onLoginSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        if ($user instanceof User) {
            $user->setLastLogin(new \DateTime());
            $user->setIsActive(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
} 