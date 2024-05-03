<?php

namespace App\DataFixtures;

use App\Entity\UserPropertyPool;
use App\Entity\User;
use App\Entity\UserIdentity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Entity\Role;

/**
 * Class UserFixture
 * @package App\DataFixtures
 */
class SuperAdminFixture extends Fixture  implements FixtureGroupInterface
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
        $manager->getConnection()->query(sprintf('SET FOREIGN_KEY_CHECKS=0'));
        $user = new User();
        $user->setProperty('baluipswissgmbh@gmail.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, '@dmin123'));
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);
        $manager->flush();

        $userIdentity = new UserIdentity();
        $userIdentity->setEnabled(true);
        $userIdentity->setUser($user);
        $role = $manager->getRepository(Role::class)->findOneBy(['roleKey' => 'admin']);
        $userIdentity->addRole($role);
        $userIdentity->setFirstName('Super');
        $userIdentity->setLastName('Admin');
        $userIdentity->setIsPolicyAccepted(true);
        $manager->persist($userIdentity);

        $propertyPool = new UserPropertyPool();
        $propertyPool->setUser($user);
        $propertyPool->setProperty('baluipswissgmbh@gmail.com');
        $propertyPool->setType('email');
        $manager->persist($propertyPool);


        $manager->flush();
    }
    
    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['admin_group'];
    }
}
