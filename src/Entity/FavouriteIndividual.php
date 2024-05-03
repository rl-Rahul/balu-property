<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Interfaces\FavouriteInterface;
use Doctrine\Common\Annotations;

/**
 * FavouriteIndividual
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="", fields={"user", "favouriteIndividual"})})
 * @ORM\Entity
 */
class FavouriteIndividual extends Base implements FavouriteInterface
{
    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="favouriteIndividuals")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="favouriteIndividuals")
     */
    private ?UserIdentity $favouriteIndividual;

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
    public function getFavouriteIndividual(): ?UserIdentity
    {
        return $this->favouriteIndividual;
    }

    /**
     * @param UserIdentity|null $favouriteIndividual
     * @return $this
     */
    public function setFavouriteIndividual(?UserIdentity $favouriteIndividual): self
    {
        $this->favouriteIndividual = $favouriteIndividual;

        return $this;
    }

}
