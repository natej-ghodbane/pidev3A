<?php

namespace App\Controller;

use App\Entity\Partenaire;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContratController extends AbstractController
{
#[Route('/partenaire/{id}/contrat', name: 'partenaire_contrat')]
public function contrat(Pdf $knpSnappyPdf, Partenaire $partenaire): Response
{
// Get the path of your logo (adjust the path as necessary)
$logoPath = $this->getParameter('kernel.project_dir') . '/public/images/logo.png';

// Convert the image to base64
$logoBase64 = base64_encode(file_get_contents($logoPath));

// Prepare the HTML content for the PDF
$html = $this->renderView('partenaire/pdf.html.twig', [
'partenaire' => $partenaire,
'paragraphe' => "Ce partenariat s'inscrit dans une volonté commune de coopération pour atteindre nos objectifs mutuels.",
'logo_base64' => $logoBase64, // Pass the base64 logo data to the template
]);

// Set the PDF filename
$filename = 'contrat_partenaire_' . $partenaire->getId();

// Return the PDF in the response
return new Response(
$knpSnappyPdf->getOutputFromHtml($html),
200,
[
'Content-Type' => 'application/pdf',
'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"'
]
);
}
}
