<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PushNotificationRepository;

/**
 * PushNotification
 * 
 * @ORM\Entity(repositoryClass=PushNotificationRepository::class)
 */
class PushNotification extends Base
{
    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private ?string $message;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $readMessage = false;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="pushNotifications")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="pushNotifications")
     */
    private ?UserIdentity $toUser;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class)
     */
    private ?Role $role;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class)
     */
    private ?Property $property;

    /**
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private ?string $messageDe;

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getReadMessage(): ?bool
    {
        return $this->readMessage;
    }

    /**
     * @param bool|null $readMessage
     * @return $this
     */
    public function setReadMessage(?bool $readMessage): self
    {
        $this->readMessage = $readMessage;

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
    public function getToUser(): ?UserIdentity
    {
        return $this->toUser;
    }

    /**
     * @param UserIdentity|null $toUser
     * @return $this
     */
    public function setToUser(?UserIdentity $toUser): self
    {
        $this->toUser = $toUser;

        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(?string $event): self
    {
        $this->event = $event;

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

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getMessageDe(): ?string
    {
        return $this->messageDe;
    }

    public function setMessageDe(?string $messageDe): self
    {
        $this->messageDe = $messageDe;

        return $this;
    }
}
