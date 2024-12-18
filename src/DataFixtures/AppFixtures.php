<?php

namespace App\DataFixtures;

use App\Entity\GardeningTip;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail('user' . $i . '@example.com');
            $user->setPassword('password');
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
