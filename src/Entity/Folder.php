<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Repository\FolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FolderRepository::class)
 */
class Folder extends Base implements ReturnableDocumentInterface
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $path;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default":0})
     */
    private bool $isPrivate = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isSystemGenerated = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $displayName = null;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="folders")
     */
    private ?UserIdentity $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=Folder::class, inversedBy="folders")
     */
    private ?Folder $parent;

    /**
     * @ORM\OneToMany(targetEntity=Folder::class, mappedBy="parent")
     */
    private Collection $folders;

    /**
     * @ORM\OneToMany(targetEntity=Document::class, mappedBy="folder")
     */
    private Collection $documents;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $displayNameOffset;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $createdRole;

    /**
     * Folder constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->folders = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }
    
    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
    
    /**
     * @param string|null $path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getIsPrivate(): ?string
    {
        return $this->isPrivate;
    }

    /**
     * @param bool $isPrivate
     * @return $this
     */
    public function setIsPrivate(bool $isPrivate): self
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }
    
    /**
     * @return string|null 
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }
    
    /**
     * @param string|null $displayName
     * @return $this
     */
    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

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
    public function getParent(): ?self
    {
        return $this->parent;
    }
    
    /**
     * @param Folder|null $parent
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
    public function getFolders(): Collection
    {
        return $this->folders;
    }
    
    /**
     * @param Folder $folder
     * @return $this
     */
    public function addFolder(self $folder): self
    {
        if (!$this->folders->contains($folder)) {
            $this->folders[] = $folder;
            $folder->setParent($this);
        }

        return $this;
    }
    
    /**
     * @param Folder $folder
     * @return $this
     */
    public function removeFolder(self $folder): self
    {
        if ($this->folders->removeElement($folder)) {
            // set the owning side to null (unless already changed)
            if ($folder->getParent() === $this) {
                $folder->setParent(null);
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

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setFolder($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getFolder() === $this) {
                $document->setFolder(null);
            }
        }

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getDisplayNameOffset(): ?string
    {
        return $this->displayNameOffset;
    }
    
    /**
     * 
     * @param string|null $displayNameOffset
     * @return self
     */
    public function setDisplayNameOffset(?string $displayNameOffset): self
    {
        $this->displayNameOffset = $displayNameOffset;

        return $this;
    }

    public function getCreatedRole(): ?Role
    {
        return $this->createdRole;
    }

    public function setCreatedRole(?Role $createdRole): self
    {
        $this->createdRole = $createdRole;

        return $this;
    }
}
