<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Interfaces\FavouriteInterface;
use Doctrine\Common\Annotations;

/**
 * BpFavouriteCompany
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="", fields={"user", "favouriteCompany"})})
 * @ORM\Entity
 */
class FavouriteCompany extends Base implements FavouriteInterface
{
    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="favouriteCompanies")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="favouriteCompanies")
     */
    private ?UserIdentity $favouriteCompany;

    /**
     * @ORM\ManyToOne(targetEntity=Property::class, inversedBy="favouriteCompanies")
     */
    private ?Property $property;

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
    public function getFavouriteCompany(): ?UserIdentity
    {
        return $this->favouriteCompany;
    }

    /**
     * @param UserIdentity|null $favouriteCompany
     * @return $this
     */
    public function setFavouriteCompany(?UserIdentity $favouriteCompany): self
    {
        $this->favouriteCompany = $favouriteCompany;

        return $this;
    }

    /**
     * @return Property|null
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }

    /**
     * @param Property|null $property
     * @return $this
     */
    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }
}
