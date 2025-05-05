<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Twig\Environment;
use App\Repository\ReclamationRepository;
use App\Repository\ReponseRepository;


final class PdfController extends AbstractController{
    #[Route('/pdf', name: 'app_pdf')]
    public function index(): Response
    {
        return $this->render('pdf/index.html.twig');
    }

    #[Route('/generate-pdf', name: 'generatePDF')]
    public function generatePdf(DompdfFactoryInterface $dompdfFactory, Environment $twig, ReclamationRepository $reclamationRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
        
        // Calculer les statistiques (repris de ReclamationController)
        $stats = [
            'total' => count($reclamations),
            'enCours' => 0,
            'resolues' => 0,
            'rejetees' => 0,
            'parType' => [],
            'parMois' => []
        ];

        $moisActuel = new \DateTime();
        $moisActuel->modify('first day of this month');
        $stats['moisActuel'] = 0;

        foreach ($reclamations as $reclamation) {
            // Compter par état
            switch ($reclamation->getEtatRec()) {
                case 'En cours':
                    $stats['enCours']++;
                    break;
                case 'Résolue':
                    $stats['resolues']++;
                    break;
                case 'Rejetée':
                    $stats['rejetees']++;
                    break;
            }

            // Compter par type
            $type = $reclamation->getTypeRec();
            if (!isset($stats['parType'][$type])) {
                $stats['parType'][$type] = 0;
            }
            $stats['parType'][$type]++;

            // Compter par mois
            $moisReclamation = $reclamation->getDateRec()->format('Y-m');
            if (!isset($stats['parMois'][$moisReclamation])) {
                $stats['parMois'][$moisReclamation] = 0;
            }
            $stats['parMois'][$moisReclamation]++;

            // Compter pour le mois actuel
            if ($reclamation->getDateRec() >= $moisActuel) {
                $stats['moisActuel']++;
            }
        }

        // Trier les statistiques par mois
        krsort($stats['parMois']);
        $stats['parMois'] = array_slice($stats['parMois'], 0, 6, true);

        // Calculer le taux de résolution
        $stats['resolutionRate'] = $stats['total'] > 0 
            ? round(($stats['resolues'] / $stats['total']) * 100) 
            : 0;
        
        // Get the server URL for the absolute URLs
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $baseUrl = $request->getSchemeAndHttpHost();
        
        $html = $twig->render('pdf/reclamations.html.twig', [
            'reclamations' => $reclamations,
            'stats' => $stats,
        ]);

        // Create the Dompdf instance with proper options
        $options = [
            'defaultFont' => 'Helvetica',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'fontDir' => sys_get_temp_dir(),
            'fontCache' => sys_get_temp_dir(),
            'chroot' => sys_get_temp_dir(),
        ];
        
        $dompdf = $dompdfFactory->create($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="reclamations.pdf"',
            ]
        );
    }
    
    #[Route('/generate-reponses-pdf', name: 'generateReponsesPDF')]
    public function generateReponsesPdf(DompdfFactoryInterface $dompdfFactory, Environment $twig, ReponseRepository $reponseRepository): Response
    {
        $reponses = $reponseRepository->findAll();
        
        $html = $twig->render('pdf/reponses.html.twig', [
            'reponses' => $reponses,
        ]);

        // Create the Dompdf instance with optimized options for A4 sizing
        $options = [
            'defaultFont' => 'Helvetica',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'fontDir' => sys_get_temp_dir(),
            'fontCache' => sys_get_temp_dir(),
            'chroot' => sys_get_temp_dir(),
            'dpi' => 96,
            'defaultPaperSize' => 'A4',
            'defaultPaperOrientation' => 'portrait',
            'isFontSubsettingEnabled' => true,
        ];
        
        $dompdf = $dompdfFactory->create($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="reponses.pdf"',
            ]
        );
    }
}
