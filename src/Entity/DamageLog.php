<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DamageLogRepository;

/**
 * DamageLog
 *
 * @ORM\Entity(repositoryClass=DamageLogRepository::class)
 */
class DamageLog extends Base
{
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $comment;

    /**
     * @ORM\ManyToOne(targetEntity=DamageStatus::class, inversedBy="damageLogs")
     */
    private ?DamageStatus $status;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="damageLogs")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="damageLogs")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $assignedCompany;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $preferredCompany;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $responsibles = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $statusText = [];

    /**
     * @ORM\ManyToOne(targetEntity=DamageOffer::class)
     */
    private $offer;

    /**
     * @ORM\ManyToOne(targetEntity=DamageRequest::class)
     */
    private $request;

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

    /**
     * @return UserIdentity|null
     */
    public function getAssignedCompany(): ?UserIdentity
    {
        return $this->assignedCompany;
    }

    /**
     * @param UserIdentity|null $assignedCompany
     * @return $this
     */
    public function setAssignedCompany(?UserIdentity $assignedCompany): self
    {
        $this->assignedCompany = $assignedCompany;

        return $this;
    }

    /**
     * @return UserIdentity|null
     */
    public function getPreferredCompany(): ?UserIdentity
    {
        return $this->preferredCompany;
    }

    /**
     * @param UserIdentity|null $preferredCompany
     * @return $this
     */
    public function setPreferredCompany(?UserIdentity $preferredCompany): self
    {
        $this->preferredCompany = $preferredCompany;

        return $this;
    }

    public function getResponsibles(): ?array
    {
        return $this->responsibles;
    }

    public function setResponsibles(?array $responsibles): self
    {
        $this->responsibles = $responsibles;

        return $this;
    }

    public function getStatusText(): ?array
    {
        return $this->statusText;
    }

    public function setStatusText(?array $statusText): self
    {
        $this->statusText = $statusText;

        return $this;
    }

    public function getOffer(): ?DamageOffer
    {
        return $this->offer;
    }

    public function setOffer(?DamageOffer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function getRequest(): ?DamageRequest
    {
        return $this->request;
    }

    public function setRequest(?DamageRequest $request): self
    {
        $this->request = $request;

        return $this;
    }
}
