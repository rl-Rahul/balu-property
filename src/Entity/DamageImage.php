<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Expose;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Repository\DamageImageRepository;

/**
 * DamageImage
 *
 * @ORM\Entity(repositoryClass=DamageImageRepository::class)
 */
class DamageImage extends Base implements ReturnableDocumentInterface
{
    /**
     * @ORM\Column(type="string", length=180, nullable=false)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private ?string $path;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $imageCategory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $mimeType;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damageImages")
     */
    private ?Damage $damage;

    /**
     * @ORM\OneToMany(targetEntity=DamageOffer::class, mappedBy="attachment")
     */
    private Collection $damageOffers;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isEditable;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $fileSize;

    /**
     * @ORM\ManyToOne(targetEntity=Folder::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Folder $folder;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private  ?string $displayName;

    /**
     * DamageImage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->damageOffers = new ArrayCollection();
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
            $damageOffer->setAttachment($this);
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
            if ($damageOffer->getAttachment() === $this) {
                $damageOffer->setAttachment(null);
            }
        }

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
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getImageCategory(): ?int
    {
        return $this->imageCategory;
    }

    /**
     * @param int|null $imageCategory
     * @return $this
     */
    public function setImageCategory(?int $imageCategory): self
    {
        $this->imageCategory = $imageCategory;

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

    /**
     * @return bool|null 
     */
    public function getIsEditable(): ?bool
    {
        return $this->isEditable;
    }
   
    /**
     * @param bool $isEditable
     * @return $this
     */
    public function setIsEditable(bool $isEditable): self
    {
        $this->isEditable = $isEditable;

        return $this;
    }
    /**
     * @param float $fileSize
     * @return $this
     */
    public function getFileSize(): ?float
    {
        return $this->fileSize;
    }
    /**
     * @param float $fileSize
     * @return $this
     */
    public function setFileSize(?float $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }
    /**
     * @param Folder $folder
     * @return $this
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
    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }
}
