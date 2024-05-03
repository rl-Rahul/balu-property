<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\FloorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FloorRepository::class)
 */
class Floor extends Base
{

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $floorNumber;

    /**
     * @ORM\OneToMany(targetEntity=Apartment::class, mappedBy="floor")
     */
    private $apartments;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sortOrder;

    public function __construct()
    {
        parent::__construct();
        $this->apartments = new ArrayCollection();
    }
    
    /**
     * 
     * @return string|null
     */
    public function getFloorNumber(): ?string
    {
        return $this->floorNumber;
    }
    
    /**
     * 
     * @param string $floorNumber
     * @return self
     */
    public function setFloorNumber(string $floorNumber): self
    {
        $this->floorNumber = $floorNumber;

        return $this;
    }

    /**
     * @return Collection|Apartment[]
     */
    public function getApartments(): Collection
    {
        return $this->apartments;
    }

    public function addApartment(Apartment $apartment): self
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments[] = $apartment;
            $apartment->setFloor($this);
        }

        return $this;
    }

    public function removeApartment(Apartment $apartment): self
    {
        if ($this->apartments->removeElement($apartment)) {
            // set the owning side to null (unless already changed)
            if ($apartment->getFloor() === $this) {
                $apartment->setFloor(null);
            }
        }

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
}
