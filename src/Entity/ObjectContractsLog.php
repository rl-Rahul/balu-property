<?php

namespace App\Entity;

use App\Repository\ObjectContractsLogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=ObjectContractsLogRepository::class)
 */
class ObjectContractsLog  extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=ObjectContracts::class, inversedBy="objectContractsLogs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $contract;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private ?UserIdentity $updatedBy;

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
    private $objectContractsLogs;

    /**
     * @ORM\OneToMany(targetEntity=ObjectContractsLogUser::class, mappedBy="log")
     */
    private $objectContractsLogUsers;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private ?int $status;
    
    /**
     * constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->propertyUsers = new ArrayCollection();
        $this->objectContractsLogs = new ArrayCollection();
        $this->objectContractsLogUsers = new ArrayCollection();
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
    
    /**
     * 
     * @return ObjectContracts|null
     */
    public function getContract(): ?ObjectContracts
    {
        return $this->contract;
    }
    
    /**
     * 
     * @param ObjectContracts|null $contract
     * @return self
     */
    public function setContract(?ObjectContracts $contract): self
    {
        $this->contract = $contract;

        return $this;
    }
    
    /**
     * 
     * @return UserIdentity|null
     */
    public function getUpdatedBy(): ?UserIdentity
    {
        return $this->updatedBy;
    }
    
    /**
     * 
     * @param UserIdentity|null $updatedBy
     * @return self
     */
    public function setUpdatedBy(?UserIdentity $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return Collection|ObjectContractsLogUser[]
     */
    public function getObjectContractsLogUsers(): Collection
    {
        return $this->objectContractsLogUsers;
    }

    public function addObjectContractsLogUser(ObjectContractsLogUser $objectContractsLogUser): self
    {
        if (!$this->objectContractsLogUsers->contains($objectContractsLogUser)) {
            $this->objectContractsLogUsers[] = $objectContractsLogUser;
            $objectContractsLogUser->setLog($this);
        }

        return $this;
    }

    public function removeObjectContractsLogUser(ObjectContractsLogUser $objectContractsLogUser): self
    {
        if ($this->objectContractsLogUsers->removeElement($objectContractsLogUser)) {
            // set the owning side to null (unless already changed)
            if ($objectContractsLogUser->getLog() === $this) {
                $objectContractsLogUser->setLog(null);
            }
        }

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
     * @param int|null $status
     * @return self
     */
    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
