<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\SubscriptionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SubscriptionHistoryRepository::class)
 */
class SubscriptionHistory extends Base
{

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class)
     */
    private ?Property $property;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $event;

    /**
     * @ORM\ManyToOne(targetEntity=CompanySubscriptionPlan::class, inversedBy="subscriptionHistories")
     */
    private ?CompanySubscriptionPlan $companyPlan;

    /**
     * @ORM\ManyToOne(targetEntity=SubscriptionPlan::class, inversedBy="subscriptionHistories")
     */
    private ?SubscriptionPlan $subscriptionPlan;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $eventType;
    
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
     * @return Property|null
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }

    /**
     * 
     * @param Property|null $property
     * @return self
     */
    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    /**
     * 
     * @return string|null
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * 
     * @param string|null $event
     * @return self
     */
    public function setEvent(?string $event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * 
     * @return CompanySubscriptionPlan|null
     */
    public function getCompanyPlan(): ?CompanySubscriptionPlan
    {
        return $this->companyPlan;
    }

    /**
     * 
     * @param CompanySubscriptionPlan|null $companyPlan
     * @return self
     */
    public function setCompanyPlan(?CompanySubscriptionPlan $companyPlan): self
    {
        $this->companyPlan = $companyPlan;

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

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }
}
