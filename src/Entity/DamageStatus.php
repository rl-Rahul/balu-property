<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DamageStatusRepository;

/**
 * DamageStatus
 *
 * @ORM\Entity(repositoryClass=DamageStatusRepository::class)
 */
class DamageStatus extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=false)
     */
    private ?string $status;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $active = true;
    
    /**
     * @ORM\Column(type="string", length=180, name="status_key")
     */
    private ?string $key;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $commentRequired;

    /**
     * @ORM\OneToMany(targetEntity=Damage::class, mappedBy="status")
     */
    private Collection $damages;

    /**
     * @ORM\OneToMany(targetEntity=DamageComment::class, mappedBy="status")
     */
    private Collection $damageComments;

    /**
     * @ORM\OneToMany(targetEntity=DamageLog::class, mappedBy="status")
     */
    private Collection $damageLogs;

    /**
     * DamageStatus constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->damages = new ArrayCollection();
        $this->damageComments = new ArrayCollection();
        $this->damageLogs = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

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
     * @param bool|null $active
     * @return $this
     */
    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCommentRequired(): ?bool
    {
        return $this->commentRequired;
    }

    /**
     * @param bool|null $commentRequired
     * @return $this
     */
    public function setCommentRequired(?bool $commentRequired): self
    {
        $this->commentRequired = $commentRequired;

        return $this;
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
            $damage->setStatus($this);
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
            if ($damage->getStatus() === $this) {
                $damage->setStatus(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageComment[]
     */
    public function getDamageComments(): Collection
    {
        return $this->damageComments;
    }

    /**
     * @param DamageComment $damageComment
     * @return $this
     */
    public function addDamageComment(DamageComment $damageComment): self
    {
        if (!$this->damageComments->contains($damageComment)) {
            $this->damageComments[] = $damageComment;
            $damageComment->setStatus($this);
        }

        return $this;
    }

    /**
     * @param DamageComment $damageComment
     * @return $this
     */
    public function removeDamageComment(DamageComment $damageComment): self
    {
        if ($this->damageComments->removeElement($damageComment)) {
            // set the owning side to null (unless already changed)
            if ($damageComment->getStatus() === $this) {
                $damageComment->setStatus(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DamageLog[]
     */
    public function getDamageLogs(): Collection
    {
        return $this->damageLogs;
    }

    /**
     * @param DamageLog $damageLog
     * @return $this
     */
    public function addDamageLog(DamageLog $damageLog): self
    {
        if (!$this->damageLogs->contains($damageLog)) {
            $this->damageLogs[] = $damageLog;
            $damageLog->setStatus($this);
        }

        return $this;
    }

    /**
     * @param DamageLog $damageLog
     * @return $this
     */
    public function removeDamageLog(DamageLog $damageLog): self
    {
        if ($this->damageLogs->removeElement($damageLog)) {
            // set the owning side to null (unless already changed)
            if ($damageLog->getStatus() === $this) {
                $damageLog->setStatus(null);
            }
        }

        return $this;
    }
}
