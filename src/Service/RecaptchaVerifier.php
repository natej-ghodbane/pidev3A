<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    private $client;
    private $secretKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->secretKey = $_ENV['EWZ_RECAPTCHA_SECRET'];
    }

    public function verify(string $recaptchaResponse): bool
    {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $recaptchaResponse,
            ]
        ]);

        $data = $response->toArray(false); // false pour éviter erreur si réponse mauvaise

        return isset($data['success']) && $data['success'] === true;
    }
}
