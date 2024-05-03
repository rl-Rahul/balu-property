<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocumentRepository::class)
 */
class Document extends Base implements ReturnableDocumentInterface
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $path;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $type;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isActive = true;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $originalName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $displayName;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $storedPath;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isPrivate = false;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class, inversedBy="documents")
     */
    private ?Property $property;

    /**
     * @ORM\ManyToOne(targetEntity=Apartment::class, inversedBy="documents")
     */
    private ?Apartment $apartment;

    /**
     * @ORM\ManyToOne(targetEntity=Folder::class, inversedBy="documents")
     */
    private ?Folder $folder;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $mimeType;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $size;

    /**
     * @ORM\ManyToOne(targetEntity=ObjectContracts::class, inversedBy="documents")
     */
    private ?ObjectContracts $contract;

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
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     * @return $this
     */
    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @param string $originalName
     * @return $this
     */
    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

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
     * @param string $displayName
     * @return $this
     */
    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStoredPath(): ?string
    {
        return $this->storedPath;
    }

    /**
     * @param string $storedPath
     * @return $this
     */
    public function setStoredPath(string $storedPath): self
    {
        $this->storedPath = $storedPath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     * @return $this
     */
    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getSize(): ?float
    {
        return $this->size;
    }

    /**
     * @param float|null $size
     * @return $this
     */
    public function setSize(?float $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsPrivate(): ?bool
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

    public function getContract(): ?ObjectContracts
    {
        return $this->contract;
    }

    public function setContract(?ObjectContracts $contract): self
    {
        $this->contract = $contract;

        return $this;
    }
}
