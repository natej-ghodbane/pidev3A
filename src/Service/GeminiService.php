<?php

namespace App\Service;

class GeminiService
{
    private string $apiKey = 'AIzaSyCQ4y0D1vtWSq3nhZqtSS-9J5JMtAS00j0'; // ✨ Put your API Key here directly
    private string $endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent';

    public function __construct()
    {
        // No parameters needed anymore
    }

    public function generateContent(string $prompt): string
    {
        $url = $this->endpoint . '?key=' . $this->apiKey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            return 'Erreur lors de la génération du plan.';
        }

        $response = json_decode($result, true);

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Pas de réponse générée.';
    }
}
