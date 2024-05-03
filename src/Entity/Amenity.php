<?php

namespace App\Entity;

use App\Repository\AmenityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=AmenityRepository::class)
 */
class Amenity extends Base
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nameDe;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $active;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sortOrder;

    /**
     * @ORM\OneToMany(targetEntity=ObjectAmenityMeasure::class, mappedBy="amenity")
     */
    private $objectAmenityMeasures;
    
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    private $amenityKey;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isInput;

    public function __construct()
    {
        parent::__construct();
        $this->objectAmenityMeasures = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    public function setNameDe(?string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(?int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return Collection|ObjectAmenityMeasure[]
     */
    public function getObjectAmenityMeasures(): Collection
    {
        return $this->objectAmenityMeasures;
    }

    public function addObjectAmenityMeasure(ObjectAmenityMeasure $objectAmenityMeasure): self
    {
        if (!$this->objectAmenityMeasures->contains($objectAmenityMeasure)) {
            $this->objectAmenityMeasures[] = $objectAmenityMeasure;
            $objectAmenityMeasure->setAmenity($this);
        }

        return $this;
    }

    public function removeObjectAmenityMeasure(ObjectAmenityMeasure $objectAmenityMeasure): self
    {
        if ($this->objectAmenityMeasures->removeElement($objectAmenityMeasure)) {
            // set the owning side to null (unless already changed)
            if ($objectAmenityMeasure->getAmenity() === $this) {
                $objectAmenityMeasure->setAmenity(null);
            }
        }

        return $this;
    }
    
        
    /**
     * 
     * @return string|null
     */
    public function getAmenityKey(): ?string
    {
        return $this->amenityKey;
    }
    
    /**
     * 
     * @param string $amenityKey
     * @return self
     */
    public function setAmenityKey(string $amenityKey): self
    {
        $this->amenityKey = $amenityKey;

        return $this;
    }
    
    /**
     * 
     * @return bool|null
     */
    public function getIsInput(): ?bool
    {
        return $this->isInput;
    }
    
    /**
     * 
     * @param bool|null $isInput
     * @return self
     */
    public function setIsInput(?bool $isInput): self
    {
        $this->isInput = $isInput;

        return $this;
    }
}
