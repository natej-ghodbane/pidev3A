<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->apiKey = '2ffd064fbe720a8406a88497acc58268'; // 🔥 Replace with your real OpenWeather key
    }

    public function getWeather(float $latitude, float $longitude): ?array
    {
        $url = sprintf(
            'https://api.openweathermap.org/data/2.5/weather?lat=%f&lon=%f&appid=%s&units=metric&lang=fr',
            $latitude,
            $longitude,
            $this->apiKey
        );

        try {
            $response = $this->client->request('GET', $url);
            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }
}
