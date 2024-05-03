<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PermissionRepository;

/**
 * Permission
 * @ORM\Entity(repositoryClass=PermissionRepository::class)
 */
class Permission extends Base
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45, nullable=false)
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45, nullable=false)
     */
    private string $permissionKey;

    /**
     * @ORM\ManyToMany(targetEntity=Role::class, mappedBy="permission")
     */
    private Collection $roles;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class, inversedBy="userPermission")
     * @ORM\JoinTable(
     *     name="balu_company_user_permission",
     *     joinColumns={
     *          @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *     }
     * )
     */
    private Collection $user;

    /**
     * Permission constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->roles = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->user = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPermissionKey(): ?string
    {
        return $this->permissionKey;
    }

    /**
     * @param string $permissionKey
     * @return $this
     */
    public function setPermissionKey(string $permissionKey): self
    {
        $this->permissionKey = $permissionKey;

        return $this;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @param Role $role
     * @return $this
     */
    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->addPermission($this);
        }

        return $this;
    }

    /**
     * @param Role $role
     * @return $this
     */
    public function removeRole(Role $role): self
    {
        if ($this->roles->removeElement($role)) {
            $role->removePermission($this);
        }

        return $this;
    }

        /**
     * @return Collection|UserIdentity[]
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    /**
     * @param UserIdentity $user
     * @return $this
     */
    public function addUser(UserIdentity $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
        }

        return $this;
    }

    /**
     * @param UserIdentity $user
     * @return $this
     */
    public function removeUser(UserIdentity $user): self
    {
        $this->user->removeElement($user);

        return $this;
    }
}
