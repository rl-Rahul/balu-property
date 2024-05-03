<?php

namespace App\DataFixtures;

use App\Entity\UserPropertyPool;
use App\Entity\User;
use App\Entity\UserIdentity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserFixture
 * @package App\DataFixtures
 */
class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setProperty('testbaluuser@yopmail.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $manager->persist($user);
        $manager->flush();

        $userIdentity = new UserIdentity();
        $userIdentity->setEnabled(true);
        $userIdentity->setUser($user);
        $userIdentity->setFirstName('Test');
        $userIdentity->setLastName('User');
        $userIdentity->setIsPolicyAccepted(true);
        $manager->persist($userIdentity);

        $propertyPool = new UserPropertyPool();
        $propertyPool->setUser($user);
        $propertyPool->setProperty('testbaluuser@yopmail.com');
        $propertyPool->setType('email');
        $manager->persist($propertyPool);

        $propertyPool = new UserPropertyPool();
        $propertyPool->setUser($user);
        $propertyPool->setProperty('testbaluuser1@yopmail.com');
        $propertyPool->setType('email');
        $manager->persist($propertyPool);

        $propertyPool = new UserPropertyPool();
        $propertyPool->setUser($user);
        $propertyPool->setProperty('1234567890');
        $propertyPool->setType('phone');
        $manager->persist($propertyPool);

        $manager->flush();
        
    }
}
