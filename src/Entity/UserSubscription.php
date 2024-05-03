<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\UserSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass=UserSubscriptionRepository::class)
 */
class UserSubscription extends Base
{
    /**
     * @ORM\OneToOne(targetEntity=UserIdentity::class, cascade={"persist", "remove"})
     */
    private ?UserIdentity $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripeSubscription;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isRecurring = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isFreePlanSubscribed = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isExpired = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private \DateTime $expiryDate;

    /**
     * @ORM\ManyToOne(targetEntity=CompanySubscriptionPlan::class, inversedBy="userSubscriptions")
     */
    private ?CompanySubscriptionPlan $companySubscriptionPlan;

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
     * @return CompanySubscriptionPlan|null
     */
    public function getCompanySubscriptionPlan(): ?CompanySubscriptionPlan
    {
        return $this->companySubscriptionPlan;
    }

    /**
     * @param CompanySubscriptionPlan|null $companySubscriptionPlan
     * @return $this
     */
    public function setCompanySubscriptionPlan(?CompanySubscriptionPlan $companySubscriptionPlan): self
    {
        $this->companySubscriptionPlan = $companySubscriptionPlan;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripeSubscription(): ?string
    {
        return $this->stripeSubscription;
    }

    /**
     * @param string|null $stripeSubscription
     * @return $this
     */
    public function setStripeSubscription(?string $stripeSubscription): self
    {
        $this->stripeSubscription = $stripeSubscription;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    /**
     * @param bool $isRecurring
     * @return $this
     */
    public function setIsRecurring(bool $isRecurring): self
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsFreePlanSubscribed(): ?bool
    {
        return $this->isFreePlanSubscribed;
    }

    /**
     * @param bool $isFreePlanSubscribed
     * @return $this
     */
    public function setIsFreePlanSubscribed(bool $isFreePlanSubscribed): self
    {
        $this->isFreePlanSubscribed = $isFreePlanSubscribed;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsExpired(): ?bool
    {
        return $this->isExpired;
    }

    /**
     * @param bool $isExpired
     * @return $this
     */
    public function setIsExpired(bool $isExpired): self
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpiryDate(): ?\DateTime
    {
        return $this->expiryDate;
    }

    /**
     * @param \DateTimeInterface|null $expiryDate
     * @return $this
     */
    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }
}
