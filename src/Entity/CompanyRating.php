<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyRating
 *
 * @ORM\Table(uniqueConstraints={
 *             @ORM\UniqueConstraint(name="unique_rating_entries", columns={"company_id","damage_id"})
 *            })
 * @ORM\Entity()
 */
class CompanyRating extends Base
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $rating;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="companyRatings")
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="companyRatings")
     */
    private ?UserIdentity $company;

    /**
     * @ORM\ManyToOne(targetEntity=Damage::class, inversedBy="companyRatings")
     */
    private ?Damage $damage;

    /**
     * @return int|null
     */
    public function getRating(): ?int
    {
        return $this->rating;
    }

    /**
     * @param int $rating
     * @return $this
     */
    public function setRating(int $rating): self
    {
        $this->rating = $rating;

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
    public function getCompany(): ?UserIdentity
    {
        return $this->company;
    }

    /**
     * @param UserIdentity|null $company
     * @return $this
     */
    public function setCompany(?UserIdentity $company): self
    {
        $this->company = $company;

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
}
