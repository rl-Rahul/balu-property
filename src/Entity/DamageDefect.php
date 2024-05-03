<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DamageDefectRepository;

/**
 * DamageDefect
 *
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="unique_entries", columns={
 *              "damage_id"
 *      })
 *   })
 * @ORM\Entity(repositoryClass=DamageDefectRepository::class)
 */
class DamageDefect extends Base
{
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description;

    /**
     * @ORM\Column(type="string", length=225, nullable=true)
     */
    private ?string $title;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damageDefects")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damageDefects")
     */
    private ?UserIdentity $user;

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
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

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
}
