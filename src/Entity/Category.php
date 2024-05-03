<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 */
class Category extends Base
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private string $nameDe;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private string $sortOrder;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $icon;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $active;

    /**
     * @ORM\ManyToMany(targetEntity=UserIdentity::class, inversedBy="categories", cascade={"persist", "remove" })
     * @ORM\JoinTable(
     *     name="balu_company_category",
     *     joinColumns={
     *          @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *     }
     * )
     */
    private Collection $user;

    /**
     * @ORM\OneToMany(targetEntity=Damage::class, mappedBy="issueType")
     */
    private Collection $damages;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->user = new ArrayCollection();
        $this->damages = new ArrayCollection();
    }

    /**
     * @return Collection|UserIdentity[]
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    /**
     * @param UserIdentity $user
     * @return $this
     */
    public function addUser(UserIdentity $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
        }

        return $this;
    }

    /**
     * @param UserIdentity $user
     * @return $this
     */
    public function removeUser(UserIdentity $user): self
    {
        $this->user->removeElement($user);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNameDe(): ?string
    {
        return $this->nameDe;
    }

    /**
     * @param string|null $nameDe
     * @return $this
     */
    public function setNameDe(?string $nameDe): self
    {
        $this->nameDe = $nameDe;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    /**
     * @param string|null $sortOrder
     * @return $this
     */
    public function setSortOrder(?string $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     * @return $this
     */
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool|null $active
     * @return $this
     */
    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection|Damage[]
     */
    public function getDamages(): Collection
    {
        return $this->damages;
    }

    public function addDamage(Damage $damage): self
    {
        if (!$this->damages->contains($damage)) {
            $this->damages[] = $damage;
            $damage->setIssueType($this);
        }

        return $this;
    }

    public function removeDamage(Damage $damage): self
    {
        if ($this->damages->removeElement($damage)) {
            // set the owning side to null (unless already changed)
            if ($damage->getIssueType() === $this) {
                $damage->setIssueType(null);
            }
        }

        return $this;
    }
}