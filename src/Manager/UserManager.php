<?php
//
//namespace App\Manager;
//
//use App\Entity\User;
//use App\Service\AdresseService;
//use Exception;
//
//readonly class UserManager
//{
//    public function __construct(private AdresseService $adresseService){}
//
//    public function getUserCoordinates(User $user): array
//    {
//        if ($coordinates = $user->getCityCoordinates()) {
//            return $coordinates;
//        } else {
//            $this->updateUserCoordinates($user);
//            return $user->getCityCoordinates();
//        }
//    }
//
//    public function updateUserCoordinates(User $user): array
//    {
//        $postalCode = $user->getPostalCode();
//        $coordinates = $this->adresseService->getCoordinatesByPostalCode($postalCode);
//        $user->setCityCoordinates($coordinates);
//
//        return $coordinates;
//    }
//
//}
