<?php

namespace App\Entity;

use App\Repository\ObjectContractsRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass=ObjectContractsRepository::class)
 */
class ObjectContracts extends Base
{

    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="objectContracts")
     * @ORM\JoinColumn(nullable=false)
     */
    private Apartment $object;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $endDate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $additionalComment;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $ownerVote;

    /**
     * @ORM\ManyToOne(targetEntity=NoticePeriod::class, inversedBy="objectContracts")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?NoticePeriod $noticePeriod;

    /**
     * @ORM\ManyToOne(targetEntity=RentalTypes::class, inversedBy="objectContracts")
     * @ORM\JoinColumn(nullable=true)     
     */
    private ?RentalTypes $rentalType = NULL;
    
    /**
     * @ORM\OneToMany(targetEntity=PropertyUser::class, mappedBy="contract")
     */
    private Collection $propertyUsers;

    /**
     * @ORM\OneToOne(targetEntity=Folder::class)
     */
    private ?Folder $folder;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $active;
    
    /**
     * @ORM\OneToMany(targetEntity=Document::class, mappedBy="property")
     */
    private Collection $documents;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $noticeReceiptDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private  ?\DateTime $terminationDate;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $terminatedBy;

    /**
     * @ORM\OneToMany(targetEntity=ObjectContractsLog::class, mappedBy="contract", orphanRemoval=true)
     */
    private Collection $objectContractsLogs;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $actualEndDate;

    /**
     * @ORM\Column(type="smallint")
     */
    private ?int $status;
    
    /**
     * constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->propertyUsers = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->objectContractsLogs = new ArrayCollection();
    }
    /**
     * 
     * @return Apartment|null
     */
    public function getObject(): ?Apartment
    {
        return $this->object;
    }
    
    /**
     * 
     * @param Apartment|null $object
     * @return self
     */
    public function setObject(?Apartment $object): self
    {
        $this->object = $object;

        return $this;
    }
    
    /**
     * 
     * @return \DateTimeInterface|null
     */
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }
    
    /**
     * 
     * @param \DateTimeInterface|null $startDate
     * @return self
     */
    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }
    
    /**
     * 
     * @return \DateTimeInterface|null
     */
    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }
    
    /**
     * 
     * @param \DateTimeInterface|null $endDate
     * @return self
     */
    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getAdditionalComment(): ?string
    {
        return $this->additionalComment;
    }
    
    /**
     * 
     * @param string|null $additionalComment
     * @return self
     */
    public function setAdditionalComment(?string $additionalComment): self
    {
        $this->additionalComment = $additionalComment;

        return $this;
    }
    
    /**
     * 
     * @return bool|null
     */
    public function getOwnerVote(): ?bool
    {
        return $this->ownerVote;
    }
    
    /**
     * 
     * @param bool|null $ownerVote
     * @return self
     */
    public function setOwnerVote(?bool $ownerVote): self
    {
        $this->ownerVote = $ownerVote;

        return $this;
    }

    public function getNoticePeriod(): ?NoticePeriod
    {
        return $this->noticePeriod;
    }
    
    /**
     * 
     * @param NoticePeriod|null $noticePeriod
     * @return self
     */
    public function setNoticePeriod(?NoticePeriod $noticePeriod): self
    {
        $this->noticePeriod = $noticePeriod;

        return $this;
    }
    
    /**
     * 
     * @return RentalTypes|null
     */
    public function getRentalType(): ?RentalTypes
    {
        return $this->rentalType;
    }
    
    /**
     * 
     * @param RentalTypes|null $rentalType
     * @return self
     */
    public function setRentalType(?RentalTypes $rentalType): self
    {
        $this->rentalType = $rentalType;

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
     * 
     * @param PropertyUser $propertyUser
     * @return self
     */
    public function addPropertyUser(PropertyUser $propertyUser): self
    {
        if (!$this->propertyUsers->contains($propertyUser)) {
            $this->propertyUsers[] = $propertyUser;
            $propertyUser->setContract($this);
        }

        return $this;
    }
    
    /**
     * 
     * @param PropertyUser $propertyUser
     * @return self
     */
    public function removePropertyUser(PropertyUser $propertyUser): self
    {
        if ($this->propertyUsers->removeElement($propertyUser)) {
            // set the owning side to null (unless already changed)
            if ($propertyUser->getContract() === $this) {
                $propertyUser->setContract(null);
            }
        }

        return $this;
    }
    
    /**
     * 
     * @return Folder|null
     */
    public function getFolder(): ?Folder
    {
        return $this->folder;
    }
    
    /**
     * 
     * @param Folder|null $folder
     * @return self
     */
    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }
    
    /**
     * 
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }
    
    /**
     * 
     * @param bool|null $active
     * @return self
     */
    public function setActive(?bool $active): self
    {
        $this->active = $active;

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
     * 
     * @return \DateTimeInterface|null
     */
    public function getNoticeReceiptDate(): ?\DateTimeInterface
    {
        return $this->noticeReceiptDate;
    }
    /**
     * 
     * @param \DateTimeInterface|null $noticeReceiptDate
     * @return self
     */
    public function setNoticeReceiptDate(?\DateTimeInterface $noticeReceiptDate): self
    {
        $this->noticeReceiptDate = $noticeReceiptDate;

        return $this;
    }
    
    /**
     * 
     * @return \DateTimeInterface|null
     */
    public function getTerminationDate(): ?\DateTimeInterface
    {
        return $this->terminationDate;
    }
    
    /**
     * 
     * @param \DateTimeInterface|null $terminationDate
     * @return self
     */
    public function setTerminationDate(?\DateTimeInterface $terminationDate): self
    {
        $this->terminationDate = $terminationDate;

        return $this;
    }
    
    /**
     * 
     * @return UserIdentity|null
     */
    public function getTerminatedBy(): ?UserIdentity
    {
        return $this->terminatedBy;
    }
    
    /**
     * 
     * @param UserIdentity|null $terminatedBy
     * @return self
     */
    public function setTerminatedBy(?UserIdentity $terminatedBy): self
    {
        $this->terminatedBy = $terminatedBy;

        return $this;
    }

    /**
     * @return Collection|ObjectContractsLog[]
     */
    public function getObjectContractsLogs(): Collection
    {
        return $this->objectContractsLogs;
    }

    public function addObjectContractsLog(ObjectContractsLog $objectContractsLog): self
    {
        if (!$this->objectContractsLogs->contains($objectContractsLog)) {
            $this->objectContractsLogs[] = $objectContractsLog;
            $objectContractsLog->setContract($this);
        }

        return $this;
    }

    public function removeObjectContractsLog(ObjectContractsLog $objectContractsLog): self
    {
        if ($this->objectContractsLogs->removeElement($objectContractsLog)) {
            // set the owning side to null (unless already changed)
            if ($objectContractsLog->getContract() === $this) {
                $objectContractsLog->setContract(null);
            }
        }

        return $this;
    }

    public function getActualEndDate(): ?\DateTimeInterface
    {
        return $this->actualEndDate;
    }

    public function setActualEndDate(?\DateTimeInterface $actualEndDate): self
    {
        $this->actualEndDate = $actualEndDate;

        return $this;
    }
    
    /**
     * 
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }
    
    /**
     * 
     * @param int $status
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
