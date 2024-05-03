<?php

namespace App\Entity;

use App\Repository\SubscriptionPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=SubscriptionPlanRepository::class)
 */
class SubscriptionPlan extends Base
{
    /**
     * @ORM\Column(type="string", length=180)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $period;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $initialPlan;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $apartmentMax;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $apartmentMin;

    /**
     * @ORM\Column(type="float", nullable=true, precision=10, scale=0, options={"default":0})
     */
    private ?float $amount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $active;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripePlan;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $inAppPlan;

    /**
     * @ORM\Column(type="float", nullable=true, precision=10, scale=0, options={"default":0})
     */
    private ?float $inAppAmount;

    /**
     * @ORM\OneToMany(targetEntity=SubscriptionRate::class, mappedBy="subscriptionPlan")
     */
    private Collection $subscriptionRates;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripeOneTimePlan;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private array $details = [];

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $colorCode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $textColor;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="subscriptionPlan")
     */
    private Collection $payments;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nameDe;

    /**
     * SubscriptionPlan constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->subscriptionRates = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->details = array();
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
     * @return bool|null
     */
    public function getInitialPlan(): ?bool
    {
        return $this->initialPlan;
    }

    /**
     * @param bool|null $initialPlan
     * @return $this
     */
    public function setInitialPlan(?bool $initialPlan): self
    {
        $this->initialPlan = $initialPlan;

        return $this;
    }

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
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     * @return $this
     */
    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripePlan(): ?string
    {
        return $this->stripePlan;
    }

    /**
     * @param string|null $stripePlan
     * @return $this
     */
    public function setStripePlan(?string $stripePlan): self
    {
        $this->stripePlan = $stripePlan;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getInAppPlan(): ?float
    {
        return $this->inAppPlan;
    }

    /**
     * @param string|null $inAppPlan
     * @return $this
     */
    public function setInAppPlan(?string $inAppPlan): self
    {
        $this->inAppPlan = $inAppPlan;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getInAppAmount(): ?float
    {
        return $this->inAppAmount;
    }

    /**
     * @param float|null $inAppAmount
     * @return $this
     */
    public function setInAppAmount(?float $inAppAmount): self
    {
        $this->inAppAmount = $inAppAmount;

        return $this;
    }

    /**
     * @return Collection|SubscriptionRate[]
     */
    public function getSubscriptionRates(): Collection
    {
        return $this->subscriptionRates;
    }

    /**
     * @param SubscriptionRate $subscriptionRate
     * @return $this
     */
    public function addSubscriptionRate(SubscriptionRate $subscriptionRate): self
    {
        if (!$this->subscriptionRates->contains($subscriptionRate)) {
            $this->subscriptionRates[] = $subscriptionRate;
            $subscriptionRate->setSubscriptionPlan($this);
        }

        return $this;
    }

    /**
     * @param SubscriptionRate $subscriptionRate
     * @return $this
     */
    public function removeSubscriptionRate(SubscriptionRate $subscriptionRate): self
    {
        if ($this->subscriptionRates->removeElement($subscriptionRate)) {
            // set the owning side to null (unless already changed)
            if ($subscriptionRate->getSubscriptionPlan() === $this) {
                $subscriptionRate->setSubscriptionPlan(null);
            }
        }

        return $this;
    }

    public function getStripeOneTimePlan(): ?string
    {
        return $this->stripeOneTimePlan;
    }

    public function setStripeOneTimePlan(?string $stripeOneTimePlan): self
    {
        $this->stripeOneTimePlan = $stripeOneTimePlan;

        return $this;
    }

    public function getDetails(): array
    {
        if (is_null($this->details)) {
            return [];
        }
        return $this->details;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getColorCode(): ?string
    {
        return $this->colorCode;
    }

    public function setColorCode(?string $colorCode): self
    {
        $this->colorCode = $colorCode;

        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(?string $textColor): self
    {
        $this->textColor = $textColor;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setSubscriptionPlan($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getSubscriptionPlan() === $this) {
                $payment->setSubscriptionPlan(null);
            }
        }

        return $this;
    }

    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    public function setNameDe(string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }
}
