<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\Permission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Class RoleFixtures
 * @package App\DataFixtures
 */
class RolePermissionFixture extends Fixture implements FixtureGroupInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $rolePermissions = [
            'tenant' => ['VIEW_PROPERTY', 'VIEW_APARTMENT'],
            'object_owner' => ['VIEW_PROPERTY', 'VIEW_APARTMENT'],
            'owner' => ['CREATE_PROPERTY', 'EDIT_PROPERTY', 'DELETE_PROPERTY', 'MANAGE_PROPERTY', 'CREATE_APARTMENT',
                'EDIT_APARTMENT', 'DELETE_APARTMENT', 'MANAGE_APARTMENT', 'VIEW_APARTMENT', 'VIEW_PROPERTY'],
            'property_admin' => ['CREATE_PROPERTY', 'EDIT_PROPERTY', 'DELETE_PROPERTY', 'MANAGE_PROPERTY', 'CREATE_APARTMENT',
                'EDIT_APARTMENT', 'DELETE_APARTMENT', 'MANAGE_APARTMENT', 'VIEW_APARTMENT', 'VIEW_PROPERTY'],
            'janitor' => ['MANAGE_PROPERTY', 'MANAGE_APARTMENT'],
            'company' => ['MANAGE_DAMAGE'],
            'company_user' => ['MANAGE_DAMAGE', 'VIEW_DAMAGE'],
        ];

        $roles = $manager->getRepository(Role::class)->findAll();
        if (!empty($roles)) {
            foreach ($roles as $role) {
                if ($role instanceof Role) {
                    if (isset($rolePermissions[$role->getRoleKey()])) {
                        foreach ($rolePermissions[$role->getRoleKey()] as $permission) {
                            $permission = $manager->getRepository(Permission::class)->findOneBy(['permissionKey' => $permission]);
                            if ($permission instanceof Permission) {
                                $role->addPermission($permission);
                                $manager->flush();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['role_permission_group'];
    }
}