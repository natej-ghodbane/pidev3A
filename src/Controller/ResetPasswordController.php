<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResetPasswordController extends AbstractController
{
    private $client;
    private $apiKey = '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';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $entityManager->flush();

                $url = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                try {
                    $response = $this->client->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                        'headers' => [
                            'accept' => 'application/json',
                            'api-key' => $this->apiKey,
                            'content-type' => 'application/json',
                        ],
                        'json' => [
                            'sender' => [
                                'name' => 'TrekSwap',
                                'email' => 'medyassinehaji87@gmail.com'
                            ],
                            'to' => [[
                                'email' => $user->getEmail(),
                                'name' => $user->getPrenom() . ' ' . $user->getNom()
                            ]],
                            'subject' => 'Réinitialisation de votre mot de passe',
                            'htmlContent' => $this->renderView('reset_password/email.html.twig', [
                                'resetUrl' => $url,
                                'user' => $user
                            ]),
                        ],
                    ]);

                    if ($response->getStatusCode() === 201) {
                        $this->addFlash('success', 'Un email de réinitialisation a été envoyé à votre adresse email.');
                        return $this->redirectToRoute('app_login');
                    } else {
                        $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.');
                }
            }

            $this->addFlash('success', 'Si un compte existe avec cette adresse email, un email de réinitialisation a été envoyé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
} 