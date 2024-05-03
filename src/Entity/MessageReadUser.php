<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\MessageReadUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MessageReadUserRepository::class)
 */
class MessageReadUser extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity=Message::class, inversedBy="messageReadUsers", cascade={"persist"})
     */
    private ?Message $message;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="messageReadUsers")
     */
    private ?Damage $damage;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, inversedBy="messageReadUsers")
     */
    private ?Role $role;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="messageReadUsers")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isRead = false;
    
    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getDamage(): ?Damage
    {
        return $this->damage;
    }

    public function setDamage(?Damage $damage): self
    {
        $this->damage = $damage;

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

    public function isIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

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
}
