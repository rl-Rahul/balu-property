<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\UserPropertyPoolRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserPropertyPoolRepository::class)
 */
class UserPropertyPool extends Base
{
    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private ?string $property;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $type;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $revoked;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isPrimary;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isVerified;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="authProperty")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user;

    public function __construct()
    {
        parent::__construct();
        $this->revoked = false;
        $this->isVerified = false;
        $this->isPrimary = true;
    }

    /**
     * @return string|null
     */
    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * @param string $property
     * @return $this
     */
    public function setProperty(string $property): self
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRevoked(): ?bool
    {
        return $this->revoked;
    }

    /**
     * @param bool $revoked
     * @return $this
     */
    public function setRevoked(bool $revoked): self
    {
        $this->revoked = $revoked;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return $this
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    /**
     * @param bool $isPrimary
     * @return $this
     */
    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    /**
     * @param bool $isVerified
     * @return $this
     */
    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
