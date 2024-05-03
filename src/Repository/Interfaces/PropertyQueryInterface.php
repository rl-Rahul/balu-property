<?php

namespace App\Repository\Interfaces;

use App\Entity\Role;
use App\Entity\UserIdentity;

/**
 * Interface PropertyQueryInterface
 * @package App\Repository\Interfaces
 */
interface PropertyQueryInterface
{
    /**
     * @param UserIdentity|null $user
     * @param array $params
     * @param Role|null $role
     * @return array
     */
    public function getProperties(UserIdentity $user = null, array $params = [], ?Role $role = null): array;

    /**
     * @param UserIdentity|null $user
     * @param Role|null $role
     * @param bool $isDashboard
     * @return int
     */
    public function countProperties(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int;

    /**
     * @param UserIdentity|null $user
     * @param Role|null $role
     * @param bool $isDashboard
     * @return int
     */
    public function countObjects(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int;

    /**
     * @param UserIdentity|null $user
     * @param Role|null $role
     * @param bool $isDashboard
     * @return int
     */
    public function countTenants(UserIdentity $user = null, ?Role $role = null, bool $isDashboard = false): ?int;
}
