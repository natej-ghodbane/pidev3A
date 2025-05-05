<?php

namespace App\Controller;

use App\Entity\Mission;
use App\Entity\User;
use App\Form\MissionType;
use App\Repository\CategorieRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\OcrService;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;;
use EWZ\RecaptchaBundle\Service\Recaptcha as EwzRecaptcha;
use App\Entity\UserMission;
use App\Service\RecaptchaVerifier;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


#[Route('/mission')]
final class MissionController extends AbstractController
{
    const MAX_MISSIONS_PAR_JOUR = 2;
  /*  #[Route(name: 'app_mission_index', methods: ['GET'])]
    public function index(MissionRepository $missionRepository): Response
    {
        return $this->render('mission/index.html.twig', [
            'missions' => $missionRepository->findAll(),
        ]);
    }*/

    #[Route('/new', name: 'app_mission_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerRegistry $doctrine

    ): Response {
        $mission = new Mission();
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mission);
            $entityManager->flush();

            // 🔢 Récupérer le nombre total de missions après insertion
            $totalMissions = $doctrine->getRepository(Mission::class)
                ->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $missionsPerPage = 10; // même nombre que dans listeMissions()
            $lastPage = ceil($totalMissions / $missionsPerPage);

            // ✅ Rediriger vers la dernière page
            return $this->redirectToRoute('app_mission_index', ['page' => $lastPage], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission/new.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }


    #[Route('/details/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function show(Mission $mission): Response
    {
        // Symfony va automatiquement récupérer la mission par son ID
        return $this->render('mission/show.html.twig', [
            'mission' => $mission
        ]);
    }


    #[Route('/{id}/edit', name: 'app_mission_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mission $mission, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_mission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission/edit.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }


    #[Route('/mission/delete/{id}', name: 'app_mission_delete', methods: ['GET'])]
    public function delete(int $id, MissionRepository $missionRepository, ManagerRegistry $managerRegistry): Response
    {
        $em = $managerRegistry->getManager();

        $mission = $missionRepository->find($id);

        if (!$mission) {
            throw $this->createNotFoundException('Mission non trouvée');
        }

        $em->remove($mission);
        $em->flush();

        return $this->redirectToRoute('app_mission_index');
    }

    #[Route('/missions-front', name: 'app_mission_front', methods: ['GET'])]
    public function frontShow(
        MissionRepository $repo,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $categories = $categorieRepository->findAll();
        $missions = $repo->findAll();

        $totalPoints = 0;
        $missionsValidees = [];
        $missionsValideesAujourdhui = 0;

        if ($this->getUser()) {
            $today = new \DateTime();
            $today->setTime(0, 0, 0);

            $missionsValideesAujourdhui = $entityManager->getRepository(UserMission::class)
                ->createQueryBuilder('um')
                ->select('COUNT(um.id)')
                ->where('um.user = :user')
                ->andWhere('um.validatedAt >= :today')
                ->setParameter('user', $this->getUser())
                ->setParameter('today', $today)
                ->getQuery()
                ->getSingleScalarResult();

            $userMissions = $entityManager->getRepository(UserMission::class)
                ->findBy(['user' => $this->getUser()]);

            foreach ($userMissions as $um) {
                $totalPoints += $um->getPointsGagnes();
                $missionsValidees[] = $um->getMission()->getId();
            }
        }

        // 🔥 Partie pour le Top 3 utilisateurs
        $users = $entityManager->getRepository(User::class)->findAll();
        $userPoints = [];

        foreach ($users as $user) {
            $userMissions = $entityManager->getRepository(UserMission::class)
                ->findBy(['user' => $user]);

            $points = 0;
            foreach ($userMissions as $um) {
                $points += $um->getPointsGagnes();
            }

            $userPoints[] = [
                'user' => $user,
                'points' => $points
            ];
        }

        // Trier les utilisateurs par leurs points décroissants
        usort($userPoints, function ($a, $b) {
            return $b['points'] <=> $a['points'];
        });

        // Garder seulement les 3 premiers
        $top3Users = array_slice($userPoints, 0, 3);

        return $this->render('mission/missionFront.html.twig', [
            'missions' => $missions,
            'categories' => $categories,
            'totalPoints' => $totalPoints,
            'missionsValidees' => $missionsValidees,
            'missionsValideesAujourdhui' => $missionsValideesAujourdhui,
            'recaptcha_site_key' => $_ENV['EWZ_RECAPTCHA_SITE_KEY'],
            'top3Users' => $top3Users, // 🔥 On envoie aussi top 3 à Twig
        ]);
    }



    #[Route('/mission/{id}', name: 'app_mission_details', methods: ['GET'])]
    public function details(?Mission $mission): Response
    {
        if (!$mission) {
            throw $this->createNotFoundException('La mission demandée est introuvable.');
        }

        return $this->render('mission/show.html.twig', [
            'mission' => $mission
        ]);
    }



    #[Route('/mission/valider/{id}', name: 'app_mission_valider', methods: ['POST'])]
    public function validerMission(
        int $id,
        Request $request,
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager,
        OcrService $ocrService,
        Security $security,
        RecaptchaVerifier $recaptchaVerifier,
        HttpClientInterface $client // ✅ On utilise HttpClient au lieu de MailerInterface
    ): Response {
        $user = $security->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour valider une mission.');
            return $this->redirectToRoute('app_mission_front');
        }

        $mission = $missionRepository->find($id);
        if (!$mission) {
            $this->addFlash('error', 'Mission introuvable.');
            return $this->redirectToRoute('app_mission_front');
        }

        $existingValidation = $entityManager->getRepository(UserMission::class)
            ->findOneBy(['user' => $user, 'mission' => $mission]);
        if ($existingValidation) {
            $this->addFlash('info', 'Vous avez déjà validé cette mission.');
            return $this->redirectToRoute('app_mission_front');
        }

        $recaptchaResponse = $request->get('g-recaptcha-response');
        if (!$recaptchaVerifier->verify($recaptchaResponse)) {
            $this->addFlash('error', 'Merci de valider le reCAPTCHA.');
            return $this->redirectToRoute('app_mission_front');
        }

        $file = $request->files->get('preuve');
        if (!$file || !$file->isValid()) {
            $this->addFlash('error', 'Veuillez soumettre une image valide.');
            return $this->redirectToRoute('app_mission_front');
        }

        $aujourdhui = new \DateTime();
        $aujourdhui->setTime(0, 0, 0);

        $missionsValideesAujourdhui = $entityManager->getRepository(UserMission::class)
            ->createQueryBuilder('um')
            ->select('COUNT(um.id)')
            ->where('um.user = :user')
            ->andWhere('um.validatedAt >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', $aujourdhui)
            ->getQuery()
            ->getSingleScalarResult();

        if ($missionsValideesAujourdhui >= self::MAX_MISSIONS_PAR_JOUR) {
            $this->addFlash('error', '❌ Vous avez déjà validé ' . self::MAX_MISSIONS_PAR_JOUR . ' missions aujourd\'hui. Réessayez demain !');
            return $this->redirectToRoute('app_mission_front');
        }

        $extractedText = $ocrService->extractText($file->getPathname());
        if (!$extractedText || stripos($extractedText, 'validation mission') === false) {
            $this->addFlash('error', 'Preuve Invalide.');
            return $this->redirectToRoute('app_mission_front');
        }

        // ✅ Enregistrement dans UserMission
        $userMission = new UserMission();
        $userMission->setUser($user);
        $userMission->setMission($mission);
        $userMission->setPointsGagnes($mission->getPointsRecompense());
        $userMission->setValidatedAt(new \DateTime());

        $entityManager->persist($userMission);
        $entityManager->flush();

        // ✅ ENVOI EMAIL PAR API HTTP
        // ✅ ENVOI EMAIL PAR API HTTP
        $apiKey = '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'; // ton API Key (attention à la régénérer)
        try {
            $response = $client->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $apiKey,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'sender' => ['email' => 'douaabj4@gmail.com'],
                    'to' => [['email' => $user->getEmail()]],
                    'subject' => '🎉 Félicitations pour votre mission validée !',
                    'htmlContent' => '
                <h2>Bravo ' . htmlspecialchars($user->getPrenom()) . ' 🎯</h2>
                <p>Vous avez validé la mission : <strong>' . htmlspecialchars($mission->getDescription()) . '</strong> et gagné <strong>' . $mission->getPointsRecompense() . ' points</strong> !</p>
                <p>Continuez ainsi 🚀</p>
            ',
                ],
            ]);

            if ($response->getStatusCode() !== 201 && $response->getStatusCode() !== 200) {
                $this->addFlash('warning', 'Mission validée mais l\'email n\'a pas pu être envoyé (Erreur API).');
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Mission validée mais impossible d\'envoyer l\'email. Réessayez plus tard.');
        }


        // Pas besoin de traiter la réponse ici car on suppose que Brevo gère ✅

        $this->addFlash('success', '✅ Mission validée et points gagnés ! Un email de félicitations vous a été envoyé.');
        return $this->redirectToRoute('app_mission_front');
    }


    #[Route(name: 'app_mission_index')]
    public function listeMissions(Request $request, PaginatorInterface $paginator, ManagerRegistry $doctrine): Response
    {
        // Créer la requête pour récupérer les missions avec un tri par id croissant
        $missionsQuery = $doctrine->getRepository(Mission::class)
            ->createQueryBuilder('m')
            // Tri par id croissant
            ->orderBy('m.id', 'ASC')  // Trier par id de manière croissante
            ->getQuery();

        // Pagination des résultats (10 missions par page)
        $pagination = $paginator->paginate(
            $missionsQuery, // La requête
            $request->query->getInt('page', 1), // Le numéro de la page à afficher (par défaut 1)
            8 // Nombre de missions à afficher par page
        );

        // Retourne la vue avec les missions paginées
        return $this->render('mission/index.html.twig', [
            'pagination' => $pagination, // On passe les résultats paginés à la vue
        ]);
    }

    #[Route('/admin/mission/search', name: 'app_mission_search')]
    public function search(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $query = $request->query->get('q');

        $qb = $entityManager->getRepository(Mission::class)
            ->createQueryBuilder('m')
            ->where('m.description LIKE :search')
            ->setParameter('search', '%' . $query . '%')
            ->orderBy('m.id', 'DESC');

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            8
        );

        return $this->json([
            'html' => $this->renderView('mission/_missions_table.html.twig', [
                'pagination' => $pagination
            ])
        ]);
    }


    #[Route('/mes-missions', name: 'app_mes_missions')]
    public function mesMissions(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour voir vos missions.');
            return $this->redirectToRoute('app_login');
        }

        $missions = $entityManager->getRepository(UserMission::class)
            ->findBy(['user' => $user], ['validatedAt' => 'DESC']); // Trier de la plus récente à la plus ancienne

        return $this->render('mission/mes_missions.html.twig', [
            'missions' => $missions,
        ]);
    }

}






