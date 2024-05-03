<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\UserIdentityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserIdentityRepository::class)
 */
class UserIdentity extends Base
{
    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $firstName;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastName;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $enabled;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="userIdentity", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user;

    /**
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $companyName;

    /**
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $website;

    /**
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $administratorName;

    /**
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $language;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $createdBy;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?\DateTime $dob;

    /**
     * @ORM\OneToMany(targetEntity=Address::class, mappedBy="user")
     */
    private Collection $addresses;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isPolicyAccepted = true;

    /**
     * @ORM\ManyToMany(targetEntity=Role::class, inversedBy="userIdentities")
     */
    private Collection $role;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isBlocked = FALSE;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class, mappedBy="user")
     */
    private Collection $categories;

    /**
     * @ORM\OneToMany(targetEntity=Property::class, mappedBy="user")
     */
    private Collection $properties;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="userIdentities")
     */
    private ?UserIdentity $administrator;

    /**
     * @ORM\OneToMany(targetEntity=UserIdentity::class, mappedBy="administrator")
     */
    private Collection $userIdentities;

    /**
     * @ORM\OneToMany(targetEntity=CompanyRating::class, mappedBy="user")
     */
    private Collection $companyRatings;

    /**
     * @ORM\OneToMany(targetEntity=Damage::class, mappedBy="user")
     */
    private Collection $damages;

    /**
     * @ORM\OneToMany(targetEntity=DamageAppointment::class, mappedBy="user")
     */
    private Collection $damageAppointments;

    /**
     * @ORM\OneToMany(targetEntity=DamageDefect::class, mappedBy="user")
     */
    private Collection $damageDefects;

    /**
     * @ORM\OneToMany(targetEntity=DamageLog::class, mappedBy="user")
     */
    private Collection $damageLogs;

    /**
     * @ORM\OneToMany(targetEntity=DamageOffer::class, mappedBy="company")
     */
    private Collection $damageOffers;

    /**
     * @ORM\OneToMany(targetEntity=FavouriteCompany::class, mappedBy="user")
     */
    private Collection $favouriteCompanies;

    /**
     * @ORM\OneToMany(targetEntity=FavouriteIndividual::class, mappedBy="user")
     */
    private Collection $favouriteIndividuals;

    /**
     * @ORM\OneToMany(targetEntity=PushNotification::class, mappedBy="toUser")
     */
    private Collection $pushNotifications;

    /**
     * @ORM\OneToMany(targetEntity=UserDevice::class, mappedBy="user")
     */
    private Collection $userDevices;

    /**
     * @ORM\OneToMany(targetEntity=UserPermissions::class, mappedBy="user")
     */
    private Collection $userPermissions;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="userParents")
     */
    private ?UserIdentity $parent;

    /**
     * @ORM\OneToMany(targetEntity=UserIdentity::class, mappedBy="parent")
     */
    private Collection $userParents;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $jobTitle;

    /**
     * @ORM\OneToMany(targetEntity=Folder::class, mappedBy="createdBy")
     */
    private Collection $folders;

    /**
     * @ORM\OneToMany(targetEntity=Property::class, mappedBy="janitor")
     */
    private Collection $janitors;

    /**
     * @ORM\OneToMany(targetEntity=Property::class, mappedBy="createdBy")
     */
    private Collection $propertiesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity=Property::class, mappedBy="administrator")
     */
    private Collection $propertyAdministrators;

    /**
     * @ORM\OneToMany(targetEntity=Apartment::class, mappedBy="createdBy")
     */
    private Collection $apartments;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isAdminBlocked;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isFreePlanSubscribed;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isRecurring;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $stripeSubscription;

    /**
     * @ORM\ManyToOne(targetEntity=CompanySubscriptionPlan::class, inversedBy="userIdentities")
     */
    private ?CompanySubscriptionPlan $companySubscriptionPlan;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isExpired;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $expiryDate;

    /**
     * @ORM\OneToMany(targetEntity=Feedback::class, mappedBy="sendBy")
     */
    private Collection $feedback;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default" : 0})
     */
    private ?bool $isSystemGeneratedEmail = FALSE;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $invitedAt;

    
    /**
     * @ORM\ManyToMany(targetEntity=Permission::class, mappedBy="user")
     */
    private Collection $userPermission;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $paymentLink;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $companyUserRestrictedDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $subscriptionCancelledAt;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $planEndDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isAppUseEnabled = false;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $authCode;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $adminEditedDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isGuestUser = false;

    /**
     * @ORM\OneToMany(targetEntity=MessageReadUser::class, mappedBy="user")
     */
    private $messageReadUsers;

    /**
     * UserIdentity constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addresses = new ArrayCollection();
        $this->role = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->userIdentities = new ArrayCollection();
        $this->companyRatings = new ArrayCollection();
        $this->damages = new ArrayCollection();
        $this->damageAppointments = new ArrayCollection();
        $this->damageDefects = new ArrayCollection();
        $this->damageLogs = new ArrayCollection();
        $this->damageOffers = new ArrayCollection();
        $this->favouriteCompanies = new ArrayCollection();
        $this->favouriteIndividuals = new ArrayCollection();
        $this->pushNotifications = new ArrayCollection();
        $this->userDevices = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->userParents = new ArrayCollection();
        $this->folders = new ArrayCollection();
        $this->janitors = new ArrayCollection();
        $this->propertiesCreatedBy = new ArrayCollection();
        $this->propertyAdministrators = new ArrayCollection();
        $this->apartments = new ArrayCollection();
        $this->isExpired = false;
        $this->isRecurring = false;
        $this->isFreePlanSubscribed = false;
        $this->isAdminBlocked = false;
        $this->feedback = new ArrayCollection();
        $this->userPermission = new ArrayCollection();
        $this->messageReadUsers = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return $this
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     * @return $this
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return $this|null
     */
    public function getCreatedBy(): ?self
    {
        return $this->createdBy;
    }

    /**
     * @param UserIdentity|null $createdBy
     * @return $this
     */
    public function setCreatedBy(?self $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection|Address[]
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    /**
     * @param Address $address
     * @return $this
     */
    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setUser($this);
        }

        return $this;
    }

    /**
     * @param Address $address
     * @return $this
     */
    public function removeAddress(Address $address): self
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string|null $companyName
     * @return $this
     */
    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     * @return $this
     */
    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAdministratorName(): ?string
    {
        return $this->administratorName;
    }

    /**
     * @param string|null $administratorName
     * @return $this
     */
    public function setAdministratorName(?string $administratorName): self
    {
        $this->administratorName = $administratorName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     * @return $this
     */
    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    /**
     * @param \DateTimeInterface|null $dob
     * @return $this
     */
    public function setDob(?\DateTimeInterface $dob): self
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsPolicyAccepted(): ?bool
    {
        return $this->isPolicyAccepted;
    }

    /**
     * @param bool $isPolicyAccepted
     * @return $this
     */
    public function setIsPolicyAccepted(bool $isPolicyAccepted): self
    {
        $this->isPolicyAccepted = $isPolicyAccepted;

        return $this;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRole(): Collection
    {
        return $this->role;
    }

    /**
     * @param Role $role
     * @return $this
     */
    public function addRole(Role $role): self
    {
        if (!$this->role->contains($role)) {
            $this->role[] = $role;
        }

        return $this;
    }

    /**
     * @param Role $role
     * @return $this
     */
    public function removeRole(Role $role): self
    {
        $this->role->removeElement($role);

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsBlocked(): ?bool
    {
        return $this->isBlocked;
    }

    /**
     * @param bool $isBlocked
     * @return $this
     */
    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->addUser($this);
        }

        return $this;
    }

    /**
     * @param Category $category
     * @return $this
     */
    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category)) {
            $category->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Property[]
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * @param Property $property
     * @return $this
     */
    public function addProperty(Property $property): self
    {
        if (!$this->properties->contains($property)) {
            $this->properties[] = $property;
            $property->setUser($this);
        }

        return $this;
    }

    /**
     * @param Property $property
     * @return $this
     */
    public function removeProperty(Property $property): self
    {
        if ($this->properties->removeElement($property)) {
            // set the owning side to null (unless already changed)
            if ($property->getUser() === $this) {
                $property->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return $this|null
     */
    public function getAdministrator(): ?self
    {
        return $this->administrator;
    }

    /**
     * @param UserIdentity|null $administrator
     * @return $this
     */
    public function setAdministrator(?self $administrator): self
    {
        $this->administrator = $administrator;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getUserIdentities(): Collection
    {
        return $this->userIdentities;
    }

    /**
     * @param UserIdentity $userIdentity
     * @return $this
     */
    public function addUserIdentity(self $userIdentity): self
    {
        if (!$this->userIdentities->contains($userIdentity)) {
            $this->userIdentities[] = $userIdentity;
            $userIdentity->setAdministrator($this);
        }

        return $this;
    }

    /**
     * @param UserIdentity $userIdentity
     * @return $this
     */
    public function removeUserIdentity(self $userIdentity): self
    {
        if ($this->userIdentities->removeElement($userIdentity)) {
            // set the owning side to null (unless already changed)
            if ($userIdentity->getAdministrator() === $this) {
                $userIdentity->setAdministrator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CompanyRating[]
     */
    public function getCompanyRatings(): Collection
    {
        return $this->companyRatings;
    }

    /**
     * @param CompanyRating $companyRating
     * @return $this
     */
    public function addCompanyRating(CompanyRating $companyRating): self
    {
        if (!$this->companyRatings->contains($companyRating)) {
            $this->companyRatings[] = $companyRating;
            $companyRating->setUser($this);
        }

        return $this;
    }

    /**
     * @param CompanyRating $companyRating
     * @return $this
     */
    public function removeCompanyRating(CompanyRating $companyRating): self
    {
        if ($this->companyRatings->removeElement($companyRating)) {
            // set the owning side to null (unless already changed)
            if ($companyRating->getUser() === $this) {
                $companyRating->setUser(null);
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
            $damage->setUser($this);
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
            if ($damage->getUser() === $this) {
                $damage->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageAppointment[]
     */
    public function getDamageAppointments(): Collection
    {
        return $this->damageAppointments;
    }

    /**
     * @param DamageAppointment $damageAppointment
     * @return $this
     */
    public function addDamageAppointment(DamageAppointment $damageAppointment): self
    {
        if (!$this->damageAppointments->contains($damageAppointment)) {
            $this->damageAppointments[] = $damageAppointment;
            $damageAppointment->setUser($this);
        }

        return $this;
    }

    /**
     * @param DamageAppointment $damageAppointment
     * @return $this
     */
    public function removeDamageAppointment(DamageAppointment $damageAppointment): self
    {
        if ($this->damageAppointments->removeElement($damageAppointment)) {
            // set the owning side to null (unless already changed)
            if ($damageAppointment->getUser() === $this) {
                $damageAppointment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageDefect[]
     */
    public function getDamageDefects(): Collection
    {
        return $this->damageDefects;
    }

    /**
     * @param DamageDefect $damageDefect
     * @return $this
     */
    public function addDamageDefect(DamageDefect $damageDefect): self
    {
        if (!$this->damageDefects->contains($damageDefect)) {
            $this->damageDefects[] = $damageDefect;
            $damageDefect->setUser($this);
        }

        return $this;
    }

    /**
     * @param DamageDefect $damageDefect
     * @return $this
     */
    public function removeDamageDefect(DamageDefect $damageDefect): self
    {
        if ($this->damageDefects->removeElement($damageDefect)) {
            // set the owning side to null (unless already changed)
            if ($damageDefect->getUser() === $this) {
                $damageDefect->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageLog[]
     */
    public function getDamageLogs(): Collection
    {
        return $this->damageLogs;
    }

    /**
     * @param DamageLog $damageLog
     * @return $this
     */
    public function addDamageLog(DamageLog $damageLog): self
    {
        if (!$this->damageLogs->contains($damageLog)) {
            $this->damageLogs[] = $damageLog;
            $damageLog->setUser($this);
        }

        return $this;
    }

    /**
     * @param DamageLog $damageLog
     * @return $this
     */
    public function removeDamageLog(DamageLog $damageLog): self
    {
        if ($this->damageLogs->removeElement($damageLog)) {
            // set the owning side to null (unless already changed)
            if ($damageLog->getUser() === $this) {
                $damageLog->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageOffer[]
     */
    public function getDamageOffers(): Collection
    {
        return $this->damageOffers;
    }

    /**
     * @param DamageOffer $damageOffer
     * @return $this
     */
    public function addDamageOffer(DamageOffer $damageOffer): self
    {
        if (!$this->damageOffers->contains($damageOffer)) {
            $this->damageOffers[] = $damageOffer;
            $damageOffer->setCompany($this);
        }

        return $this;
    }

    /**
     * @param DamageOffer $damageOffer
     * @return $this
     */
    public function removeDamageOffer(DamageOffer $damageOffer): self
    {
        if ($this->damageOffers->removeElement($damageOffer)) {
            // set the owning side to null (unless already changed)
            if ($damageOffer->getCompany() === $this) {
                $damageOffer->setCompany(null);
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
            $favouriteCompany->setUser($this);
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
            if ($favouriteCompany->getUser() === $this) {
                $favouriteCompany->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|PushNotification[]
     */
    public function getPushNotifications(): Collection
    {
        return $this->pushNotifications;
    }

    /**
     * @param PushNotification $pushNotification
     * @return $this
     */
    public function addPushNotification(PushNotification $pushNotification): self
    {
        if (!$this->pushNotifications->contains($pushNotification)) {
            $this->pushNotifications[] = $pushNotification;
            $pushNotification->setToUser($this);
        }

        return $this;
    }

    /**
     * @param PushNotification $pushNotification
     * @return $this
     */
    public function removePushNotification(PushNotification $pushNotification): self
    {
        if ($this->pushNotifications->removeElement($pushNotification)) {
            // set the owning side to null (unless already changed)
            if ($pushNotification->getToUser() === $this) {
                $pushNotification->setToUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserDevice[]
     */
    public function getUserDevices(): Collection
    {
        return $this->userDevices;
    }

    /**
     * @param UserDevice $userDevice
     * @return $this
     */
    public function addUserDevice(UserDevice $userDevice): self
    {
        if (!$this->userDevices->contains($userDevice)) {
            $this->userDevices[] = $userDevice;
            $userDevice->setUser($this);
        }

        return $this;
    }

    /**
     * @param UserDevice $userDevice
     * @return $this
     */
    public function removeUserDevice(UserDevice $userDevice): self
    {
        if ($this->userDevices->removeElement($userDevice)) {
            // set the owning side to null (unless already changed)
            if ($userDevice->getUser() === $this) {
                $userDevice->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return $this|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param UserIdentity|null $parent
     * @return $this
     */
    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getUserParents(): Collection
    {
        return $this->userParents;
    }

    /**
     * @param UserIdentity $userParent
     * @return $this
     */
    public function addUserParent(self $userParent): self
    {
        if (!$this->userParents->contains($userParent)) {
            $this->userParents[] = $userParent;
            $userParent->setParent($this);
        }

        return $this;
    }

    /**
     * @return Collection|Folder[]
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    /**
     * @param Folder $folder
     * @return $this
     */
    public function addFolder(Folder $folder): self
    {
        if (!$this->folders->contains($folder)) {
            $this->folders[] = $folder;
            $folder->setCreatedBy($this);
        }

        return $this;
    }

    /**
     * @param Folder $folder
     * @return $this
     */
    public function removeFolder(Folder $folder): self
    {
        if ($this->folders->removeElement($folder)) {
            // set the owning side to null (unless already changed)
            if ($folder->getCreatedBy() === $this) {
                $folder->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Property[]
     */
    public function getJanitors(): Collection
    {
        return $this->janitors;
    }

    /**
     * @param Property $janitor
     * @return $this
     */
    public function addJanitor(Property $janitor): self
    {
        if (!$this->janitors->contains($janitor)) {
            $this->janitors[] = $janitor;
            $janitor->setJanitor($this);
        }

        return $this;
    }

    /**
     * @param Property $janitor
     * @return $this
     */
    public function removeJanitor(Property $janitor): self
    {
        if ($this->janitors->removeElement($janitor)) {
            // set the owning side to null (unless already changed)


            if ($janitor->getJanitor() === $this) {
                $janitor->setJanitor(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    /**
     * @param string|null $jobTitle
     */
    public function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    /**
     * @return Collection|Property[]
     */
    public function getPropertiesCreatedBy(): Collection
    {
        return $this->propertiesCreatedBy;
    }

    /**
     * @param Property $propertiesCreatedBy
     * @return $this
     */
    public function addPropertiesCreatedBy(Property $propertiesCreatedBy): self
    {
        if (!$this->propertiesCreatedBy->contains($propertiesCreatedBy)) {
            $this->propertiesCreatedBy[] = $propertiesCreatedBy;
            $propertiesCreatedBy->setCreatedBy($this);
        }
        return $this;
    }

    /**
     * @param Property $propertiesCreatedBy
     * @return $this
     */
    public function removePropertiesCreatedBy(Property $propertiesCreatedBy): self
    {
        if ($this->propertiesCreatedBy->removeElement($propertiesCreatedBy)) {
            // set the owning side to null (unless already changed)
            if ($propertiesCreatedBy->getCreatedBy() === $this) {
                $propertiesCreatedBy->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Property[]
     */
    public function getPropertyAdministrators(): Collection
    {
        return $this->propertyAdministrators;
    }

    /**
     * @param Property $propertyAdministrator
     * @return $this
     */
    public function addPropertyAdministrator(Property $propertyAdministrator): self
    {
        if (!$this->propertyAdministrators->contains($propertyAdministrator)) {
            $this->propertyAdministrators[] = $propertyAdministrator;
            $propertyAdministrator->setAdministrator($this);
        }

        return $this;
    }

    /**
     * @param Property $propertyAdministrator
     * @return $this
     */
    public function removePropertyAdministrator(Property $propertyAdministrator): self
    {
        if ($this->propertyAdministrators->removeElement($propertyAdministrator)) {
            // set the owning side to null (unless already changed)
            if ($propertyAdministrator->getAdministrator() === $this) {
                $propertyAdministrator->setAdministrator(null);
            }
        }

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
            $apartment->setCcreatedBy($this);
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
            if ($apartment->getCcreatedBy() === $this) {
                $apartment->setCcreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsAdminBlocked(): ?bool
    {
        return $this->isAdminBlocked;
    }

    /**
     * @param bool $isAdminBlocked
     * @return $this
     */
    public function setIsAdminBlocked(bool $isAdminBlocked): self
    {
        $this->isAdminBlocked = $isAdminBlocked;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsFreePlanSubscribed(): ?bool
    {
        return $this->isFreePlanSubscribed;
    }

    /**
     * @param bool $isFreePlanSubscribed
     * @return $this
     */
    public function setIsFreePlanSubscribed(bool $isFreePlanSubscribed): self
    {
        $this->isFreePlanSubscribed = $isFreePlanSubscribed;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    /**
     * @param bool $isRecurring
     * @return $this
     */
    public function setIsRecurring(bool $isRecurring): self
    {
        $this->isRecurring = $isRecurring;

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
     * @return CompanySubscriptionPlan|null
     */
    public function getCompanySubscriptionPlan(): ?CompanySubscriptionPlan
    {
        return $this->companySubscriptionPlan;
    }

    /**
     * @param CompanySubscriptionPlan|null $companySubscriptionPlan
     * @return $this
     */
    public function setCompanySubscriptionPlan(?CompanySubscriptionPlan $companySubscriptionPlan): self
    {
        $this->companySubscriptionPlan = $companySubscriptionPlan;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsExpired(): ?bool
    {
        return $this->isExpired;
    }

    /**
     * @param bool $isExpired
     * @return $this
     */
    public function setIsExpired(bool $isExpired): self
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    /**
     * @param \DateTimeInterface|null $expiryDate
     * @return $this
     */
    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * @return Collection|Feedback[]
     */
    public function getFeedback(): Collection
    {
        return $this->feedback;
    }
    
    /**
     * 
     * @param Feedback $feedback
     * @return self
     */
    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->feedback->contains($feedback)) {
            $this->feedback[] = $feedback;
            $feedback->setSendBy($this);
        }

        return $this;
    }
    
    /**
     * 
     * @param Feedback $feedback
     * @return self
     */
    public function removeFeedback(Feedback $feedback): self
    {
        if ($this->feedback->removeElement($feedback)) {
            // set the owning side to null (unless already changed)
            if ($feedback->getSendBy() === $this) {
                $feedback->setSendBy(null);
            }
        }

        return $this;
    }
    
    /**
     * 
     * @return bool|null
     */
    public function getIsSystemGeneratedEmail(): ?bool
    {
        return $this->isSystemGeneratedEmail;
    }
    
    /**
     * 
     * @param bool|null $isSystemGeneratedEmail
     * @return self
     */
    public function setIsSystemGeneratedEmail(?bool $isSystemGeneratedEmail): self
    {
        $this->isSystemGeneratedEmail = $isSystemGeneratedEmail;
        
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

    /**
     * 
     * @return Collection
     */
    public function getUserPermission(): Collection
    {
        return $this->userPermission;
    }

    /**
     *
     * @param Permission $userPermission
     * @return self
     */
    public function addUserPermission(Permission $userPermission): self
    {
        if (!$this->userPermission->contains($userPermission)) {
            $this->userPermission[] = $userPermission;
            $userPermission->addUser($this);
        }

        return $this;
    }

    /**
     *
     * @param Permission $userPermission
     * @return self
     */
    public function removeUserPermission(Permission $userPermission): self
    {
        if ($this->userPermission->removeElement($userPermission)) {
            $userPermission->removeUser($this);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentLink(): ?string
    {
        return $this->paymentLink;
    }

    /**
     * @param string|null $paymentLink
     * @return $this
     */
    public function setPaymentLink(?string $paymentLink): self
    {
        $this->paymentLink = $paymentLink;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCompanyUserRestrictedDate(): ?\DateTimeInterface
    {
        return $this->companyUserRestrictedDate;
    }

    /**
     * @param \DateTimeInterface|null $companyUserRestrictedDate
     * @return $this
     */
    public function setCompanyUserRestrictedDate(?\DateTimeInterface $companyUserRestrictedDate): self
    {
        $this->companyUserRestrictedDate = $companyUserRestrictedDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getSubscriptionCancelledAt(): ?\DateTimeInterface
    {
        return $this->subscriptionCancelledAt;
    }

    /**
     * @param \DateTimeInterface|null $subscriptionCancelledAt
     * @return $this
     */
    public function setSubscriptionCancelledAt(?\DateTimeInterface $subscriptionCancelledAt): self
    {
        $this->subscriptionCancelledAt = $subscriptionCancelledAt;

        return $this;
    }
    
    /**
     * 
     * @return \DateTimeInterface|null
     */
    public function getPlanEndDate(): ?\DateTimeInterface
    {
        return $this->planEndDate;
    }

    /**
     * 
     * @param \DateTimeInterface|null $planEndDate
     * @return self
     */
    public function setPlanEndDate(?\DateTimeInterface $planEndDate): self
    {
        $this->planEndDate = $planEndDate;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsAppUseEnabled(): ?bool
    {
        return $this->isAppUseEnabled;
    }

    /**
     * @param bool $isAppUseEnabled
     * @return $this
     */
    public function setIsAppUseEnabled(bool $isAppUseEnabled): self
    {
        $this->isAppUseEnabled = $isAppUseEnabled;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthCode(): ?string
    {
        return $this->authCode;
    }

    /**
     * @param string|null $authCode
     * @return $this
     */
    public function setAuthCode(?string $authCode): self
    {
        $this->authCode = $authCode;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getAdminEditedDate(): ?\DateTimeInterface
    {
        return $this->adminEditedDate;
    }

    /**
     * @param \DateTimeInterface|null $adminEditedDate
     * @return $this
     */
    public function setAdminEditedDate(?\DateTimeInterface $adminEditedDate): self
    {
        $this->adminEditedDate = $adminEditedDate;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsGuestUser(): ?bool
    {
        return $this->isGuestUser;
    }

    /**
     * @param bool $isGuestUser
     * @return $this
     */
    public function setIsGuestUser(bool $isGuestUser): self
    {
        $this->isGuestUser = $isGuestUser;

        return $this;
    }

    /**
     * @return Collection<int, MessageReadUser>
     */
    public function getMessageReadUsers(): Collection
    {
        return $this->messageReadUsers;
    }

    public function addMessageReadUser(MessageReadUser $messageReadUser): self
    {
        if (!$this->messageReadUsers->contains($messageReadUser)) {
            $this->messageReadUsers[] = $messageReadUser;
            $messageReadUser->setUser($this);
        }

        return $this;
    }

    public function removeMessageReadUser(MessageReadUser $messageReadUser): self
    {
        if ($this->messageReadUsers->removeElement($messageReadUser)) {
            // set the owning side to null (unless already changed)
            if ($messageReadUser->getUser() === $this) {
                $messageReadUser->setUser(null);
            }
        }

        return $this;
    }
}
