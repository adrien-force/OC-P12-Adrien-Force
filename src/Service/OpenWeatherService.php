<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenWeatherService
{
    private $apiKey;
    private string $url = 'https://api.openweathermap.org/data/3.0/onecall';

    public function __construct(private readonly HttpClientInterface $client, string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getWeatherByCoordinates(
        float $latitude,
        float $longitude,
    ): array
    {

        $response = $this->client->request('GET', $this->url, [
            'query' => [
                'lat' => $latitude,
                'lon' => $longitude,
                'appid' => $this->apiKey,
                'date' => (new \DateTimeImmutable())->format('YYYY-MM-DD')
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erreur lors de la récupération des données de l\'API OpenWeather');
        }

        return $response->toArray();

    }
}
