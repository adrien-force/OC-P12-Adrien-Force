<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly final class SignUpService
{
    public function __construct
    (
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface          $validator,
        private UserRepository              $userRepository,
    ){}

    public function handleSignUpRequest(Request $request): JsonResponse
    {
        try {
            $datas = $this->getAndValidateContent($request);
        } catch (\JsonException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($this->checkForEmailExistence($datas["email"])) {
            return new JsonResponse('Email déjà utilisé, veuillez en utiliser une autre', Response::HTTP_CONFLICT);
        }

        try {
            $user = $this->createNewUserFromDataAndValidate($datas);
        } catch (ValidationFailedException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse('Compte utilisateur crée', Response::HTTP_CREATED);

    }

    /**
     * @param Request $request
     * @return array{email: string, password: string, postalCode: string}
     * @throws JsonException
     */
    private function getAndValidateContent(Request $request): array
    {
        if (!$request->getContent()) {
            throw new JsonException('Invalid request', Response::HTTP_BAD_REQUEST);
        }
        $data = json_decode($request->getContent(), true);

        $datas = [
            'email' => $data['email'] ?? null,
            'password' => $data['password'] ?? null,
            'postalCode' => $data['postalCode'] ?? null,
        ];

        if (
            !isset($datas['email']) ||
            !isset($datas['password']) ||
            !isset($datas['postalCode'])
        ) {
            throw new JsonException('Champs manquants, veuillez verifier votre demande.', Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($datas['email']) || !is_string($datas['password']) || !is_string($datas['postalCode'])) {
            throw new JsonException('Données invalides, veuillez verifier votre demande.', Response::HTTP_BAD_REQUEST);
        }

        return $datas;
    }

    private function checkForEmailExistence(string $email): bool
    {
        return $this->userRepository->findOneBy(['email' => $email]) !== null;
    }

    private function createNewUserFromDataAndValidate(array $datas): User
    {
        $user = new User();
        $user->setEmail($datas['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $datas['password']));
        $user->setPostalCode($datas['postalCode']);

        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            throw new ValidationFailedException($user, $errors);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

}
