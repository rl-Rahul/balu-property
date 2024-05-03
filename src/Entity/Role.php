<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * Role
 *
 * @ORM\Entity
 */
class Role extends Base
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private string $roleKey;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class, mappedBy="role")
     */
    private Collection $userIdentities;

    /**
     * @ORM\ManyToMany(targetEntity=Permission::class, inversedBy="roles")
     * @ORM\JoinTable(
     *     name="balu_role_permission",
     *     joinColumns={
     *          @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     *     }
     * )
     */
    private Collection $permission;

    /**
     * @ORM\OneToMany(targetEntity=UserPermissions::class, mappedBy="role")
     */
    private Collection $userPermissions;

    /**
     * @ORM\OneToMany(targetEntity=PropertyUser::class, mappedBy="role")
     */
    private Collection $propertyUsers;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $sortOrder;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="role_id")
     */
    private Collection $payments;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $nameDe;

    /**
     * @ORM\OneToMany(targetEntity=MessageReadUser::class, mappedBy="role")
     */
    private Collection $messageReadUsers;

    /**
     * Role constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->userIdentities = new ArrayCollection();
        $this->permission = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->propertyUsers = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->messageReadUsers = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRoleKey(): ?string
    {
        return $this->roleKey;
    }

    /**
     * @param string|null $roleKey
     * @return $this
     */
    public function setRoleKey(?string $roleKey): self
    {
        $this->roleKey = $roleKey;

        return $this;
    }

    /**
     * @return Collection|UserIdentity[]
     */
    public function getUserIdentities(): Collection
    {
        return $this->userIdentities;
    }

    /**
     * @param UserIdentity $userIdentity
     * @return $this
     */
    public function addUserIdentity(UserIdentity $userIdentity): self
    {
        if (!$this->userIdentities->contains($userIdentity)) {
            $this->userIdentities[] = $userIdentity;
            $userIdentity->addRole($this);
        }

        return $this;
    }

    /**
     * @param UserIdentity $userIdentity
     * @return $this
     */
    public function removeUserIdentity(UserIdentity $userIdentity): self
    {
        if ($this->userIdentities->removeElement($userIdentity)) {
            $userIdentity->removeRole($this);
        }

        return $this;
    }

    /**
     * @return Collection|Permission[]
     */
    public function getPermission(): Collection
    {
        return $this->permission;
    }

    /**
     * @param Permission $permission
     * @return $this
     */
    public function addPermission(Permission $permission): self
    {
        if (!$this->permission->contains($permission)) {
            $this->permission[] = $permission;
        }

        return $this;
    }

    /**
     * @param Permission $permission
     * @return $this
     */
    public function removePermission(Permission $permission): self
    {
        $this->permission->removeElement($permission);

        return $this;
    }

    /**
     * @return Collection|UserPermissions[]
     */
    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    /**
     * @param UserPermissions $userPermission
     * @return $this
     */
    public function addUserPermission(UserPermissions $userPermission): self
    {
        if (!$this->userPermissions->contains($userPermission)) {
            $this->userPermissions[] = $userPermission;
            $userPermission->setRole($this);
        }

        return $this;
    }

    /**
     * @param UserPermissions $userPermission
     * @return $this
     */
    public function removeUserPermission(UserPermissions $userPermission): self
    {
        if ($this->userPermissions->removeElement($userPermission)) {
            // set the owning side to null (unless already changed)
            if ($userPermission->getRole() === $this) {
                $userPermission->setRole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|PropertyUser[]
     */
    public function getPropertyUsers(): Collection
    {
        return $this->propertyUsers;
    }

    /**
     * @param PropertyUser $propertyUser
     * @return $this
     */
    public function addPropertyUser(PropertyUser $propertyUser): self
    {
        if (!$this->propertyUsers->contains($propertyUser)) {
            $this->propertyUsers[] = $propertyUser;
            $propertyUser->setRole($this);
        }

        return $this;
    }

    /**
     * @param PropertyUser $propertyUser
     * @return $this
     */
    public function removePropertyUser(PropertyUser $propertyUser): self
    {
        if ($this->propertyUsers->removeElement($propertyUser)) {
            // set the owning side to null (unless already changed)
            if ($propertyUser->getRole() === $this) {
                $propertyUser->setRole(null);
            }
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    /**
     * @param Payment $payment
     * @return $this
     */
    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setRole($this);
        }

        return $this;
    }

    /**
     * @param Payment $payment
     * @return $this
     */
    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getRole() === $this) {
                $payment->setRole(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    /**
     * @param string|null $nameDe
     * @return $this
     */
    public function setNameDe(?string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }

    /**
     * @return Collection<int, MessageReadUser>
     */
    public function getMessageReadUsers(): Collection
    {
        return $this->messageReadUsers;
    }

    public function addMessageReadUser(MessageReadUser $messageReadUser): self
    {
        if (!$this->messageReadUsers->contains($messageReadUser)) {
            $this->messageReadUsers[] = $messageReadUser;
            $messageReadUser->setRole($this);
        }

        return $this;
    }

    public function removeMessageReadUser(MessageReadUser $messageReadUser): self
    {
        if ($this->messageReadUsers->removeElement($messageReadUser)) {
            // set the owning side to null (unless already changed)
            if ($messageReadUser->getRole() === $this) {
                $messageReadUser->setRole(null);
            }
        }

        return $this;
    }
}
