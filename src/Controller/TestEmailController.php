<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TestEmailController extends AbstractController
{
    private $client;
    private $apiKey = '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';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/send-mail-api', name: 'send_mail_api')]
    public function sendMailApi(): Response
    {
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
                        'email' => 'medyassinehaji87@gmail.com',
                        'name' => 'Med Yassine'
                    ]],
                    'subject' => 'Test Email from TrekSwap',
                    'htmlContent' => '<p>This is a test email from TrekSwap</p>',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode === 201) {
                return $this->json([
                    'status' => 'success',
                    'message' => 'Email sent successfully!'
                ]);
            } else {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Failed to send email',
                    'details' => json_decode($content, true)
                ], $statusCode);
            }
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred while sending the email',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
