<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DamageCommentRepository;

/**
 * DamageComment
 *
 * @ORM\Entity(repositoryClass=DamageCommentRepository::class)
 */
class DamageComment extends Base
{
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $currentActive = true;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damageComments")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=DamageStatus::class, inversedBy="damageComments")
     */
    private ?DamageStatus $status;

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCurrentActive(): ?bool
    {
        return $this->currentActive;
    }

    /**
     * @param bool|null $currentActive
     * @return $this
     */
    public function setCurrentActive(?bool $currentActive): self
    {
        $this->currentActive = $currentActive;

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
     * @return DamageStatus|null
     */
    public function getStatus(): ?DamageStatus
    {
        return $this->status;
    }

    /**
     * @param DamageStatus|null $status
     * @return $this
     */
    public function setStatus(?DamageStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
