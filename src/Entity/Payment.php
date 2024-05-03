<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=PaymentRepository::class)
 */
class Payment extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $response;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isSuccess;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private ?bool $isCompany;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $transactionId;

    /**
     * @ORM\Column(type="float", nullable=true, options={"default":0})
     */
    private ?float $amount;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     */
    private ?int $period;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class, inversedBy="payment")
     */
    private Property $property;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, inversedBy="payments")
     */
    private Role $role;

    /**
     * @ORM\ManyToOne(targetEntity=SubscriptionPlan::class, inversedBy="payments")
     */
    private ?SubscriptionPlan $subscriptionPlan;

    /**
     * @ORM\ManyToOne(targetEntity=CompanySubscriptionPlan::class, inversedBy="payments")
     */
    private ?CompanySubscriptionPlan $companyPlan;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $endDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $cancelledDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, name="event_id")
     */
    private ?string $event;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $expiredAt;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $selectedItems = [];

    /**
     * @return string|null
     */
    public function getResponse(): ?string
    {
        return $this->response;
    }

    /**
     * @param string|null $response
     * @return $this
     */
    public function setResponse(?string $response): self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsSuccess(): ?bool
    {
        return $this->isSuccess;
    }

    /**
     * @param bool $isSuccess
     * @return $this
     */
    public function setIsSuccess(bool $isSuccess): self
    {
        $this->isSuccess = $isSuccess;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsCompany(): ?bool
    {
        return $this->isCompany;
    }

    /**
     * @param bool $isCompany
     * @return $this
     */
    public function setIsCompany(bool $isCompany): self
    {
        $this->isCompany = $isCompany;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param string|null $transactionId
     * @return $this
     */
    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount
     * @return $this
     */
    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPeriod(): ?int
    {
        return $this->period;
    }

    /**
     * @param int|null $period
     * @return $this
     */
    public function setPeriod(?int $period): self
    {
        $this->period = $period;

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
     * @return Property|null
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }

    /**
     * @param Property $property
     * @return $this
     */
    public function setProperty(Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return SubscriptionPlan|null
     */
    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    /**
     * @param SubscriptionPlan|null $subscriptionPlan
     * @return $this
     */
    public function setSubscriptionPlan(?SubscriptionPlan $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    /**
     * @return CompanySubscriptionPlan|null
     */
    public function getCompanyPlan(): ?CompanySubscriptionPlan
    {
        return $this->companyPlan;
    }

    /**
     * @param CompanySubscriptionPlan|null $companyPlan
     * @return $this
     */
    public function setCompanyPlan(?CompanySubscriptionPlan $companyPlan): self
    {
        $this->companyPlan = $companyPlan;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    /**
     * @param \DateTimeInterface|null $startDate
     * @return $this
     */
    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    /**
     * @param \DateTimeInterface|null $endDate
     * @return $this
     */
    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCancelledDate(): ?\DateTimeInterface
    {
        return $this->cancelledDate;
    }

    /**
     * @param \DateTimeInterface|null $cancelledDate
     * @return $this
     */
    public function setCancelledDate(?\DateTimeInterface $cancelledDate): self
    {
        $this->cancelledDate = $cancelledDate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * @param string|null $event
     * @return $this
     */
    public function setEvent(?string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getExpiredAt(): ?\DateTime
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTime $expiredAt): self
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
    
    public function getSelectedItems(): ?array
    {
        return $this->selectedItems;
    }

    public function setSelectedItems(?array $selectedItems): self
    {
        $this->selectedItems = $selectedItems;

        return $this;
    }
}
