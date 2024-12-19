<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user_signup', methods: ['POST'])]
    public function signup
    (
        Request                $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        if (!$request->getContent()) {
            return new JsonResponse('Invalid request', Response::HTTP_BAD_REQUEST);
        }
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['postalCode'])) {
            return new JsonResponse('Invalid request', Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setPostalCode($data['postalCode']);
        $em->persist($user);
        $em->flush();
        return new JsonResponse('Compte utilisateur crée', Response::HTTP_CREATED);

    }

    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete
    (
        User                   $user,
        EntityManagerInterface $em
    ): JsonResponse
    {
        if (!$user) {
            return new JsonResponse('Compte utilisateur non trouvé', Response::HTTP_NOT_FOUND);
        }
        $em->remove($user);
        $em->flush();
        return new JsonResponse('Compte utilisateur supprimé', Response::HTTP_OK);
    }

    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update
    (
        User                   $user,
        Request                $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        if (!$user) {
            return new JsonResponse('Compte utilisateur non trouvé', Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }
        if (isset($data['postalCode'])) {
            $user->setPostalCode($data['postalCode']);
        }
        $em->flush();
        return new JsonResponse('Compte utilisateur mis à jour', Response::HTTP_OK);
    }
}
