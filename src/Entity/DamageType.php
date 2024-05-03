<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * DamageType
 *
 * @ORM\Entity
 */
class DamageType extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=false)
     */
    private ?string $name;

     /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $nameDe;

    /**
     * @ORM\OneToMany(targetEntity=Damage::class, mappedBy="damageType")
     */
    private Collection $damages;

    /**
     * DamageType constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->damages = new ArrayCollection();
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
            $damage->setDamageType($this);
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
            if ($damage->getDamageType() === $this) {
                $damage->setDamageType(null);
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
    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    /**
     * @param string|null $nameDe
     * @return $this
     */
    public function setNameDe(?string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }
}
