<?php

namespace App\Entity;

use App\Repository\ObjectAmenityMeasureRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=ObjectAmenityMeasureRepository::class)
 */
class ObjectAmenityMeasure extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="objectAmenityMeasures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $object;

    /**
     * @ORM\ManyToOne(targetEntity=Amenity::class, inversedBy="objectAmenityMeasures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $amenity;

    /**
     * @ORM\Column(type="float")
     */
    private $value;

    public function __construct()
    {
        parent::__construct();
    }

    public function getObject(): ?Apartment
    {
        return $this->object;
    }

    public function setObject(?Apartment $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getAmenity(): ?Amenity
    {
        return $this->amenity;
    }

    public function setAmenity(?Amenity $amenity): self
    {
        $this->amenity = $amenity;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }
}
