<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\PaymentLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentLogRepository::class)
 */
class PaymentLog extends Base
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $amount;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isExpired = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $paymentId;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getIsExpired(): ?bool
    {
        return $this->isExpired;
    }

    public function setIsExpired(bool $isExpired): self
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    public function getUser(): ?UserIdentity
    {
        return $this->user;
    }

    public function setUser(?UserIdentity $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

}
