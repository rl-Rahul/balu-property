<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use App\Repository\DamageOfferRepository;

/**
 * DamageOffer
 *
 * @ORM\Entity(repositoryClass=DamageOfferRepository::class)
 * @ExclusionPolicy("all")
 */
class DamageOffer extends Base
{
    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     * @Expose
     */
    private ?string $description;

    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     * @Expose
     */
    private ?float $amount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Expose
     */
    private bool $accepted = true;
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @Expose
     */
    private bool $active = false;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damageOffers")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=DamageImage::class, inversedBy="damageOffers")
     */
    private ?DamageImage $attachment;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damageOffers")
     */
    private ?UserIdentity $company;

    /**
     * @ORM\OneToMany(targetEntity=DamageOfferField::class, mappedBy="offer")
     */
    private Collection $damageOfferFields;

    /**
     * @ORM\ManyToOne(targetEntity=DamageRequest::class)
     */
    private ?DamageRequest $damageRequest;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $acceptedDate;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $priceSplit = [];

    /**
     * DamageOffer constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->damageOfferFields = new ArrayCollection();
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
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount
     * @return $this
     */
    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAccepted(): ?bool
    {
        return $this->accepted;
    }

    /**
     * @param bool|null $accepted
     * @return $this
     */
    public function setAccepted(?bool $accepted): self
    {
        $this->accepted = $accepted;

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
     * @param bool $active
     * @return $this
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

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
     * @return DamageImage|null
     */
    public function getAttachment(): ?DamageImage
    {
        return $this->attachment;
    }

    /**
     * @param DamageImage|null $attachment
     * @return $this
     */
    public function setAttachment(?DamageImage $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getCompany(): ?UserIdentity
    {
        return $this->company;
    }

    /**
     * @param UserIdentity|null $company
     * @return $this
     */
    public function setCompany(?UserIdentity $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Collection|DamageOfferField[]
     */
    public function getDamageOfferFields(): Collection
    {
        return $this->damageOfferFields;
    }

    /**
     * @param DamageOfferField $damageOfferField
     * @return $this
     */
    public function addDamageOfferField(DamageOfferField $damageOfferField): self
    {
        if (!$this->damageOfferFields->contains($damageOfferField)) {
            $this->damageOfferFields[] = $damageOfferField;
            $damageOfferField->setOffer($this);
        }

        return $this;
    }

    /**
     * @param DamageOfferField $damageOfferField
     * @return $this
     */
    public function removeDamageOfferField(DamageOfferField $damageOfferField): self
    {
        if ($this->damageOfferFields->removeElement($damageOfferField)) {
            // set the owning side to null (unless already changed)
            if ($damageOfferField->getOffer() === $this) {
                $damageOfferField->setOffer(null);
            }
        }

        return $this;
    }

    public function getDamageRequest(): ?DamageRequest
    {
        return $this->damageRequest;
    }

    public function setDamageRequest(?DamageRequest $damageRequest): self
    {
        $this->damageRequest = $damageRequest;

        return $this;
    }

    public function getAcceptedDate(): ?\DateTimeInterface
    {
        return $this->acceptedDate;
    }

    public function setAcceptedDate(?\DateTimeInterface $acceptedDate): self
    {
        $this->acceptedDate = $acceptedDate;

        return $this;
    }

    public function getPriceSplit(): ?array
    {
        return $this->priceSplit;
    }

    public function setPriceSplit(?array $priceSplit): self
    {
        $this->priceSplit = $priceSplit;

        return $this;
    }
}
