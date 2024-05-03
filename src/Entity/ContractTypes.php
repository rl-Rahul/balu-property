<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\ContractTypesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ContractTypesRepository::class)
 */
class ContractTypes extends Base
{
    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $nameEn;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $nameDe;

    /**
     * @ORM\Column(type="smallint")
     */
    private ?int $type;
    
    /**
     * @ORM\OneToMany(targetEntity=ObjectContractDetail::class, mappedBy="contractType")
     */
    private Collection $objectContractDetails;

    public function __construct()
    {
        parent::__construct();
        $this->apartments = new ArrayCollection();
        $this->objectContractDetails = new ArrayCollection();
    }
    
    
    /**
     *
     * @return string|null
     */
    public function getNameEn(): ?string
    {
        return $this->nameEn;
    }
    
    /**
     *
     * @param string $nameEn
     * @return self
     */
    public function setNameEn(string $nameEn): self
    {
        $this->nameEn = $nameEn;

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }
    
    /**
     * 
     * @param string $nameDe
     * @return self
     */
    public function setNameDe(string $nameDe): self
    {
        $this->nameDe = $nameDe;

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
            $apartment->setContractType($this);
        }

        return $this;
    }

    public function removeApartment(Apartment $apartment): self
    {
        if ($this->apartments->removeElement($apartment)) {
            // set the owning side to null (unless already changed)
            if ($apartment->getContractType() === $this) {
                $apartment->setContractType(null);
            }
        }

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
