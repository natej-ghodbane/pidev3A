<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiPart
{
    private $client;
    private $apiKey = 'AIzaSyCQ4y0D1vtWSq3nhZqtSS-9J5JMtAS00j0'; // ✅ your real API key

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function generatePartners(string $place, int $number): array
    {
        $prompt = "Generate exactly $number fictional companies located in $place. 
The companies should only be fun or leisure places for tourists, like:
- Restaurants
- Cafés
- Hotels
- Maisons d'hôtes (guest houses)

DO NOT include travel agencies, insurance, or any non-touristic business.

Return ONLY a pure JSON array, without any extra explanation or comments.
Each partner should have:
- name
- address
- email
- type (either 'restaurant', 'café', 'hotel', or 'maison d'hôte').";
        // ✅ Correct URL using v1
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent?key=' . $this->apiKey;

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]
        ]);

        $content = $response->getContent(false);
        $data = json_decode($content, true);

        // If Gemini returns error
        if (isset($data['error'])) {
            throw new \Exception('Gemini API Error: ' . $data['error']['message']);
        }

        // If Gemini returns no candidates
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return [];
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'];

        $partners = json_decode($text, true);

        if (!is_array($partners)) {
            return [];
        }

        return $partners;
    }
}
