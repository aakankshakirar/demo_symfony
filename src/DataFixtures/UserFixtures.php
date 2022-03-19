<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        // Create an user in User table
        $user = new User();
        $user->setFirstName('admin');
        $user->setLastName('admin');
        $user->setEmail('admin@admin.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin123'));
        $user->setDateCreated(new \DateTime());
        $user->setDateUpdated(new \DateTime());
        $manager->persist($user);
        $manager->flush();
    }
}
