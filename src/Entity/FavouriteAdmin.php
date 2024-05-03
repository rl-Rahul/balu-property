<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Interfaces\FavouriteInterface;
use Doctrine\Common\Annotations;

/**
 * FavouriteAdmin
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="", fields={"user", "favouriteAdmin"})})
 * @ORM\Entity
 */
class FavouriteAdmin extends Base implements FavouriteInterface
{
    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="favouriteAdmins")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="favouriteAdmins")
     */
    private ?UserIdentity $favouriteAdmin;

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
    public function getFavouriteAdmin(): ?UserIdentity
    {
        return $this->favouriteAdmin;
    }

    /**
     * @param UserIdentity|null $favouriteAdmin
     * @return $this
     */
    public function setFavouriteAdmin(?UserIdentity $favouriteAdmin): self
    {
        $this->favouriteAdmin = $favouriteAdmin;

        return $this;
    }

}
