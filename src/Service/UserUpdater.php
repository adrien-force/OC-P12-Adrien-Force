<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class UserUpdater
{
    public function __construct
    (
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface          $validator,
        private UserRepository              $userRepository,
        private TagAwareCacheInterface      $cache,
        private UserRequestParser           $userRequestParser
    )
    {
    }

    public function updateUser(
        int $id,
        Request $request,
    ): void {
        $user = $this->userRepository->find($id);

        if ($user instanceof User) {
            throw new \LogicException('User not found');
        }

        $data = $this->userRequestParser->getUserRequestContent($request);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        }

        if (isset($data['postalCode'])) {
            $user->setPostalCode($data['postalCode']);
            $this->cache->invalidateTags([sprintf("weatherCache%s", $user->getId())]);
        }

        $errors = $this->validator->validate($user);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($user, $errors);
        }

        $this->em->flush();
    }



}
