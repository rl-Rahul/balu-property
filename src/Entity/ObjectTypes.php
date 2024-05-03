<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ObjectTypesRepository;

/**
 * ObjectTypes
 *
 * @ORM\Entity(repositoryClass=ObjectTypesRepository::class)
 */
class ObjectTypes extends Base
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
    private ?bool $active;

    /**
     * @ORM\OneToMany(targetEntity=Apartment::class, mappedBy="objectType")
     */
    private Collection $apartments;


    /**
     * ObjectTypes constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->apartments = new ArrayCollection();
    }

    public function __set($property, $value): self
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
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
            $apartment->setObjectType($this);
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
            if ($apartment->getObjectType() === $this) {
                $apartment->setObjectType(null);
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


