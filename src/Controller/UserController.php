<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SignUpService;
use App\Service\UserUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Attributes as OA;

class UserController extends AbstractController
{
    public function __construct
    (
        private readonly SignUpService $signUpService,
        private readonly UserUpdater $userUpdater,
    ){}

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
    public function signup(Request $request): JsonResponse
    {
        return $this->signUpService->handleSignUpRequest($request);

    }

    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'utilisateur',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[IsGranted('ROLE_ADMIN', message: 'Seul un administrateur peut supprimer un compte utilisateur', statusCode: Response::HTTP_FORBIDDEN)]
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
    #[IsGranted('ROLE_ADMIN', message: 'Seul un administrateur peut modifier un compte utilisateur', statusCode: Response::HTTP_FORBIDDEN)]
    public function update
    (
        int                    $id,
        Request                $request,
    ): JsonResponse
    {
        try {
            $this->userUpdater->updateUser($id, $request);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse('Compte utilisateur mis à jour', Response::HTTP_OK);
    }

    #[Route('/api/user/{id}', name: 'app_user_get', methods: ['GET'])]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'utilisateur',
        in: 'path',
        required: true,
    )]
    #[OA\Schema(type: 'integer')]
    #[IsGranted('ROLE_ADMIN', message: 'Seul un administrateur peut accéder à un compte utilisateur', statusCode: Response::HTTP_FORBIDDEN)]
    public function get
    (
        int           $id,
        UserRepository $userRepository,
        SerializerInterface    $serializer,
    ): JsonResponse
    {
        /** @var User $user */
        $user = $userRepository->find($id);
        $user = $serializer->serialize($user, 'json', ['groups' => 'user:read']);

        if (!$user) {
            return new JsonResponse('Compte utilisateur non trouvé', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($user, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'app_user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Seul un administrateur peut accéder à la liste des utilisateurs', statusCode: Response::HTTP_FORBIDDEN)]
    public function list
    (
        UserRepository $userRepository,
        SerializerInterface    $serializer,

    ): JsonResponse
    {
        $users = $userRepository->findAll();
        $users = $serializer->serialize($users, 'json', ['groups' => 'user:read']);

        return new JsonResponse($users, Response::HTTP_OK, [], true);
    }
}
