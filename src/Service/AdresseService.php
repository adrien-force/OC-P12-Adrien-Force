<?php

namespace App\Service;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdresseService
{
    private string $apiUrl = 'https://api-adresse.data.gouv.fr/search/';

    public function __construct(private readonly HttpClientInterface $client){}

    public function getCoordinatesByPostalCode(
        string $postalCode,
    ): array
    {
        $response = $this->client->request('GET', $this->apiUrl, [
            'query' => [
                'q' => $postalCode,
                'postCode' => $postalCode
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('Erreur lors de la récupération des données de l\'API Adresse');
        }

        $data = $response->toArray();

        if (empty($data['features'])) {
            throw new Exception('Aucune adresse trouvée pour ce code postal, veuillez vérifier le code postal saisi pour votre utilisateur');
        }

        return [
            'longitude' => $data['features'][0]['geometry']['coordinates'][0],
            'latitude' => $data['features'][0]['geometry']['coordinates'][1]
        ];


    }

}
