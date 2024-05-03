<?php

namespace App\Entity;

use App\Repository\ApartmentLogRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=ApartmentLogRepository::class)
 */
class ApartmentLog extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="apartmentLogs")
     */
    private $apartment;
    
    /**
    * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
    */
    private ?float $area  = null;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $roomCount = null;
    
    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     */
    private ?float $rent = null;

    /**
     * @ORM\ManyToOne(targetEntity=ObjectTypes::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?ObjectTypes $objectType = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $sortOrder;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $ceilingHeight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $volume;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $maxFloorLoading;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $officialNumber;

    /**
     * @ORM\ManyToOne(targetEntity=Floor::class)
     */
    private ?Floor $floor = null;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private UserIdentity $createdBy;
    
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
     * @ORM\ManyToOne(targetEntity=ReferenceIndex::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $referenceRate;

    /**
     * @ORM\ManyToOne(targetEntity=LandIndex::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $landIndex;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $baseIndexDate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $baseIndexValue;

    /**
     * @ORM\ManyToOne(targetEntity=ContractTypes::class)
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
     * @return \DateTimeInterface|null
     */
    public function getBaseIndexDate(): ?\DateTimeInterface
    {
        return $this->baseIndexDate;
    }
    
    /**
     * 
     * @param \DateTimeInterface|null $baseIndexDate
     * @return self
     */
    public function setBaseIndexDate(?\DateTimeInterface $baseIndexDate): self
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

    
    /**
     * @return float|null
     */
    public function getArea(): ?float
    {
        return $this->area;
    }

    /**
     * @param float|null $area
     * @return $this
     */
    public function setArea(?float $area): self
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRoomCount(): ?string
    {
        return $this->roomCount;
    }

    /**
     * @param int|null roomCount
     * @return $this
     */
    public function setRoomCount(?string $roomCount): self
    {
        $this->roomCount = $roomCount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRent(): ?float
    {
        return $this->rent;
    }

    /**
     * @param float|null $rent
     * @return $this
     */
    public function setRent(?float $rent): self
    {
        $this->rent = $rent;

        return $this;
    }

    /**
     * @return ObjectTypes|null
     */
    public function getObjectType(): ?ObjectTypes
    {
        return $this->objectType;
    }

    /**
     * @param ObjectTypes|null $objectType
     * @return $this
     */
    public function setObjectType(?ObjectTypes $objectType): self
    {
        $this->objectType = $objectType;

        return $this;
    }
    
    /**
     * 
     * @return int|null
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }
    
    /**
     * 
     * @param int|null $sortOrder
     * @return self
     */
    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * 
     * @param string $name
     * @return self
     */
    
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     *
     * @return float|null
     */
    public function getCeilingHeight(): ?float
    {
        return $this->ceilingHeight;
    }

    /**
     *
     * @param float $ceilingHeight
     * @return self
     */
    public function setCeilingHeight(float $ceilingHeight): self
    {
        $this->ceilingHeight = $ceilingHeight;

        return $this;
    }

    /**
     *
     * @return float|null
     */
    public function getVolume(): ?float
    {
        return $this->volume;
    }

    /**
     *
     * @param float|null $volume
     * @return self
     */
    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     *
     * @return int|null
     */
    public function getMaxFloorLoading(): ?int
    {
        return $this->maxFloorLoading;
    }

    /**
     *
     * @param int|null $maxFloorLoading
     * @return self
     */
    public function setMaxFloorLoading(?int $maxFloorLoading): self
    {
        $this->maxFloorLoading = $maxFloorLoading;

        return $this;
    }

    /**
     * 
     * @return int|null
     */
    public function getOfficialNumber(): ?int
    {
        return $this->officialNumber;
    }

    /**
     * 
     * @param int $officialNumber
     * @return self
     */
    public function setOfficialNumber(?int $officialNumber): self
    {
        $this->officialNumber = $officialNumber;

        return $this;
    }

    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    public function setFloor(?Floor $floor): self
    {
        $this->floor = $floor;

        return $this;
    }
    
    public function getCreatedBy(): ?UserIdentity
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?UserIdentity $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
    
    public function getApartment(): ?Apartment
    {
        return $this->apartment;
    }

    public function setApartment(?Apartment $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }
}
