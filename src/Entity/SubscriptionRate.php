<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * SubscriptionRate
 *
 * @ORM\Entity
 */
class SubscriptionRate extends Base
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $apartmentMax;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $apartmentMin;

    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     */
    private ?float $amount;

    /**
     * @ORM\ManyToOne(targetEntity=SubscriptionPlan::class, inversedBy="subscriptionRates")
     */
    private ?SubscriptionPlan $subscriptionPlan;

    /**
     * @return int|null
     */
    public function getApartmentMax(): ?int
    {
        return $this->apartmentMax;
    }

    /**
     * @param int|null $apartmentMax
     * @return $this
     */
    public function setApartmentMax(?int $apartmentMax): self
    {
        $this->apartmentMax = $apartmentMax;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getApartmentMin(): ?int
    {
        return $this->apartmentMin;
    }

    /**
     * @param int|null $apartmentMin
     * @return $this
     */
    public function setApartmentMin(?int $apartmentMin): self
    {
        $this->apartmentMin = $apartmentMin;

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
}
