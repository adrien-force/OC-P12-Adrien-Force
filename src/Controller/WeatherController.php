<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AdresseService;
use App\Service\OpenWeatherService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Attributes as OA;


class WeatherController extends AbstractController
{
    public function __construct
    (
        private readonly AdresseService $adresseService,
        private readonly OpenWeatherService $openWeatherService,
        private readonly TagAwareCacheInterface $cache,
    ){}

    #[Route('/api/meteo', name: 'app_weather', methods: ['GET'])]
    public function getUserWeather(): JsonResponse
    {
        /**
         * @var User $user
         */
        if ($user = $this->getUser()) {
            try {
                $coordinates = $this->adresseService->getCoordinatesByPostalCode($user->getPostalCode());
            } catch (Exception $e){
                return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            $idCache = sprintf("weather_user_%s", $user->getId());

            try {
                $weather = $this->cache->get($idCache, function (ItemInterface $item) use ($coordinates, $user) {
                    $item->tag(sprintf("weatherCache%s", $user->getId()));
                    $item->expiresAfter(3600);
                    return $this->openWeatherService->getWeatherByCoordinates($coordinates['latitude'], $coordinates['longitude']);
                });
            } catch (\Throwable $e) {
                return new JsonResponse("Erreur de récupération des données météo", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new JsonResponse([
                'message' => sprintf('Données météo récupérées avec succès pour le code postal: %s', $user->getPostalCode()),
                'meteo' => $weather['current'],
            ], status: Response::HTTP_OK);
        }

        return new JsonResponse(['message' => 'Vous devez vous connecter pour acceder à cette donnée'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param string $ville Code postal de la ville
     */
    #[Route('/api/meteo/{ville}', name: 'app_weather_city', methods: ['GET'])]
    #[OA\Parameter(
        name: 'ville',
        description: 'Code postal de la ville',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    public function getCityWeather(string $ville): JsonResponse
    {
        if (!is_numeric($ville) || strlen($ville) !== 5) {
            return new JsonResponse(['message' => 'Code postal invalide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $cityCoordonates = $this->adresseService->getCoordinatesByPostalCode($ville);
            $idCache = sprintf("weather_city_%s", $ville);

            $weather = $this->cache->get($idCache, function(ItemInterface $item) use ($cityCoordonates, $ville) {
                $item->tag(sprintf("weatherCache_city%s", $ville));
                $item->expiresAfter(3600);
                return $this->openWeatherService->getWeatherByCoordinates($cityCoordonates['latitude'], $cityCoordonates['longitude']);
            });

            return new JsonResponse([
                'message' => sprintf("Données météo récupérées avec succès pour le code postal: %s", $ville),
                'meteo' => $weather
            ], Response::HTTP_OK);
        } catch (\Exception) {
            return new JsonResponse(['message' => 'Ville non trouvée'], Response::HTTP_NOT_FOUND);
        }

    }

}
