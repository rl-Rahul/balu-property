<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserPermissions
 *
 * @ORM\Entity
 */
class UserPermissions extends Base
{
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isCompany = false;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="userPermissions")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, inversedBy="userPermissions")
     */
    private ?Role $role;

    /**
     * @ORM\ManyToOne(targetEntity=Permission::class, inversedBy="userPermissions")
     */
    private ?Permission $permission;

    /**
     * @return bool|null
     */
    public function getIsCompany(): ?bool
    {
        return $this->isCompany;
    }

    /**
     * @param bool|null $isCompany
     * @return $this
     */
    public function setIsCompany(?bool $isCompany): self
    {
        $this->isCompany = $isCompany;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getUser(): ?UserIdentity
    {
        return $this->user;
    }

    /**
     * @param UserIdentity|null $user
     * @return $this
     */
    public function setUser(?UserIdentity $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Role|null
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * @param Role|null $role
     * @return $this
     */
    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Permission|null
     */
    public function getPermission(): ?Permission
    {
        return $this->permission;
    }

    /**
     * @param Permission|null $permission
     * @return $this
     */
    public function setPermission(?Permission $permission): self
    {
        $this->permission = $permission;

        return $this;
    }
}
