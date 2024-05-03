<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=CurrencyRepository::class)
 */
class Currency extends Base
{

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private ?string $nameEn;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private ?string $nameDe;
    
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
     * @param string|null $nameEn
     * @return self
     */
    public function setNameEn(?string $nameEn): self
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
}
