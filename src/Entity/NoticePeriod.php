<?php

namespace App\Entity;

use App\Repository\NoticePeriodRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=NoticePeriodRepository::class)
 */
class NoticePeriod extends Base
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $type;
    
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
    
    /**
     * 
     * @return int|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }
    
    /**
     * 
     * @param int|null $type
     * @return self
     */
    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
