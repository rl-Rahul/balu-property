<?php

namespace App\Entity;

use App\Repository\RentalTypesRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=RentalTypesRepository::class)
 */
class RentalTypes extends Base
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $nameEn;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $nameDe;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $type;
    
    /**
     * 
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->nameEn;
    }
    
    /**
     * 
     * @param string|null $nameEn
     * @return self
     */
    public function setName(?string $nameEn): self
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
     * @param string|null $nameDe
     * @return self
     */
    public function setNameDe(?string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }
    
    /**
     * 
     * @param string|null $type
     * @return self
     */
    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
