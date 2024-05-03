<?php

namespace App\Entity;

use App\Repository\MigrationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MigrationRepository::class)
 */
class Temp
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, cascade={"persist", "remove"})
     */
    private ?User $user;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $oldUserId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOldUserId(): ?int
    {
        return $this->oldUserId;
    }

    public function setOldUserId(int $oldUserId): self
    {
        $this->oldUserId = $oldUserId;

        return $this;
    }
}
