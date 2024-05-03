<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection; 
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MessageRepository::class)
 */
class Message extends Base
{  

    /** 
     * @ORM\ManyToOne(targetEntity=MessageType::class, inversedBy="message") 
     */
    private MessageType $type; 
    
    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="message") 
     */
    private UserIdentity $createdBy;

    /** 
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="message") 
     */
    private ?Damage $damage = null; 

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $archive = false;

    /**
     * @ORM\ManyToMany(targetEntity=Apartment::class)
     */
    private Collection $apartments;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class)
     */
    private Collection $users;
   
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $message = null;
  /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $subject = null;

    /**
     * @ORM\OneToMany(targetEntity=MessageDocument::class, mappedBy="message", orphanRemoval=true)
     */
    private Collection $messageDocuments;

    /**
     * @ORM\OneToOne(targetEntity=Folder::class, cascade={"persist", "remove"})
     */
    private ?Folder $folder = null;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class) 
     * @ORM\JoinTable(name="message_read")
     */
    private Collection $readUsers;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $createdByRole;

    /**
     * @ORM\OneToMany(targetEntity=MessageReadUser::class, mappedBy="message")
     */
    private Collection $messageReadUsers;

    public function __construct()
    {
        parent::__construct();
        $this->apartments = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->messageDocuments = new ArrayCollection();
        $this->readUsers = new ArrayCollection();
        $this->messageReadUsers = new ArrayCollection();
    }
   
    /**
     * @return int|null
     */
    public function getType(): ?MessageType
    {
        return $this->type;
    }
    /**
     * @param int $type
     * @return $this
     */
    public function setType(MessageType $type): self
    {
        $this->type = $type;

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
     * @param UserIdentity $user
     * @return $this
     */
    public function setCreatedBy(?UserIdentity $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
    /**
     * @return Damage|null
     */
    public function getDamage(): ?Damage
    {
        return $this->damage;
    }
    /**
     * @param Damage|null $damage
     * @return $this
     */
    public function setDamage(?Damage $damage): self
    {
        $this->damage = $damage;

        return $this;
    } 

    public function getArchive(): ?bool
    {
        return $this->archive;
    }

    public function setArchive(?bool $archive): self
    {
        $this->archive = $archive;

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
     * @param Apartment Apartment
     * @return $this
     */
    public function addApartment(Apartment $apartment): self
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments[] = $apartment;
        }

        return $this;
    }
    /**
     * @param Apartment Apartment
     * @return $this
     */
    public function removeApartment(Apartment $apartment): self
    {
        $this->apartments->removeElement($apartment);

        return $this;
    }

    /**
     * @return Collection|UserIdentity[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }
    /**
     * @param UserIdentity $user
     * @return $this
     */
    public function addUser(UserIdentity $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }
    /**
     * @param UserIdentity $user
     * @return $this
     */
    public function removeUser(UserIdentity $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }      
    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return Collection|MessageDocument[]
     */
    public function getMessageDocuments(): Collection
    {
        return $this->messageDocuments;
    }

    public function addMessageDocument(MessageDocument $messageDocument): self
    {
        if (!$this->messageDocuments->contains($messageDocument)) {
            $this->messageDocuments[] = $messageDocument;
            $messageDocument->setMessage($this);
        }

        return $this;
    }

    public function removeMessageDocument(MessageDocument $messageDocument): self
    {
        if ($this->messageDocuments->removeElement($messageDocument)) {
            // set the owning side to null (unless already changed)
            if ($messageDocument->getMessage() === $this) {
                $messageDocument->setMessage(null);
            }
        }

        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

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

    public function isArchive(): ?bool
    {
        return $this->archive;
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
            $messageReadUser->setMessage($this);
        }

        return $this;
    }

    public function removeMessageReadUser(MessageReadUser $messageReadUser): self
    {
        if ($this->messageReadUsers->removeElement($messageReadUser)) {
            // set the owning side to null (unless already changed)
            if ($messageReadUser->getMessage() === $this) {
                $messageReadUser->setMessage(null);
            }
        }

        return $this;
    }
}
