<?php

namespace App\Entity;

use App\Repository\DirectoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=DirectoryRepository::class)
 */
class Directory extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $invitor;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\OneToMany(targetEntity=PropertyUser::class, mappedBy="directory")
     */
    private Collection $propertyUsers;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $isFavourite = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $invitedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $street;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $streetNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $city;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $zipCode;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $country;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class, inversedBy="directories")
     */
    private ?Property $property;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $state;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $landline;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?\DateTime $dob;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $companyName;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $countryCode;

    /**
     * Directory constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->propertyUsers = new ArrayCollection();
    }
    
    /**
     * @return UserIdentity|null
     */
    public function getInvitor(): ?UserIdentity
    {
        return $this->invitor;
    }

    /**
     * @param UserIdentity|null $invitor
     * @return $this
     */
    public function setInvitor(?UserIdentity $invitor): self
    {
        $this->invitor = $invitor;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getUser(): ?UserIdentity
    {
        return $this->user;
    }

    /**
     * @param UserIdentity|null $user
     * @return $this
     */
    public function setUser(?UserIdentity $user): self
    {
        $this->user = $user;

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
            $propertyUser->setDirectory($this);
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
            if ($propertyUser->getDirectory() === $this) {
                $propertyUser->setDirectory(null);
            }
        }

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsFavourite(): ?bool
    {
        return $this->isFavourite;
    }

    /**
     * @param bool|null $isFavourite
     * @return $this
     */
    public function setIsFavourite(?bool $isFavourite): self
    {
        $this->isFavourite = $isFavourite;

        return $this;
    }
    
    /**
     * 
     * @return \DateTimeInterface|null
     */
    public function getInvitedAt(): ?\DateTimeInterface
    {
        return $this->invitedAt;
    }
    
    /**
     * 
     * @param \DateTimeInterface|null $invitedAt
     * @return self
     */
    public function setInvitedAt(?\DateTimeInterface $invitedAt): self
    {
        $this->invitedAt = $invitedAt;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getLandline(): ?string
    {
        return $this->landline;
    }

    public function setLandline(?string $landline): self
    {
        $this->landline = $landline;

        return $this;
    }

    public function getDob(): ?\DateTime
    {
        return $this->dob;
    }

    public function setDob(?\DateTime $dob): self
    {
        $this->dob = $dob;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

}
