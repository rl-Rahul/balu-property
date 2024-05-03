<?php

namespace App\Entity;

use App\Repository\PropertyGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ORM\Entity(repositoryClass=PropertyGroupRepository::class)
 * @ExclusionPolicy("all")
 */
class PropertyGroup extends Base
{
    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     */
    private ?string $name;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="propertyGroups")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?UserIdentity $createdBy;

    /**
     * @Accessor(getter="getUserId")
     * @SerializedName("userId")
     * @Expose
     */
    private ?string $owner;
    
    
    /**
     * @ORM\ManyToMany(targetEntity=Property::class, mappedBy="propertyGroups")
     */
    private Collection $properties;
    
//    /**
//     * @Accessor(getter="getPropertyCount")
//     * @SerializedName("propertyCount")
//     * @Expose
//     */
//    private ?int $propertyCount;

    public function __construct()
    {
        parent::__construct();
        $this->properties = new ArrayCollection();
    }
    

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedBy(): ?UserIdentity
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?UserIdentity $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection|Property[]
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(Property $property): self
    {
        if (!$this->properties->contains($property)) {
            $this->property[] = $property;
        }

        return $this;
    }

    public function removeProperty(Property $property): self
    {
        $this->properties->removeElement($property);

        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getUserId(): string
    {
       return $this->createdBy->getPublicId();
    }
    
    
    /**
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
    
//    /**
//     *
//     * @return int
//     */
//    public function getPropertyCount(): int
//    {
//       return count($this->properties);
//    }
}
