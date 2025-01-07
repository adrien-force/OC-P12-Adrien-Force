<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Attributes as OA;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user_signup', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Cree un compte utilisateur',
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password', 'postalCode'],
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'postalCode', type: 'string'),
            ],
            type: 'object'
        )
    )]
    public function signup
    (
        Request                $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository         $userRepository,
        ValidatorInterface     $validator

    ): JsonResponse
    {
        if (!$request->getContent()) {
            return new JsonResponse('Invalid request', Response::HTTP_BAD_REQUEST);
        }
        $data = json_decode($request->getContent(), true);

        if ($userRepository->findOneBy(['email' => $data['email']]) ) { //FIXME: Pas ouf niveau securité, à revoir
            return new JsonResponse('Email déjà utilisé, veuillez en utiliser une autre', Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        if ( $data['password'] !== '' ) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        } else {
            $user->setPassword($data['password']);
        }
        $user->setRoles(['ROLE_USER']);
        $user->setPostalCode($data['postalCode']);

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            throw new ValidationFailedException($user, $errors);
        }

        $em->persist($user);
        $em->flush();
        return new JsonResponse('Compte utilisateur crée', Response::HTTP_CREATED);

    }

    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'utilisateur',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete
    (
        int                   $id,
        EntityManagerInterface $em,
        UserRepository         $userRepository
    ): JsonResponse
    {
        if (!$id) {
            return new JsonResponse('Veuillez renseigner un ID', Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse('Compte utilisateur non trouvé', Response::HTTP_NOT_FOUND);
        }

        $em->remove($user);
        $em->flush();
        return new JsonResponse('Compte utilisateur supprimé', Response::HTTP_OK);
    }

    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'utilisateur',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[OA\RequestBody(
        description: 'Modifier un compte utilisateur',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'postalCode', type: 'string'),
            ],
            type: 'object'
        )
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update
    (
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        TagAwareCacheInterface $cache,
        UserRepository         $userRepository,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse('Compte utilisateur non trouvé', Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            if ( $data['password'] !== '' ) {
                $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
            } else {
                $user->setPassword($data['password']);
            }
        }
        if (isset($data['postalCode'])) {
            $user->setPostalCode($data['postalCode']);
            $cache->invalidateTags([sprintf("weatherCache%s", $user->getId())]);
        }
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            throw new ValidationFailedException($user, $errors);
        }

        $em->flush();
        return new JsonResponse('Compte utilisateur mis à jour', Response::HTTP_OK);
    }
}
