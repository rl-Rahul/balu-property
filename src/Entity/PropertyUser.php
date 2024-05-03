<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\PropertyUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PropertyUserRepository::class)
 */
class PropertyUser extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=Property::class, inversedBy="propertyUsers")
     */
    private ?Property $property;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, inversedBy="propertyUsers")
     */
    private ?Role $role;

    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="propertyUsers")
     */
    private ?Apartment $object;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isActive;

    /**
     * @ORM\ManyToOne(targetEntity=ObjectContracts::class, inversedBy="propertyUsers")
     */
    private ?ObjectContracts $contract;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isPinnedUser;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isJanitor = false;

    /**
     * @return Property|null
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }

    /**
     * @param Property|null $property
     * @return $this
     */
    public function setProperty(?Property $property): self
    {
        $this->property = $property;

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
     * @return Apartment|null
     */
    public function getObject(): ?Apartment
    {
        return $this->object;
    }

    /**
     * @param Apartment|null $object
     * @return $this
     */
    public function setObject(?Apartment $object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
    
    /**
     * 
     * @return ObjectContracts|null
     */
    public function getContract(): ?ObjectContracts
    {
        return $this->contract;
    }
    
    /**
     * 
     * @param ObjectContracts|null $contract
     * @return self
     */
    public function setContract(?ObjectContracts $contract): self
    {
        $this->contract = $contract;

        return $this;
    }
    
    /**
     * 
     * @return UserIdentity|null
     */
    public function getUser(): ?UserIdentity
    {
        return $this->user;
    }
    
    /**
     * 
     * @param UserIdentity|null $user
     * @return self
     */
    public function setUser(?UserIdentity $user): self
    {
        $this->user = $user;

        return $this;
    }
    
    /**
     * 
     * @return bool|null
     */
    public function getIsPinnedUser(): ?bool
    {
        return $this->isPinnedUser;
    }
    
    /**
     * 
     * @param bool|null $isPinnedUser
     * @return self
     */
    public function setIsPinnedUser(?bool $isPinnedUser): self
    {
        $this->isPinnedUser = $isPinnedUser;

        return $this;
    }

    public function isIsJanitor(): ?bool
    {
        return $this->isJanitor;
    }

    public function setIsJanitor(bool $isJanitor): self
    {
        $this->isJanitor = $isJanitor;

        return $this;
    }
}
