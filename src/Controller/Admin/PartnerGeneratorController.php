<?php

namespace App\Controller\Admin;

use App\Form\GeneratePartnersType;
use App\Service\GeminiPart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PartnerGeneratorController extends AbstractController
{
    #[Route('/admin/generate-partners', name: 'admin_generate_partners')]
    public function generatePartners(
        Request $request,
        GeminiPart $geminiPart
    ): Response
    {
        $form = $this->createForm(GeneratePartnersType::class);
        $form->handleRequest($request);

        $partnersData = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $partnersData = $geminiPart->generatePartners($data['place'], $data['number']);

            $this->addFlash('success', count($partnersData) . ' partner suggestions generated successfully.');
        }

        return $this->render('partenaire/generate_partners.html.twig', [
            'form' => $form->createView(),
            'partners' => $partnersData,
        ]);
    }
}
