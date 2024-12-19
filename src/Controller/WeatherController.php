<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManager;
use App\Service\AdresseService;
use App\Service\OpenWeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WeatherController extends AbstractController
{

    public function __construct
    (
        private readonly AdresseService $adresseService,
        private readonly OpenWeatherService $openWeatherService,
        private readonly UserManager $userManager
    ){}

    #[Route('/api/meteo', name: 'app_weather')]
    public function getUserWeather(): JsonResponse
    {
        /**
         * @var User $user
         */
        if ($user = $this->getUser()) {
            $coordinates = $this->userManager->getUserCoordinates($user);

            $weather = $this->openWeatherService->getWeatherByCoordinates($coordinates['latitude'], $coordinates['longitude']);

            return new JsonResponse([
                'message' => 'Données météo récupérées avec succès',
                'meteo' => $weather
            ]);
        } else {
            return new JsonResponse(['message' => 'Vous devez vous connecter pour acceder à cette donnée'], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @param string $ville Code postal de la ville
     */
    #[Route('/api/meteo/{ville}', name: 'app_weather_city')]
    public function getCityWeather(string $ville): JsonResponse
    {
        if ($cityCoordonates = $this->adresseService->getCoordinatesByPostalCode($ville))
        {
            $weather = $this->openWeatherService->getWeatherByCoordinates($cityCoordonates['latitude'], $cityCoordonates['longitude']);

            return new JsonResponse([
                'message' => 'Données météo récupérées avec succès',
                'meteo' => $weather
            ]);
        } else {
            return new JsonResponse(['message' => 'Ville non trouvée'], Response::HTTP_NOT_FOUND);
        }


    }

    #[Route('/api/adresse/{postalCode}', name: 'app_adresse')]
    public function testAdresseService($postalCode): JsonResponse
    {
        $data = $this->adresseService->getCoordinatesByPostalCode($postalCode);

        return $this->json($data);
    }


}
