<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * ApartmentRentHistory
 *
 * @ORM\Entity
 */
class ApartmentRentHistory extends Base
{
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $rent;

    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     */
    private ?float $additionalCost;


    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $actualIndexStand;

    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     */
    private ?float $actualIndexStandNumber;

    /**
     * @ORM\ManyToOne(targetEntity=ModeOfPayment::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?ModeOfPayment $modeOfPayment = null;

    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="apartmentRentHistories")
     */
    private ?Apartment $apartment;

    /**
     * @ORM\ManyToOne(targetEntity=ReferenceIndex::class, inversedBy="apartmentRentHistories")
     */
    private ?ReferenceIndex $referenceRate = null;

    /**
     * @ORM\ManyToOne(targetEntity=LandIndex::class, inversedBy="apartmentRentHistories")
     */
    private ?LandIndex $basisLandIndex = null;

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
     * @return float|null
     */
    public function getAdditionalCost(): ?float
    {
        return $this->additionalCost;
    }

    /**
     * @param float|null $additionalCost
     * @return $this
     */
    public function setAdditionalCost(?float $additionalCost): self
    {
        $this->additionalCost = $additionalCost;

        return $this;
    }    

    /**
     * @return string|null
     */
    public function getActualIndexStand(): ?string
    {
        return $this->actualIndexStand;
    }

    /**
     * @param string|null $actualIndexStand
     * @return $this
     */
    public function setActualIndexStand(?string $actualIndexStand): self
    {
        $this->actualIndexStand = $actualIndexStand;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getActualIndexStandNumber(): ?float
    {
        return $this->actualIndexStandNumber;
    }

    /**
     * @param float|null $actualIndexStandNumber
     * @return $this
     */
    public function setActualIndexStandNumber(?float $actualIndexStandNumber): self
    {
        $this->actualIndexStandNumber = $actualIndexStandNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentMode(): ?string
    {
        return $this->paymentMode;
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
     * @param Apartment|null $apartment
     * @return $this
     */
    public function setApartment(?Apartment $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }

    /**
     * @return ReferenceIndex|null
     */
    public function getReferenceRate(): ?ReferenceIndex
    {
        return $this->referenceRate;
    }

    /**
     * @param ReferenceIndex|null $referenceRate
     * @return $this
     */
    public function setReferenceRate(?ReferenceIndex $referenceRate): self
    {
        $this->referenceRate = $referenceRate;

        return $this;
    }

    /**
     * @return LandIndex|null
     */
    public function getBasisLandIndex(): ?LandIndex
    {
        return $this->basisLandIndex;
    }

    /**
     * @param LandIndex|null $basisLandIndex
     * @return $this
     */
    public function setBasisLandIndex(?LandIndex $basisLandIndex): self
    {
        $this->basisLandIndex = $basisLandIndex;

        return $this;
    }
}
