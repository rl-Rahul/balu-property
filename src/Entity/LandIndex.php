<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\Translation\t;
use App\Repository\LandIndexRepository;

/**
 * LandIndex
 *
 * @ORM\Entity(repositoryClass=LandIndexRepository::class)
 */
class LandIndex extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $name;
    
     /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $nameDe;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $sortOrder;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $active = true;

    /**
     * @ORM\OneToMany(targetEntity=ApartmentRentHistory::class, mappedBy="basisLandIndex")
     */
    private Collection $apartmentRentHistories;
    /**
     * @ORM\OneToMany(targetEntity=Property::class, mappedBy="landIndex")
     */
    private Collection $contracts;
    

    /**
     * LandIndex constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->apartmentRentHistories = new ArrayCollection();
        $this->apartments = new ArrayCollection();
        $this->contracts = new ArrayCollection();
    }

    /**
     * @return Collection|ApartmentRentHistory[]
     */
    public function getApartmentRentHistories(): Collection
    {
        return $this->apartmentRentHistories;
    }

    /**
     * @param ApartmentRentHistory $apartmentRentHistory
     * @return $this
     */
    public function addApartmentRentHistory(ApartmentRentHistory $apartmentRentHistory): self
    {
        if (!$this->apartmentRentHistories->contains($apartmentRentHistory)) {
            $this->apartmentRentHistories[] = $apartmentRentHistory;
            $apartmentRentHistory->setBasisLandIndex($this);
        }

        return $this;
    }

    /**
     * @param ApartmentRentHistory $apartmentRentHistory
     * @return $this
     */
    public function removeApartmentRentHistory(ApartmentRentHistory $apartmentRentHistory): self
    {
        if ($this->apartmentRentHistories->removeElement($apartmentRentHistory)) {
            // set the owning side to null (unless already changed)
            if ($apartmentRentHistory->getBasisLandIndex() === $this) {
                $apartmentRentHistory->setBasisLandIndex(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    /**
     * @param string|null $nameDe
     * @return $this
     */
    public function setNameDe(?string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @param int|null $sortOrder
     * @return $this
     */
    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

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

}


