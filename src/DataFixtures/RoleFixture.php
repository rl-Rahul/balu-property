<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class RoleFixtures
 * @package App\DataFixtures
 */
class RoleFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $administrator = $manager->getRepository(Role::class)->findOneBy(['roleKey' => 'property_administrator']);
        if ($administrator instanceof Role) {
            $administrator->setRoleKey('property_admin');
            $manager->flush();
        }
        $role = new Role();
        $role->setRoleKey('janitor');
        $role->setName('Janitor');
        $role->setNameDe('Hauswartung');
        $role->setSortOrder(3);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('object_owner');
        $role->setName('Object owner');
        $role->setNameDe('Stockwerkeigentümer/in');
        $role->setSortOrder(6);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('owner');
        $role->setName('Owner');
        $role->setNameDe('Eigentümer/in');
        $role->setSortOrder(1);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('property_admin');
        $role->setName('Property Administrator');
        $role->setNameDe('Verwaltung');
        $role->setSortOrder(2);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('company_user');
        $role->setName('Company User');
        $role->setNameDe('Unternehmen / Handwerksbetrieb');
        $role->setSortOrder(5);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('tenant');
        $role->setName('Tenant');
        $role->setNameDe('Mieter/in');
        $role->setSortOrder(7);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('company');
        $role->setName('Company');
        $role->setNameDe('Unternehmen / Handwerksbetrieb');
        $role->setSortOrder(4);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('admin');
        $role->setName('Admin');
        $role->setNameDe('Verwaltung');
        $role->setSortOrder(0);
        $manager->persist($role);
        $manager->flush();

        $role = new Role();
        $role->setRoleKey('guest');
        $role->setName('Guest');
        $role->setNameDe('Gast');
        $role->setSortOrder(8);
        $manager->persist($role);
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['role_group'];
    }
}