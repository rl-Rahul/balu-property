<?php

namespace App\Entity;

use App\Entity\Interfaces\ReturnableInterface;
use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=PropertyRepository::class)
 */
class Property extends Base implements ReturnableInterface
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $address;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $streetName;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $streetNumber = null;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $postalCode = null;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $city;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $state;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $country;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $countryCode;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $currency;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $planStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $planEndDate;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default":0})
     */
    private ?bool $active;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $latitude;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $longitude;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private ?bool $recurring;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private ?bool $pendingPayment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripeSubscription;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="properties")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=SubscriptionPlan::class)
     */
    private ?SubscriptionPlan $subscriptionPlan;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="property")
     */
    private Collection $payment;

    /**
     * @ORM\OneToMany(targetEntity=Apartment::class, mappedBy="property")
     */
    private Collection $apartments;

    /**
     * @ORM\OneToMany(targetEntity=FavouriteCompany::class, mappedBy="property")
     */
    private Collection $favouriteCompanies;

    /**
     * @ORM\OneToOne(targetEntity=Folder::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Folder $folder;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="janitors")
     */
    private ?UserIdentity $janitor = null;

    /**
     * @ORM\ManyToMany(targetEntity=PropertyGroup::class, inversedBy="property")
     * @ORM\JoinTable(
     *     name="balu_property_group_mapping",
     *     joinColumns={
     *          @ORM\JoinColumn(name="property_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *     }
     * )
     */
    private Collection $propertyGroups;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="propertiesCreatedBy")
     */
    private ?UserIdentity $createdBy;

    /**
     * @ORM\OneToMany(targetEntity=PropertyUser::class, mappedBy="property")
     */
    private Collection $propertyUsers;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="propertyAdministrators")
     */
    private ?UserIdentity $administrator = null;

    /**
     * @ORM\OneToMany(targetEntity=Document::class, mappedBy="property")
     */
    private Collection $documents;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isCancelledSubscription = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $cancelledDate;

    /**
     * @ORM\Column(type="string", length=10, nullable=true, options={"default":0})
     */
    private ?string $resetCount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $paymentLink;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $expiredDate;

    /**
     * @ORM\OneToMany(targetEntity=Directory::class, mappedBy="property")
     */
    private Collection $directories;

    /**
     * Property constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->payment = new ArrayCollection();
        $this->apartments = new ArrayCollection();
        $this->favouriteCompanies = new ArrayCollection();
        $this->propertyGroups = new ArrayCollection();
        $this->propertyUsers = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->directories = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * @param string|null $streetName
     * @return $this
     */
    public function setStreetName(?string $streetName): self
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    /**
     * @param string|null $streetNumber
     * @return $this
     */
    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string|null $postalCode
     * @return $this
     */
    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return $this
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     * @return $this
     */
    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     * @return $this
     */
    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * @param string|null $countryCode
     * @return $this
     */
    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     * @return $this
     */
    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPlanStartDate(): ?\DateTimeInterface
    {
        return $this->planStartDate;
    }

    /**
     * @param \DateTimeInterface|null $planStartDate
     * @return $this
     */
    public function setPlanStartDate(?\DateTimeInterface $planStartDate): self
    {
        $this->planStartDate = $planStartDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPlanEndDate(): ?\DateTimeInterface
    {
        return $this->planEndDate;
    }

    /**
     * @param \DateTimeInterface|null $planEndDate
     * @return $this
     */
    public function setPlanEndDate(?\DateTimeInterface $planEndDate): self
    {
        $this->planEndDate = $planEndDate;

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

    /**
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     * @return $this
     */
    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
     * @param string|null $longitude
     * @return $this
     */
    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRecurring(): ?bool
    {
        return $this->recurring;
    }

    /**
     * @param bool $recurring
     * @return $this
     */
    public function setRecurring(bool $recurring): self
    {
        $this->recurring = $recurring;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getPendingPayment(): ?bool
    {
        return $this->pendingPayment;
    }

    /**
     * @param bool $pendingPayment
     * @return $this
     */
    public function setPendingPayment(bool $pendingPayment): self
    {
        $this->pendingPayment = $pendingPayment;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripeSubscription(): ?string
    {
        return $this->stripeSubscription;
    }

    /**
     * @param string|null $stripeSubscription
     * @return $this
     */
    public function setStripeSubscription(?string $stripeSubscription): self
    {
        $this->stripeSubscription = $stripeSubscription;

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
     * @return SubscriptionPlan|null
     */
    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    /**
     * @param SubscriptionPlan|null $subscriptionPlan
     * @return $this
     */
    public function setSubscriptionPlan(?SubscriptionPlan $subscriptionPlan): self
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayment(): Collection
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     * @return $this
     */
    public function addPayment(Payment $payment): self
    {
        if (!$this->payment->contains($payment)) {
            $this->payment[] = $payment;
        }

        return $this;
    }

    /**
     * @param Payment $payment
     * @return $this
     */
    public function removePayment(Payment $payment): self
    {
        $this->payment->removeElement($payment);

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
            $apartment->setProperty($this);
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
            if ($apartment->getProperty() === $this) {
                $apartment->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FavouriteCompany[]
     */
    public function getFavouriteCompanies(): Collection
    {
        return $this->favouriteCompanies;
    }

    /**
     * @param FavouriteCompany $favouriteCompany
     * @return $this
     */
    public function addFavouriteCompany(FavouriteCompany $favouriteCompany): self
    {
        if (!$this->favouriteCompanies->contains($favouriteCompany)) {
            $this->favouriteCompanies[] = $favouriteCompany;
            $favouriteCompany->setProperty($this);
        }

        return $this;
    }

    /**
     * @param FavouriteCompany $favouriteCompany
     * @return $this
     */
    public function removeFavouriteCompany(FavouriteCompany $favouriteCompany): self
    {
        if ($this->favouriteCompanies->removeElement($favouriteCompany)) {
            // set the owning side to null (unless already changed)
            if ($favouriteCompany->getProperty() === $this) {
                $favouriteCompany->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder
    {
        return $this->folder;
    }

    /**
     * @param Folder $folder
     * @return $this
     */
    public function setFolder(Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getJanitor(): ?UserIdentity
    {
        return $this->janitor;
    }

    /**
     * @param UserIdentity|null $janitor
     * @return $this
     */
    public function setJanitor(?UserIdentity $janitor): self
    {
        $this->janitor = $janitor;
        return $this;
    }
    /**
     * @return Collection|PropertyGroup[]
     */
    public function getPropertyGroups(): Collection
    {
        return $this->propertyGroups;
    }

    /**
     * @param PropertyGroup $propertyGroup
     * @return $this
     */
    public function addPropertyGroup(PropertyGroup $propertyGroup): self
    {
        if (!$this->propertyGroups->contains($propertyGroup)) {
            $this->propertyGroups[] = $propertyGroup;
            $propertyGroup->addProperty($this);
        }

        return $this;
    }

    /**
     * @param PropertyGroup $propertyGroup
     * @return $this
     */
    public function removePropertyGroup(PropertyGroup $propertyGroup): self
    {
        if ($this->propertyGroups->removeElement($propertyGroup)) {
            $propertyGroup->removeProperty($this);
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
     * @return Collection|PropertyUser[]
     */
    public function getPropertyUsers(): Collection
    {
        return $this->propertyUsers;
    }

    public function addPropertyUser(PropertyUser $propertyUser): self
    {
        if (!$this->propertyUsers->contains($propertyUser)) {
            $this->propertyUsers[] = $propertyUser;
            $propertyUser->setProperty($this);
        }

        return $this;
    }

    public function removePropertyUser(PropertyUser $propertyUser): self
    {
        if ($this->propertyUsers->removeElement($propertyUser)) {
            // set the owning side to null (unless already changed)
            if ($propertyUser->getProperty() === $this) {
                $propertyUser->setProperty(null);
            }
        }

        return $this;
    }

    public function getAdministrator(): ?UserIdentity
    {
        return $this->administrator;
    }

    public function setAdministrator(?UserIdentity $administrator): self
    {
        $this->administrator = $administrator;

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getIsCancelledSubscription(): ?bool
    {
        return $this->isCancelledSubscription;
    }

    public function setIsCancelledSubscription(bool $isCancelledSubscription): self
    {
        $this->isCancelledSubscription = $isCancelledSubscription;

        return $this;
    }

    public function getCancelledDate(): ?\DateTimeInterface
    {
        return $this->cancelledDate;
    }

    public function setCancelledDate(?\DateTimeInterface $cancelledDate): self
    {
        $this->cancelledDate = $cancelledDate;

        return $this;
    }

    public function getResetCount(): ?string
    {
        return $this->resetCount;
    }

    public function setResetCount(?string $resetCount): self
    {
        $this->resetCount = $resetCount;

        return $this;
    }

    public function getPaymentLink(): ?string
    {
        return $this->paymentLink;
    }

    public function setPaymentLink(string $paymentLink): self
    {
        $this->paymentLink = $paymentLink;

        return $this;
    }

    public function getExpiredDate(): ?string
    {
        return $this->expiredDate;
    }

    public function setExpiredDate(?string $expiredDate): self
    {
        $this->expiredDate = $expiredDate;

        return $this;
    }

    /**
     * @return Collection<int, Directory>
     */
    public function getDirectories(): Collection
    {
        return $this->directories;
    }

    public function addDirectory(Directory $directory): self
    {
        if (!$this->directories->contains($directory)) {
            $this->directories[] = $directory;
            $directory->setProperty($this);
        }

        return $this;
    }

    public function removeDirectory(Directory $directory): self
    {
        if ($this->directories->removeElement($directory)) {
            // set the owning side to null (unless already changed)
            if ($directory->getProperty() === $this) {
                $directory->setProperty(null);
            }
        }

        return $this;
    }
}
