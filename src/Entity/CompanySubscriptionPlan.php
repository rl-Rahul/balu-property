<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CompanySubscriptionPlan
 *
 * @ORM\Entity
 */
class CompanySubscriptionPlan extends Base
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=180)
     */
    private string $name;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $period;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $initialPlan;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private float $amount;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $active = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripePlan;

     /**
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    private ?string $inAppPlan;

    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     */
    private ?float $inAppAmount = 0.0;

    /**
     * @ORM\OneToMany(targetEntity=UserSubscription::class, mappedBy="companySubscriptionPlan")
     */
    private Collection $userSubscriptions;

    /**
     * @ORM\OneToMany(targetEntity=UserIdentity::class, mappedBy="companySubscriptionPlan")
     */
    private Collection $userIdentities;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripeOneTimePlan;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $maxPerson;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $minPerson;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="companyPlan")
     */
    private Collection $payments;
    
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $colorCode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $textColor;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private array $details = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isPlatformUsageAllowed;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nameDe;

    public function __construct()
    {
        parent::__construct();
        $this->userSubscriptions = new ArrayCollection();
        $this->userIdentities = new ArrayCollection();
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
    public function setStripePlan(?string $stripePlan = null): self
    {
        $this->stripePlan = $stripePlan;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInAppPlan(): ?string
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
     * @return Collection|UserSubscription[]
     */
    public function getUserSubscriptions(): Collection
    {
        return $this->userSubscriptions;
    }

    public function addUserSubscription(UserSubscription $userSubscription): self
    {
        if (!$this->userSubscriptions->contains($userSubscription)) {
            $this->userSubscriptions[] = $userSubscription;
            $userSubscription->setCompanySubscriptionPlan($this);
        }

        return $this;
    }

    public function removeUserSubscription(UserSubscription $userSubscription): self
    {
        if ($this->userSubscriptions->removeElement($userSubscription)) {
            // set the owning side to null (unless already changed)
            if ($userSubscription->getCompanySubscriptionPlan() === $this) {
                $userSubscription->setCompanySubscriptionPlan(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserIdentity[]
     */
    public function getUserIdentities(): Collection
    {
        return $this->userIdentities;
    }

    public function addUserIdentity(UserIdentity $userIdentity): self
    {
        if (!$this->userIdentities->contains($userIdentity)) {
            $this->userIdentities[] = $userIdentity;
            $userIdentity->setCompanySubscriptionPlan($this);
        }

        return $this;
    }

    public function removeUserIdentity(UserIdentity $userIdentity): self
    {
        if ($this->userIdentities->removeElement($userIdentity)) {
            // set the owning side to null (unless already changed)
            if ($userIdentity->getCompanySubscriptionPlan() === $this) {
                $userIdentity->setCompanySubscriptionPlan(null);
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

    public function getMaxPerson(): ?int
    {
        return $this->maxPerson;
    }

    public function setMaxPerson(?int $maxPerson): self
    {
        $this->maxPerson = $maxPerson;

        return $this;
    }

    public function getMinPerson(): ?int
    {
        return $this->minPerson;
    }

    public function setMinPerson(?int $minPerson): self
    {
        $this->minPerson = $minPerson;

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
            $payment->setCompanyPlan($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getCompanyPlan() === $this) {
                $payment->setCompanyPlan(null);
            }
        }

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

    public function isIsPlatformUsageAllowed(): ?bool
    {
        return $this->isPlatformUsageAllowed;
    }

    public function setIsPlatformUsageAllowed(bool $isPlatformUsageAllowed): self
    {
        $this->isPlatformUsageAllowed = $isPlatformUsageAllowed;

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
