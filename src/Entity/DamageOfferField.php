<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * DamageOfferField
 *
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class DamageOfferField extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @Expose
     */
    private ?string $label;

    /**
     * @ORM\Column(type="float", precision=10, scale=0, nullable=true)
     * @Expose
     */
    private ?float $amount;

    /**
     * @ORM\ManyToOne(targetEntity=DamageOffer::class, inversedBy="damageOfferFields")
     */
    private ?DamageOffer $offer;

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     * @return $this
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

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
     * @return DamageOffer|null
     */
    public function getOffer(): ?DamageOffer
    {
        return $this->offer;
    }

    /**
     * @param DamageOffer|null $offer
     * @return $this
     */
    public function setOffer(?DamageOffer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }
}
