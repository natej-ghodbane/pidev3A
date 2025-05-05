<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Repository\AbonnementRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QrCodeController extends AbstractController
{
    /**
     * @Route("/qr-code/{id}", name="generate_qr_code")
     */
    public function generateQrCode(int $id, AbonnementRepository $abonnementRepository): Response
    {
        // Retrieve the abonnement using its ID
        $abonnement = $abonnementRepository->find($id);

        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement not found');
        }

        // Retrieve the pack details
        $pack = $abonnement->getPack();
        if (!$pack) {
            throw $this->createNotFoundException('Pack not found');
        }

        // Generate the QR code data (you can customize this as needed)
        $data = sprintf(
            "Pack Name: %s\nPrice: %s DT\nDuration: %s months\nDetails: %s",
            $pack->getNomPack(),
            $pack->getPrix(),
            $pack->getDuree(),
            $pack->getAvantages()
        );

        // Generate the QR code
        $qrCode = Builder::create()
            ->writer(new SvgWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevelHigh::HIGH)
            ->size(180)
            ->margin(10)
            ->build();

        // Render the view with the QR code
        return $this->render('abonnement/qr_code.html.twig', [
            'qrCode' => $qrCode->getString(),
            'abonnement' => $abonnement
        ]);
    }
}
