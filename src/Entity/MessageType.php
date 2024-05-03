<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\MessageTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MessageTypeRepository::class)
 */
class MessageType extends Base
{
    /**
     * @ORM\Column(type="string", length=25)
     */
    private $nameEn;
    
    /**
     * @ORM\Column(type="string", length=25)
     */
    private $nameDe;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $typeKey;

    public function getNameEn(): ?string
    {
        return $this->nameEn;
    }

    public function setNameEn(string $type): self
    {
        $this->nameEn = $type;

        return $this;
    }
    
    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    public function setNameDe(string $typeKey): self
    {
        $this->nameDe = $typeKey;

        return $this;
    }

    public function getTypeKey(): ?string
    {
        return $this->typeKey;
    }

    public function setTypeKey(string $typeKey): self
    {
        $this->typeKey = $typeKey;

        return $this;
    }
}
