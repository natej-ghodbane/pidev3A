<?php

namespace App\Controller;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserAbonnement;
use App\Repository\PackRepository;
use App\Repository\AbonnementRepository;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\WhatsAppService;

class PaymentController extends AbstractController
{
    #[Route('/send-whatsapp', name: 'send_whatsapp')]
    public function send(WhatsAppService $whatsApp): Response
    {
        // Call the sendMessage method with the recipient number and message
        $whatsApp->sendMessage('whatsapp:+21624354335', 'Hello from Symfony via WhatsApp!');

        return new Response('WhatsApp message sent!');
    }

    #[Route('/payment', name: 'payment')]
    public function index(): Response
    {
        return $this->render('payment/index.html.twig', [
            'controller_name' => 'PaymentController',
        ]);
    }

    #[Route('/checkoutqr/{id}', name: 'checkoutqr')]
    public function checkoutqr(
        $id, 
        string $stripeSK, 
        AbonnementRepository $AbonRepository, 
        PackRepository $packRepository, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        // Set Stripe API key
        Stripe::setApiKey($stripeSK);
    
        // Fetch the abonnement (subscription) by ID
        $Abon = $AbonRepository->find($id);
        if (!$Abon) {
            throw $this->createNotFoundException('Abonnement not found.');
        }
    
        // Retrieve the pack price using the repository method
        $unitAmount = $packRepository->getPriceById($Abon->getIdPack());
    
        // Convert to cents
        $unitAmount = $unitAmount * 100; // Assuming price is in DT, convert to cents
    
        // Create a Stripe checkout session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Payment subscription',
                    ],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('success_url', ['id_abonnement' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('cancel_url', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    
        // Get the URL from the session
        $checkoutUrl = $session->url;
    
        // Generate the QR Code for the checkout URL
        $qrCode = Builder::create()
        ->writer(new SvgWriter())
        ->data($checkoutUrl)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
        ->size(300)
        ->margin(10)
        ->build();
    
        // Render the view with the QR Code
        return $this->render('abonnements/qr_code.html.twig', [
            'qrCode' => $qrCode->getString(), // get the SVG string
            'abonnements' => $Abon,
            'checkoutUrl' => $checkoutUrl, // Pass URL for "Buy Now" button
        ]);
    }

    #[Route('/checkout/{id}', name: 'checkout')]
    public function checkout(
        $id, 
        string $stripeSK, 
        AbonnementRepository $AbonRepository, 
        PackRepository $packRepository, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        // Set Stripe API key
        Stripe::setApiKey($stripeSK);

        // Fetch the abonnement (subscription) by ID
        $Abon = $AbonRepository->find($id);
        if (!$Abon) {
            throw $this->createNotFoundException('Abonnement not found.');
        }

        // Retrieve the pack price using the repository method
        $unitAmount = $packRepository->getPriceById($Abon->getIdPack());

        // Ensure the price is converted to cents (Stripe uses the smallest currency unit)
        $unitAmount = $unitAmount * 100; // Assuming price is in DT, convert to cents

        // Create a Stripe checkout session
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'usd',  
                    'product_data' => [
                        'name' => 'Payment subscription',
                    ],
                    'unit_amount'  => $unitAmount, 
                ],
                'quantity'   => 1,
            ]],
            'mode'                 => 'payment',
            'success_url'          => $this->generateUrl('success_url', ['id_abonnement' => $id], UrlGeneratorInterface::ABSOLUTE_URL), // Add id_abonnement in the success URL
            'cancel_url'           => $this->generateUrl('cancel_url', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // Redirect user to Stripe checkout page
        return $this->redirect($session->url, 303);
    }

    #[Route('/success-url', name: 'success_url')]
    public function successUrl(Request $request, EntityManagerInterface $em, WhatsAppService $whatsApp): Response
    {
        // Retrieve the abonnement_id from the query parameter
        $abonnementId = $request->query->get('id_abonnement');

        // Check if abonnementId is missing
        if (!$abonnementId) {
            throw $this->createNotFoundException('Abonnement ID is missing.');
        }

        // Get the current logged-in user
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to complete this action.');
        }

        // Create a new UserAbonnement entity and set the values
        $userAbonnement = new UserAbonnement();
        $userAbonnement->setIdUser($user->getId());        // Set the current user's ID
        $userAbonnement->setIdAbonnement($abonnementId);   // Set the abonnement ID from the query parameter

        // Persist the UserAbonnement record to the database
        $em->persist($userAbonnement);
        $em->flush();

        // Optionally send WhatsApp message (commented out)
        $to = 'whatsapp:+21624354335'; 
        $message = "✅ Paiement réussi ! Merci pour votre abonnement.";
        $whatsApp->sendMessage($to, $message);

        // Render the success page
        return $this->render('payment/success.html.twig');
    }

    #[Route('/cancel-url', name: 'cancel_url')]
    public function cancelUrl(AbonnementRepository $abonnementRepository, PackRepository $packRepository): Response
    {
        $abonnements = $abonnementRepository->findAll();

        return $this->render('payment/cancel.html.twig', [
            'abonnements' => $abonnements,
            'packRepository' => $packRepository,
        ]);
    }
}