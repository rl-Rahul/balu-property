<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\PropertyRoleInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PropertyRoleInvitationRepository::class)
 */
class PropertyRoleInvitation extends Base
{

    /**
     * @ORM\ManyToOne(targetEntity=Property::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Property $property;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $invitee;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $invitor;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $role;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $invitationAcceptedDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $invitationRejectedDate;

    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private ?string $reason;

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getInvitee(): ?UserIdentity
    {
        return $this->invitee;
    }

    public function setInvitee(?UserIdentity $invitee): self
    {
        $this->invitee = $invitee;

        return $this;
    }

    public function getInvitor(): ?UserIdentity
    {
        return $this->invitor;
    }

    public function setInvitor(?UserIdentity $invitor): self
    {
        $this->invitor = $invitor;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getInvitationAcceptedDate(): ?\DateTimeInterface
    {
        return $this->invitationAcceptedDate;
    }

    public function setInvitationAcceptedDate(?\DateTimeInterface $invitationAcceptedDate): self
    {
        $this->invitationAcceptedDate = $invitationAcceptedDate;

        return $this;
    }

    public function getInvitationRejectedDate(): ?\DateTimeInterface
    {
        return $this->invitationRejectedDate;
    }

    public function setInvitationRejectedDate(?\DateTimeInterface $invitationRejectedDate): self
    {
        $this->invitationRejectedDate = $invitationRejectedDate;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }
}
