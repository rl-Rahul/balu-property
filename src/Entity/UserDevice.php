<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserDeviceRepository;

/**
 * @ORM\Entity(repositoryClass=UserDeviceRepository::class)
 */
class UserDevice extends Base
{
   /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $deviceId;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="userDevices")
     */
    private ?UserIdentity $user;

    /**
     * @return string|null
     */
    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    /**
     * @param string|null $deviceId
     * @return $this
     */
    public function setDeviceId(?string $deviceId): self
    {
        $this->deviceId = $deviceId;

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
