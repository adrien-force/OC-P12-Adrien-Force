<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenWeatherService
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

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new \JsonException('Erreur lors de la récupération des données de l\'API OpenWeather');
        }

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

    }
}
