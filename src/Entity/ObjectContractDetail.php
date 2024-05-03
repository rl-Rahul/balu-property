<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\ObjectContractDetailRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ObjectContractDetailRepository::class)
 */
class ObjectContractDetail extends Base
{
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $totalObjectValue;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostBuilding;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostEnvironment;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostHeating;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostElevator;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostParking;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostRenewal;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostMaintenance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCostAdministration;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $additionalCost;
    
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $netRentRate;

    /**
     * @ORM\ManyToOne(targetEntity=ReferenceIndex::class, inversedBy="apartments")
     * @ORM\JoinColumn(nullable=true)
     */
    private $referenceRate;

    /**
     * @ORM\ManyToOne(targetEntity=LandIndex::class, inversedBy="indexes")
     * @ORM\JoinColumn(nullable=true)
     */
    private $landIndex;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $baseIndexDate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $baseIndexValue;

    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="objectContractDetails")
     * @ORM\JoinColumn(nullable=false)
     */
    private $object;

    /**
     * @ORM\ManyToOne(targetEntity=ContractTypes::class, inversedBy="objectContractDetails")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?ContractTypes $contractType;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $active;

    /**
     * @ORM\ManyToOne(targetEntity=Currency::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Currency $netRentRateCurrency;

    /**
     * @ORM\ManyToOne(targetEntity=Currency::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Currency $additionalCostCurrency;

    /**
     * @ORM\ManyToOne(targetEntity=ModeOfPayment::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?ModeOfPayment $modeOfPayment;

    public function __construct()
    {
        parent::__construct();
    }

     /**
     * @return float|null
     */
    public function getNetRentRate(): ?float
    {
        return $this->netRentRate;
    }

    /**
     * 
     * @param float|null $additionalCostBuilding
     * @return self
     */
    public function setAdditionalCostBuilding(?float $additionalCostBuilding): self
    {
        $this->additionalCostBuilding = $additionalCostBuilding;

        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAdditionalCostEnvironment(): ?float
    {
        return $this->additionalCostEnvironment;
    }
    
    /**
     * 
     * @param float|null $additionalCostEnvironment
     * @return self
     */
    public function setAdditionalCostEnvironment(?float $additionalCostEnvironment): self
    {
        $this->additionalCostEnvironment = $additionalCostEnvironment;

        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAdditionalCostHeating(): ?float
    {
        return $this->additionalCostHeating;
    }

    /**
     * 
     * @param float|null $additionalCostHeating
     * @return self
     */
    public function setAdditionalCostHeating(?float $additionalCostHeating): self
    {
        $this->additionalCostHeating = $additionalCostHeating;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getAdditionalCostElevator(): ?float
    {
        return $this->additionalCostElevator;
    }
    
    /**
     * 
     * @param float|null $additionalCostElevator
     * @return self
     */
    public function setAdditionalCostElevator(?float $additionalCostElevator): self
    {
        $this->additionalCostElevator = $additionalCostElevator;

        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAdditionalCostParking(): ?float
    {
        return $this->additionalCostParking;
    }
    
    /**
     * 
     * @param float|null $additionalCostParking
     * @return self
     */
    public function setAdditionalCostParking(?float $additionalCostParking): self
    {
        $this->additionalCostParking = $additionalCostParking;

        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAdditionalCostRenewal(): ?float
    {
        return $this->additionalCostRenewal;
    }
    
    /**
     * 
     * @param float|null $additionalCostRenewal
     * @return self
     */
    public function setAdditionalCostRenewal(?float $additionalCostRenewal): self
    {
        $this->additionalCostRenewal = $additionalCostRenewal;

        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAdditionalCostMaintenance(): ?float
    {
        return $this->additionalCostMaintenance;
    }
    
    /**
     * 
     * @param float|null $additionalCostMaintenance
     * @return self
     */
    public function setAdditionalCostMaintenance(?float $additionalCostMaintenance): self
    {
        $this->additionalCostMaintenance = $additionalCostMaintenance;

        return $this;
    }
    
    /**
     * @return float|null
     */
    public function getAdditionalCostAdministration(): ?float
    {
        return $this->additionalCostAdministration;
    }
    
    /**
     * 
     * @param float $netRentRate
     * @return self
     */
    public function setNetRentRate(?float $netRentRate): self
    {
        $this->netRentRate = $netRentRate;

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getAdditionalCost(): ?string
    {
        return $this->additionalCost;
    }
    
    /**
     * 
     * @param string|null $additionalCost
     * @return self
     */
    public function setAdditionalCost(?string $additionalCost): self
    {
        $this->additionalCost = $additionalCost;

        return $this;
    }
    
    /**
     * 
     * @return ReferenceIndex|null
     */
    public function getReferenceRate(): ?ReferenceIndex
    {
        return $this->referenceRate;
    }
    
    /**
     * 
     * @param ReferenceIndex|null $referenceRate
     * @return self
     */
    public function setReferenceRate(?ReferenceIndex $referenceRate): self
    {
        $this->referenceRate = $referenceRate;

        return $this;
    }
    
    /**
     * 
     * @return LandIndex|null
     */
     public function getLandIndex(): ?LandIndex
    {
        return $this->landIndex;
    }
    
    /**
     * 
     * @param LandIndex|null $landIndex
     * @return self
     */
    public function setLandIndex(?LandIndex $landIndex): self
    {
        $this->landIndex = $landIndex;

        return $this;
    }
    
    /**
     * 
     * @return \DateTime|null
     */
    public function getBaseIndexDate(): ?\DateTime
    {
        return $this->baseIndexDate;
    }
    
    /**
     * 
     * @param \DateTime|null $baseIndexDate
     * @return self
     */
    public function setBaseIndexDate(?\DateTime $baseIndexDate): self
    {
        $this->baseIndexDate = $baseIndexDate;

        return $this;
    }
    
    /**
     * 
     * @return float|null
     */
    public function getBaseIndexValue(): ?float
    {
        return $this->baseIndexValue;
    }
    
    /**
     * 
     * @param float|null $baseIndexValue
     * @return self
     */
    public function setBaseIndexValue(?float $baseIndexValue): self
    {
        $this->baseIndexValue = $baseIndexValue;

        return $this;
    }
    
    /**
     * 
     * @return float|null
     */
    public function getTotalObjectValue(): ?float
    {
        return $this->totalObjectValue;
    }
    
    /**
     * 
     * @param float $totalObjectValue
     * @return self
     */
    public function setTotalObjectValue(?float $totalObjectValue): self
    {
        $this->totalObjectValue = $totalObjectValue;

        return $this;
    }
    
    /**
     * 
     * @return float|null
     */
    public function getAdditionalCostBuilding(): ?float
    {
        return $this->additionalCostBuilding;
    }
    
    /**
     * 
     * @param float|null $additionalCostAdministration
     * @return self
     */
    public function setAdditionalCostAdministration(?float $additionalCostAdministration): self
    {
        $this->additionalCostAdministration = $additionalCostAdministration;

        return $this;
    }

    public function getObject(): ?Apartment
    {
        return $this->object;
    }

    public function setObject(?Apartment $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getContractType(): ?ContractTypes
    {
        return $this->contractType;
    }

    public function setContractType(?ContractTypes $contractType): self
    {
        $this->contractType = $contractType;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }
    /**
     * 
     * @return Currency|null
     */
    public function getNetRentRateCurrency(): ?Currency
    {
        return $this->netRentRateCurrency;
    }
    
    /**
     * 
     * @param Currency|null $netRentRateCurrency
     * @return self
     */
    public function setNetRentRateCurrency(?Currency $netRentRateCurrency): self
    {
        $this->netRentRateCurrency = $netRentRateCurrency;

        return $this;
    }
    
    /**
     * 
     * @return Currency|null
     */
    public function getAdditionalCostCurrency(): ?Currency
    {
        return $this->additionalCostCurrency;
    }
    
    /**
     * 
     * @param Currency|null $additionalCostCurrency
     * @return self
     */
    public function setAdditionalCostCurrency(?Currency $additionalCostCurrency): self
    {
        $this->additionalCostCurrency = $additionalCostCurrency;

        return $this;
    }
    
    /**
     * 
     * @return ModeOfPayment|null
     */
    public function getModeOfPayment(): ?ModeOfPayment
    {
        return $this->modeOfPayment;
    }
    
    /**
     * 
     * @param ModeOfPayment|null $modeOfPayment
     * @return self
     */
    public function setModeOfPayment(?ModeOfPayment $modeOfPayment): self
    {
        $this->modeOfPayment = $modeOfPayment;

        return $this;
    }

}
