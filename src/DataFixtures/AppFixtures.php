<?php

namespace App\DataFixtures;

use App\Entity\GardeningTip;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{


    private UserPasswordHasherInterface $UserPasswordHasherInterface;
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->UserPasswordHasherInterface = $userPasswordHasher;
    }
    public function load(
        ObjectManager $manager,

    ): void
    {
        $admin = new User();
        $admin->setEmail('admin@api.com');
        $admin->setPassword($this->UserPasswordHasherInterface->hashPassword($admin, 'password'));
        $admin->setPostalCode('21000');
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail('user' . $i . '@example.com');
            $user->setPassword($this->UserPasswordHasherInterface->hashPassword($user, 'password'));
            $user->setPostalCode('21000');

            $gardeningTip = new GardeningTip();
            $gardeningTip->setContent('Gardening tip content ' . $i);
            $gardeningTip->setCreationDate(new \DateTimeImmutable());
            $manager->persist($user);
            $manager->persist($gardeningTip);
        }

        $manager->flush();
    }
}
