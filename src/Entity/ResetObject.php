<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\ResetObjectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ResetObjectRepository::class)
 */
class ResetObject extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="resetObjects")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Apartment $apartment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $reason;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default":0})
     */
    private ?bool $isSuperAdminApproved;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $superAdminComment;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $requestedBy;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $property;

    /**
     * @return Apartment|null
     */
    public function getApartment(): ?Apartment
    {
        return $this->apartment;
    }

    /**
     * @param Apartment|null $apartment
     * @return $this
     */
    public function setApartment(?Apartment $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param string|null $reason
     * @return $this
     */
    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsSuperAdminApproved(): ?bool
    {
        return $this->isSuperAdminApproved;
    }

    /**
     * @param bool $isSuperAdminApproved
     * @return $this
     */
    public function setIsSuperAdminApproved(bool $isSuperAdminApproved): self
    {
        $this->isSuperAdminApproved = $isSuperAdminApproved;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSuperAdminComment(): ?string
    {
        return $this->superAdminComment;
    }

    /**
     * @param string $superAdminComment
     * @return $this
     */
    public function setSuperAdminComment(string $superAdminComment): self
    {
        $this->superAdminComment = $superAdminComment;

        return $this;
    }

    public function getRequestedBy(): ?UserIdentity
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?UserIdentity $requestedBy): self
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }
}
