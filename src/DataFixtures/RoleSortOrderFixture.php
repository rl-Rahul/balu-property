<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class RoleSortOrderFixture
 * @package App\DataFixtures
 */
class RoleSortOrderFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $sortOrders = [
            'owner' => 1,
            'property_admin' => 2,
            'janitor' => 3,
            'company' => 4,
            'company_user' => 5,
            'object_owner' => 6,
            'tenant' => 7,
            'admin' => 0
        ];
        $roles = $manager->getRepository(Role::class)->findAll();
        foreach ($roles as $role) {
            $role->setSortOrder($sortOrders[$role->getRoleKey()]);
            $manager->flush();
        }
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['role_sort_order_group'];
    }
}
