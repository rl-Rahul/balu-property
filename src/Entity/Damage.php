<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DamageRepository;

/**
 * Damage
 *
 * @ORM\Entity(repositoryClass=DamageRepository::class)
 */
class Damage extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=false)
     */
    private ?string $title;

    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private ?string $description;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $location;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $floor;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isDeviceAffected = false;

    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private ?string $repair;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $barCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $internalReferenceNumber;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $signature = true;

    /**
     * @ORM\OneToMany(targetEntity=CompanyRating::class, mappedBy="damage")
     */
    private Collection $companyRatings;

    /**
     * @ORM\ManyToOne(targetEntity=DamageStatus::class, inversedBy="damages")
     */
    private ?DamageStatus $status;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damages")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="damages")
     */
    private ?Apartment $apartment;

    /**
     * @ORM\ManyToOne(targetEntity=DamageType::class, inversedBy="damages")
     */
    private ?DamageType $damageType;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damages")
     */
    private ?UserIdentity $preferredCompany;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damages")
     */
    private ?UserIdentity $assignedCompany = null;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damages")
     */
    private ?UserIdentity $companyAssignedBy = null;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damages")
     */
    private ?Damage $parentDamage;

    /**
     * @ORM\OneToMany(targetEntity=Damage::class, mappedBy="parentDamage")
     */
    private Collection $damages;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damages")
     */
    private ?Damage $childDamage;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damages")
     */
    private ?UserIdentity $damageOwner;

    /**
     * @ORM\OneToMany(targetEntity=DamageAppointment::class, mappedBy="damage")
     */
    private Collection $damageAppointments;

    /**
     * @ORM\OneToMany(targetEntity=DamageComment::class, mappedBy="damage")
     */
    private Collection $damageComments;

    /**
     * @ORM\OneToMany(targetEntity=DamageDefect::class, mappedBy="damage")
     */
    private Collection $damageDefects;

    /**
     * @ORM\OneToMany(targetEntity=DamageImage::class, mappedBy="damage")
     */
    private Collection $damageImages;

    /**
     * @ORM\OneToMany(targetEntity=DamageLog::class, mappedBy="damage")
     */
    private Collection $damageLogs;

    /**
     * @ORM\OneToMany(targetEntity=DamageOffer::class, mappedBy="damage")
     */
    private Collection $damageOffers;

    /**
     * @ORM\OneToMany(targetEntity=PushNotification::class, mappedBy="damage")
     */
    private Collection $pushNotifications;

    /**
     * @ORM\OneToOne(targetEntity=Folder::class, cascade={"persist", "remove"})
     */
    private ?Folder $folder = null;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class)
     */
    private Collection $users;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class)
     * @ORM\JoinTable(name="damage_read")
     */
    private Collection $readUsers;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $createdByRole;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $companyAssignedByRole;

    /**
     * @ORM\ManyToOne(targetEntity=userIdentity::class)
     */
    private ?userIdentity $currentUser;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $currentUserRole;

    /**
     * @ORM\Column(type="boolean", options={"comment":"Ticket allocation"}))
     */
    private ?bool $allocation = false;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="damages")
     */
    private ?Category $issueType;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isJanitorEnabled = false;

    /**
     * @ORM\OneToMany(targetEntity=MessageReadUser::class, mappedBy="damage")
     */
    private Collection $messageReadUsers;

    /**
     * Damage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->companyRatings = new ArrayCollection();
        $this->damages = new ArrayCollection();
        $this->damageAppointments = new ArrayCollection();
        $this->damageComments = new ArrayCollection();
        $this->damageDefects = new ArrayCollection();
        $this->damageImages = new ArrayCollection();
        $this->damageLogs = new ArrayCollection();
        $this->damageOffers = new ArrayCollection();
        $this->pushNotifications = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->readUsers = new ArrayCollection();
        $this->messageReadUsers = new ArrayCollection(); 
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
            $damageAppointment->setDamage($this);
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
            if ($damageAppointment->getDamage() === $this) {
                $damageAppointment->setDamage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageComment[]
     */
    public function getDamageComments(): Collection
    {
        return $this->damageComments;
    }

    /**
     * @param DamageComment $damageComment
     * @return $this
     */
    public function addDamageComment(DamageComment $damageComment): self
    {
        if (!$this->damageComments->contains($damageComment)) {
            $this->damageComments[] = $damageComment;
            $damageComment->setDamage($this);
        }

        return $this;
    }

    /**
     * @param DamageComment $damageComment
     * @return $this
     */
    public function removeDamageComment(DamageComment $damageComment): self
    {
        if ($this->damageComments->removeElement($damageComment)) {
            // set the owning side to null (unless already changed)
            if ($damageComment->getDamage() === $this) {
                $damageComment->setDamage(null);
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
            $damageDefect->setDamage($this);
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
            if ($damageDefect->getDamage() === $this) {
                $damageDefect->setDamage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageImage[]
     */
    public function getDamageImages(): Collection
    {
        return $this->damageImages;
    }

    /**
     * @param DamageImage $damageImage
     * @return $this
     */
    public function addDamageImage(DamageImage $damageImage): self
    {
        if (!$this->damageImages->contains($damageImage)) {
            $this->damageImages[] = $damageImage;
            $damageImage->setDamage($this);
        }

        return $this;
    }

    /**
     * @param DamageImage $damageImage
     * @return $this
     */
    public function removeDamageImage(DamageImage $damageImage): self
    {
        if ($this->damageImages->removeElement($damageImage)) {
            // set the owning side to null (unless already changed)
            if ($damageImage->getDamage() === $this) {
                $damageImage->setDamage(null);
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
            $damageLog->setDamage($this);
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
            if ($damageLog->getDamage() === $this) {
                $damageLog->setDamage(null);
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
            $damageOffer->setDamage($this);
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
            if ($damageOffer->getDamage() === $this) {
                $damageOffer->setDamage(null);
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
            $pushNotification->setDamage($this);
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
            if ($pushNotification->getDamage() === $this) {
                $pushNotification->setDamage(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     * @return $this
     */
    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFloor(): ?string
    {
        return $this->floor;
    }

    /**
     * @param string|null $floor
     * @return $this
     */
    public function setFloor(?string $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsDeviceAffected(): ?bool
    {
        return $this->isDeviceAffected;
    }

    /**
     * @param bool|null $isDeviceAffected   
     * @return $this
     */
    public function setIsDeviceAffected(?bool $isDeviceAffected): self
    {
        $this->isDeviceAffected = $isDeviceAffected;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRepair(): ?string
    {
        return $this->repair;
    }

    /**
     * @param string|null $repair
     * @return $this
     */
    public function setRepair(?string $repair): self
    {
        $this->repair = $repair;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBarCode(): ?string
    {
        return $this->barCode;
    }

    /**
     * @param string|null $barCode
     * @return $this
     */
    public function setBarCode(?string $barCode): self
    {
        $this->barCode = $barCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalReferenceNumber(): ?string
    {
        return $this->internalReferenceNumber;
    }

    /**
     * @param string|null $internalReferenceNumber
     * @return $this
     */
    public function setInternalReferenceNumber(?string $internalReferenceNumber): self
    {
        $this->internalReferenceNumber = $internalReferenceNumber;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSignature(): ?bool
    {
        return $this->signature;
    }

    /**
     * @param bool $signature
     * @return $this
     */
    public function setSignature(bool $signature): self
    {
        $this->signature = $signature;

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
            $companyRating->setDamage($this);
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
            if ($companyRating->getDamage() === $this) {
                $companyRating->setDamage(null);
            }
        }

        return $this;
    }

    /**
     * @return DamageStatus|null
     */
    public function getStatus(): ?DamageStatus
    {
        return $this->status;
    }

    /**
     * @param DamageStatus|null $status
     * @return $this
     */
    public function setStatus(?DamageStatus $status): self
    {
        $this->status = $status;

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
     * @return Apartment|null
     */
    public function getApartment(): ?Apartment
    {
        return $this->apartment;
    }

    /**
     * @param Apartment|null $apartment
     * @return $this
     */
    public function setApartment(?Apartment $apartment): self
    {
        $this->apartment = $apartment;

        return $this;
    }

    /**
     * @return DamageType|null
     */
    public function getDamageType(): ?DamageType
    {
        return $this->damageType;
    }

    /**
     * @param DamageType|null $damageType
     * @return $this
     */
    public function setDamageType(?DamageType $damageType): self
    {
        $this->damageType = $damageType;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getPreferredCompany(): ?UserIdentity
    {
        return $this->preferredCompany;
    }

    /**
     * @param UserIdentity|null $preferredCompany
     * @return $this
     */
    public function setPreferredCompany(?UserIdentity $preferredCompany): self
    {
        $this->preferredCompany = $preferredCompany;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getAssignedCompany(): ?UserIdentity
    {
        return $this->assignedCompany;
    }

    /**
     * @param UserIdentity|null $assignedCompany
     * @return $this
     */
    public function setAssignedCompany(?UserIdentity $assignedCompany): self
    {
        $this->assignedCompany = $assignedCompany;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getCompanyAssignedBy(): ?UserIdentity
    {
        return $this->companyAssignedBy;
    }

    /**
     * @param UserIdentity|null $companyAssignedBy
     * @return $this
     */
    public function setCompanyAssignedBy(?UserIdentity $companyAssignedBy): self
    {
        $this->companyAssignedBy = $companyAssignedBy;

        return $this;
    }

    /**
     * @return $this|null
     */
    public function getParentDamage(): ?self
    {
        return $this->parentDamage;
    }

    /**
     * @param Damage|null $parentDamage
     * @return $this
     */
    public function setParentDamage(?self $parentDamage): self
    {
        $this->parentDamage = $parentDamage;

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
            $damage->setParentDamage($this);
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
            if ($damage->getParentDamage() === $this) {
                $damage->setParentDamage(null);
            }
        }

        return $this;
    }

    /**
     * @return $this|null
     */
    public function getChildDamage(): ?self
    {
        return $this->childDamage;
    }

    /**
     * @param Damage|null $childDamage
     * @return $this
     */
    public function setChildDamage(?self $childDamage): self
    {
        $this->childDamage = $childDamage;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getDamageOwner(): ?UserIdentity
    {
        return $this->damageOwner;
    }

    /**
     * @param UserIdentity|null $damageOwner
     * @return $this
     */
    public function setDamageOwner(?UserIdentity $damageOwner): self
    {
        $this->damageOwner = $damageOwner;

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
     * @param Folder $folder
     * @return $this
     */
    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return Collection|UserIdentity[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserIdentity $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(UserIdentity $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection|UserIdentity[]
     */
    public function getReadUsers(): Collection
    {
        return $this->readUsers;
    }

    public function addReadUser(UserIdentity $readUser): self
    {
        if (!$this->readUsers->contains($readUser)) {
            $this->readUsers[] = $readUser;
        }

        return $this;
    }

    public function removeReadUser(UserIdentity $readUser): self
    {
        $this->readUsers->removeElement($readUser);

        return $this;
    }

    public function getCreatedByRole(): ?Role
    {
        return $this->createdByRole;
    }

    public function setCreatedByRole(?Role $createdByRole): self
    {
        $this->createdByRole = $createdByRole;

        return $this;
    }

    public function getCompanyAssignedByRole(): ?Role
    {
        return $this->companyAssignedByRole;
    }

    public function setCompanyAssignedByRole(?Role $companyAssignedByRole): self
    {
        $this->companyAssignedByRole = $companyAssignedByRole;

        return $this;
    }

    public function getCurrentUser(): ?userIdentity
    {
        return $this->currentUser;
    }

    public function setCurrentUser(?userIdentity $currentUser): self
    {
        $this->currentUser = $currentUser;

        return $this;
    }
    
    /**
     * 
     * @return Role|null
     */
    public function getCurrentUserRole(): ?Role
    {
        return $this->currentUserRole;
    }
    
    /**
     * 
     * @param Role|null $currentUserRole
     * @return self
     */
    public function setCurrentUserRole(?Role $currentUserRole): self
    {
        $this->currentUserRole = $currentUserRole;

        return $this;
    }

    public function getAllocation(): ?bool
    {
        return $this->allocation;
    }

    public function setAllocation(bool $allocation): self
    {
        $this->allocation = $allocation;

        return $this;
    }

    public function getIssueType(): ?Category
    {
        return $this->issueType;
    }

    public function setIssueType(?Category $issueType): self
    {
        $this->issueType = $issueType;

        return $this;
    }

    public function getIsJanitorEnabled(): ?bool
    {
        return $this->isJanitorEnabled;
    }

    public function setIsJanitorEnabled(bool $isJanitorEnabled): self
    {
        $this->isJanitorEnabled = $isJanitorEnabled;

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
            $messageReadUser->setDamage($this);
        }

        return $this;
    }

    public function removeMessageReadUser(MessageReadUser $messageReadUser): self
    {
        if ($this->messageReadUsers->removeElement($messageReadUser)) {
            // set the owning side to null (unless already changed)
            if ($messageReadUser->getDamage() === $this) {
                $messageReadUser->setDamage(null);
            }
        }

        return $this;
    }
}
