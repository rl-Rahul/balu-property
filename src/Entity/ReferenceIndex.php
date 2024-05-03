<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReferenceIndexRepository;

/**
 * ReferenceIndex
 *
 * @ORM\Entity(repositoryClass=ReferenceIndexRepository::class)
 */
class ReferenceIndex extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $sortOrder;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $active;

    /**
     * @ORM\OneToMany(targetEntity=ObjectContractDetail::class, mappedBy="referenceRate")
     */
    private Collection $apartments;

    /**
     * @ORM\OneToMany(targetEntity=ApartmentRentHistory::class, mappedBy="referenceRate")
     */
    private Collection $apartmentRentHistories;

    /**
     * ReferenceIndex constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->apartments = new ArrayCollection();
        $this->apartmentRentHistories = new ArrayCollection();
    }

    /**
     * @return Collection|Apartment[]
     */
    public function getApartments(): Collection
    {
        return $this->apartments;
    }

    /**
     * @param Apartment $apartment
     * @return $this
     */
    public function addApartment(Apartment $apartment): self
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments[] = $apartment;
            $apartment->setReferenceRate($this);
        }

        return $this;
    }

    /**
     * @param Apartment $apartment
     * @return $this
     */
    public function removeApartment(Apartment $apartment): self
    {
        if ($this->apartments->removeElement($apartment)) {
            // set the owning side to null (unless already changed)
            if ($apartment->getReferenceRate() === $this) {
                $apartment->setReferenceRate(null);
            }
        }

        return $this;
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
            $apartmentRentHistory->setReferenceRate($this);
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
            if ($apartmentRentHistory->getReferenceRate() === $this) {
                $apartmentRentHistory->setReferenceRate(null);
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


