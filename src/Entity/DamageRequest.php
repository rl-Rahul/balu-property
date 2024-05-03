<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\DamageRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DamageRequestRepository::class)
 */
class DamageRequest extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=Damage::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?UserIdentity $company = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $requestedDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $newOfferRequestedDate;

    /**
     * @ORM\ManyToOne(targetEntity=DamageStatus::class)
     */
    private ?DamageStatus $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $comment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $companyEmail;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $requestRejectDate;

    /**
     * @return Damage|null
     */
    public function getDamage(): ?Damage
    {
        return $this->damage;
    }

    /**
     * @param Damage|null $damage
     * @return $this
     */
    public function setDamage(?Damage $damage): self
    {
        $this->damage = $damage;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getCompany(): ?UserIdentity
    {
        return $this->company;
    }

    /**
     * @param UserIdentity|null $company
     * @return $this
     */
    public function setCompany(?UserIdentity $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getRequestedDate(): ?\DateTimeInterface
    {
        return $this->requestedDate;
    }

    /**
     * @param \DateTimeInterface|null $requestedDate
     * @return $this
     */
    public function setRequestedDate(?\DateTimeInterface $requestedDate): self
    {
        $this->requestedDate = $requestedDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getNewOfferRequestedDate(): ?\DateTimeInterface
    {
        return $this->newOfferRequestedDate;
    }

    /**
     * @param \DateTimeInterface|null $newOfferRequestedDate
     * @return $this
     */
    public function setNewOfferRequestedDate(?\DateTimeInterface $newOfferRequestedDate): self
    {
        $this->newOfferRequestedDate = $newOfferRequestedDate;

        return $this;
    }

    /**
     * @return DamageStatus|null
     */
    public function getStatus(): ?DamageStatus
    {
        return $this->status;
    }

    /**
     * @param DamageStatus|null $status
     * @return $this
     */
    public function setStatus(?DamageStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompanyEmail(): ?string
    {
        return $this->companyEmail;
    }

    /**
     * @param string|null $companyEmail
     * @return $this
     */
    public function setCompanyEmail(?string $companyEmail): self
    {
        $this->companyEmail = $companyEmail;

        return $this;
    }

    public function getRequestRejectDate(): ?\DateTimeInterface
    {
        return $this->requestRejectDate;
    }

    public function setRequestRejectDate(?\DateTimeInterface $requestRejectDate): self
    {
        $this->requestRejectDate = $requestRejectDate;

        return $this;
    }
}
