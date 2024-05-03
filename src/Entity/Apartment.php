<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Entity\Interfaces\ReturnableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ApartmentRepository;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Apartment
 *
 * @ORM\Entity(repositoryClass=ApartmentRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Apartment extends Base implements ReturnableInterface
{
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $active;

    /**
    * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
    */
    private ?float $area  = null;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $roomCount = null;
    
    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     */
    private ?float $rent = null;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class, inversedBy="apartments")
     */
    private ?Property $property  = null;

    /**
     * @ORM\ManyToOne(targetEntity=ObjectTypes::class, inversedBy="apartments")
     */
    private ?ObjectTypes $objectType = null;

    /**
     * @ORM\OneToMany(targetEntity=ApartmentRentHistory::class, mappedBy="apartment")
     */
    private Collection $apartmentRentHistories;

    /**
     * @ORM\OneToMany(targetEntity=Damage::class, mappedBy="apartment")
     */
    private Collection $damages;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    private ?int $sortOrder;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private ?string $name;

    /**
     * @ORM\OneToMany(targetEntity=ObjectAmenityMeasure::class, mappedBy="object")
     */
    private Collection $objectAmenityMeasures;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $ceilingHeight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $volume = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $maxFloorLoading = null;

    /**
     * @Assert\Type(type="integer")
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $officialNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity=Floor::class, inversedBy="apartments")
     */
    private ?Floor $floor = null;

    /**
     * @ORM\OneToMany(targetEntity=ObjectContractDetail::class, mappedBy="object")
     */
    private Collection $objectContractDetails;

    /**
     * @ORM\OneToMany(targetEntity=ObjectContracts::class, mappedBy="object")
     */
    private Collection $objectContracts;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="apartments")
     */
    private UserIdentity $createdBy;

    /**
     * @ORM\OneToOne(targetEntity=Folder::class, cascade={"persist", "remove"})
     */
    private ?Folder $folder;
    
    /**
     * @ORM\OneToMany(targetEntity=PropertyUser::class, mappedBy="object")
     */
    private Collection $propertyUsers;

    /**
     * @ORM\OneToMany(targetEntity=Document::class, mappedBy="apartment")
     */
    private Collection $documents;

    /**
     * @ORM\OneToMany(targetEntity=ApartmentLog::class, mappedBy="apartment")
     */
    private Collection $apartmentLogs;

    /**
     * @ORM\OneToMany(targetEntity=ResetObject::class, mappedBy="apartment", orphanRemoval=true)
     */
    private Collection $resetObjects;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isSystemGenerated = false;


    /**
     * Apartment constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->apartmentRentHistories = new ArrayCollection();
        $this->damages = new ArrayCollection();
        $this->objectContractDetails = new ArrayCollection();
        $this->propertyUsers = new ArrayCollection();
        $this->objectContracts = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->apartmentLogs = new ArrayCollection();
        $this->resetObjects = new ArrayCollection();
        $this->objectAmenityMeasures = new ArrayCollection();
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

    /**
     * @return float|null
     */
    public function getArea(): ?float
    {
        return $this->area;
    }

    /**
     * @param float|null $area
     * @return $this
     */
    public function setArea(?float $area): self
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRoomCount(): ?string
    {
        return $this->roomCount;
    }

    /**
     * @param string|null $roomCount
     * @return $this
     */
    public function setRoomCount(?string $roomCount): self
    {
        $this->roomCount = $roomCount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRent(): ?float
    {
        return $this->rent;
    }

    /**
     * @param float|null $rent
     * @return $this
     */
    public function setRent(?float $rent): self
    {
        $this->rent = $rent;

        return $this;
    }


    /**
     * @return Property|null
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }

    /**
     * @param Property|null $property
     * @return $this
     */
    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    /**
     * @return ObjectTypes|null
     */
    public function getObjectType(): ?ObjectTypes
    {
        return $this->objectType;
    }

    /**
     * @param ObjectTypes|null $objectType
     * @return $this
     */
    public function setObjectType(?ObjectTypes $objectType): self
    {
        $this->objectType = $objectType;

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
            $apartmentRentHistory->setApartment($this);
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
            if ($apartmentRentHistory->getApartment() === $this) {
                $apartmentRentHistory->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Damage[]
     */
    public function getDamages(): Collection
    {
        return $this->damages;
    }

    /**
     * @param Damage $damage
     * @return $this
     */
    public function addDamage(Damage $damage): self
    {
        if (!$this->damages->contains($damage)) {
            $this->damages[] = $damage;
            $damage->setApartment($this);
        }

        return $this;
    }

    /**
     * @param Damage $damage
     * @return $this
     */
    public function removeDamage(Damage $damage): self
    {
        if ($this->damages->removeElement($damage)) {
            // set the owning side to null (unless already changed)
            if ($damage->getApartment() === $this) {
                $damage->setApartment(null);
            }
        }

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
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|ObjectAmenityMeasure[]
     */
    public function getObjectAmenityMeasures(): Collection
    {
        return $this->objectAmenityMeasures;
    }

    /**
     *
     * @param ObjectAmenityMeasure $objectAmenityMeasure
     * @return self
     */
    public function addObjectAmenityMeasure(ObjectAmenityMeasure $objectAmenityMeasure): self
    {
        if (!$this->objectAmenityMeasures->contains($objectAmenityMeasure)) {
            $this->objectAmenityMeasures[] = $objectAmenityMeasure;
            $objectAmenityMeasure->setObject($this);
        }

        return $this;
    }

    /**
     *
     * @param ObjectAmenityMeasure $objectAmenityMeasure
     * @return self
     */
    public function removeObjectAmenityMeasure(ObjectAmenityMeasure $objectAmenityMeasure): self
    {
        if ($this->objectAmenityMeasures->removeElement($objectAmenityMeasure)) {
            // set the owning side to null (unless already changed)
            if ($objectAmenityMeasure->getObject() === $this) {
                $objectAmenityMeasure->setObject(null);
            }
        }

        return $this;
    }

    /**
     *
     * @return float|null
     */
    public function getCeilingHeight(): ?float
    {
        return $this->ceilingHeight;
    }

    /**
     *
     * @param float $ceilingHeight
     * @return self
     */
    public function setCeilingHeight(float $ceilingHeight): self
    {
        $this->ceilingHeight = $ceilingHeight;

        return $this;
    }

    /**
     *
     * @return float|null
     */
    public function getVolume(): ?float
    {
        return $this->volume;
    }

    /**
     *
     * @param float|null $volume
     * @return self
     */
    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     *
     * @return int|null
     */
    public function getMaxFloorLoading(): ?int
    {
        return $this->maxFloorLoading;
    }

    /**
     *
     * @param int|null $maxFloorLoading
     * @return self
     */
    public function setMaxFloorLoading(?int $maxFloorLoading): self
    {
        $this->maxFloorLoading = $maxFloorLoading;

        return $this;
    }

    /**
     * 
     * @return int|null
     */
    public function getOfficialNumber(): ?int
    {
        return $this->officialNumber;
    }

    /**
     * 
     * @param int $officialNumber
     * @return self
     */
    public function setOfficialNumber(?int $officialNumber): self
    {
        $this->officialNumber = $officialNumber;

        return $this;
    }

    /**
     * @return Floor|null
     */
    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    /**
     * @param Floor|null $floor
     * @return $this
     */
    public function setFloor(?Floor $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * @return Collection|ObjectContractDetail[]
     */
    public function getObjectContractDetails(): Collection
    {
        return $this->objectContractDetails;
    }

    /**
     * @param ObjectContractDetail $objectContractDetail
     * @return $this
     */
    public function addObjectContractDetail(ObjectContractDetail $objectContractDetail): self
    {
        if (!$this->objectContractDetails->contains($objectContractDetail)) {
            $this->objectContractDetails[] = $objectContractDetail;
            $objectContractDetail->setObject($this);
        }

        return $this;
    }

    /**
     * @param ObjectContractDetail $objectContractDetail
     * @return $this
     */
    public function removeObjectContractDetail(ObjectContractDetail $objectContractDetail): self
    {
        if ($this->objectContractDetails->removeElement($objectContractDetail)) {
            // set the owning side to null (unless already changed)
            if ($objectContractDetail->getObject() === $this) {
                $objectContractDetail->setObject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|PropertyUser[]
     */
    public function getPropertyUsers(): Collection
    {
        return $this->propertyUsers;
    }

    /**
     * @param PropertyUser $propertyUser
     * @return $this
     */
    public function addPropertyUser(PropertyUser $propertyUser): self
    {
        if (!$this->propertyUsers->contains($propertyUser)) {
            $this->propertyUsers[] = $propertyUser;
            $propertyUser->setObject($this);
        }

        return $this;
    }

    /**
     * @param PropertyUser $propertyUser
     * @return $this
     */
    public function removePropertyUser(PropertyUser $propertyUser): self
    {
        if ($this->propertyUsers->removeElement($propertyUser)) {
            // set the owning side to null (unless already changed)
            if ($propertyUser->getObject() === $this) {
                $propertyUser->setObject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ObjectContracts[]
     */
    public function getObjectContracts(): Collection
    {
        return $this->objectContracts;
    }

    /**
     * @param ObjectContracts $objectContract
     * @return $this
     */
    public function addObjectContract(ObjectContracts $objectContract): self
    {
        if (!$this->objectContracts->contains($objectContract)) {
            $this->objectContracts[] = $objectContract;
            $objectContract->setObject($this);
        }

        return $this;
    }

    /**
     * @param ObjectContracts $objectContract
     * @return $this
     */
    public function removeObjectContract(ObjectContracts $objectContract): self
    {
        if ($this->objectContracts->removeElement($objectContract)) {
            // set the owning side to null (unless already changed)
            if ($objectContract->getObject() === $this) {
                $objectContract->setObject(null);
            }
        }

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getCreatedBy(): ?UserIdentity
    {
        return $this->createdBy;
    }

    /**
     * @param UserIdentity|null $createdBy
     * @return $this
     */
    public function setCreatedBy(?UserIdentity $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Folder|null
     */
    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    /**
     * @param Folder|null $folder
     * @return $this
     */
    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setApartment($this);
        }

        return $this;
    }

    /**
     * @param Document $document
     * @return $this
     */
    public function removeDocument(Document $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getApartment() === $this) {
                $document->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ApartmentLog[]
     */
    public function getApartmentLogs(): Collection
    {
        return $this->apartmentLogs;
    }

    /**
     * @param ApartmentLog $apartmentLog
     * @return $this
     */
    public function addApartmentLog(ApartmentLog $apartmentLog): self
    {
        if (!$this->apartmentLogs->contains($apartmentLog)) {
            $this->apartmentLogs[] = $apartmentLog;
            $apartmentLog->setApartment($this);
        }

        return $this;
    }

    /**
     * @param ApartmentLog $apartmentLog
     * @return $this
     */
    public function removeApartmentLog(ApartmentLog $apartmentLog): self
    {
        if ($this->apartmentLogs->removeElement($apartmentLog)) {
            // set the owning side to null (unless already changed)
            if ($apartmentLog->getApartment() === $this) {
                $apartmentLog->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ResetObject[]
     */
    public function getResetObjects(): Collection
    {
        return $this->resetObjects;
    }

    /**
     * @param ResetObject $resetObject
     * @return $this
     */
    public function addResetObject(ResetObject $resetObject): self
    {
        if (!$this->resetObjects->contains($resetObject)) {
            $this->resetObjects[] = $resetObject;
            $resetObject->setApartment($this);
        }

        return $this;
    }

    /**
     * @param ResetObject $resetObject
     * @return $this
     */
    public function removeResetObject(ResetObject $resetObject): self
    {
        if ($this->resetObjects->removeElement($resetObject)) {
            // set the owning side to null (unless already changed)
            if ($resetObject->getApartment() === $this) {
                $resetObject->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsSystemGenerated(): ?bool
    {
        return $this->isSystemGenerated;
    }

    /**
     * @param bool $isSystemGenerated
     * @return $this
     */
    public function setIsSystemGenerated(bool $isSystemGenerated): self
    {
        $this->isSystemGenerated = $isSystemGenerated;

        return $this;
    }
}
