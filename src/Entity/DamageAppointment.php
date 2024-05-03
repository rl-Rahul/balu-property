<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DamageAppointmentRepository;

/**
 * DamageAppointment
 *
 * @ORM\Entity(repositoryClass=DamageAppointmentRepository::class)
 */
class DamageAppointment extends Base
{
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private \DateTime $scheduledTime;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $status;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damageAppointments")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damageAppointments")
     */
    private ?UserIdentity $user;

    /**
     * @return \DateTimeInterface|null
     */
    public function getScheduledTime(): ?\DateTimeInterface
    {
        return $this->scheduledTime;
    }

    /**
     * @param \DateTimeInterface $scheduledTime
     * @return $this
     */
    public function setScheduledTime(\DateTimeInterface $scheduledTime): self
    {
        $this->scheduledTime = $scheduledTime;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * @param bool|null $status
     * @return $this
     */
    public function setStatus(?bool $status): self
    {
        $this->status = $status;

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
