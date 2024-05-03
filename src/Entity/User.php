<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User extends Base implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @Assert\Email()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private ?string $property;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @ORM\OneToMany(targetEntity=UserPropertyPool::class, mappedBy="user", orphanRemoval=true)
     */
    private ?Collection $authProperty;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $confirmationToken;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default"=0})
     */
    private bool $isTokenVerified = FALSE;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $passwordRequestedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $lastLogin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $firstLogin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $webFirstLogin;

    /**
     * @ORM\Column(nullable=true, options={"default"=0})
     */
    private bool $isPasswordChanged = FALSE;
    
    /**
     * @ORM\OneToOne(targetEntity=UserIdentity::class, mappedBy="user")
     */
    private ?UserIdentity $userIdentity;

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->authProperty = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * @param string $property
     * @return $this
     */
    public function setProperty(string $property): self
    {
        $this->property = $property;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->property;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->property;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return ArrayCollection
     */
    public function getAuthProperty(): ArrayCollection
    {
        return $this->authProperty;
    }

    /**
     * @param UserPropertyPool $authProperty
     * @return $this
     */
    public function addAuthProperty(UserPropertyPool $authProperty): self
    {
        if (!$this->authProperty->contains($authProperty)) {
            $this->authProperty[] = $authProperty;
            $authProperty->setUser($this);
        }

        return $this;
    }

    /**
     * @param UserPropertyPool $authProperty
     * @return $this
     */
    public function removeAuthProperty(UserPropertyPool $authProperty): self
    {
        if ($this->authProperty->removeElement($authProperty)) {
            // set the owning side to null (unless already changed)
            if ($authProperty->getUser() === $this) {
                $authProperty->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    /**
     * @param string|null $confirmationToken
     * @return $this
     */
    public function setConfirmationToken(?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsTokenVerified(): ?bool
    {
        return $this->isTokenVerified;
    }

    /**
     * @param bool|null $isTokenVerified
     * @return $this
     */
    public function setIsTokenVerified(?bool $isTokenVerified): self
    {
        $this->isTokenVerified = $isTokenVerified;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPasswordRequestedAt(): ?\DateTime
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param \DateTimeInterface|null $passwordRequestedAt
     * @return User
     */
    public function setPasswordRequestedAt(?\DateTimeInterface $passwordRequestedAt): self
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTimeInterface|null $lastLogin
     * @return $this
     */
    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getFirstLogin(): ?\DateTimeInterface
    {
        return $this->firstLogin;
    }

    public function setFirstLogin(?\DateTimeInterface $firstLogin): self
    {
        $this->firstLogin = $firstLogin;

        return $this;
    }

    public function getWebFirstLogin(): ?\DateTimeInterface
    {
        return $this->webFirstLogin;
    }

    public function setWebFirstLogin(?\DateTimeInterface $webFirstLogin): self
    {
        $this->webFirstLogin = $webFirstLogin;

        return $this;
    }

    public function getIsPasswordChanged(): ?bool
    {
        return $this->isPasswordChanged;
    }

    public function setIsPasswordChanged(?bool $isPasswordChanged): self
    {
        $this->isPasswordChanged = $isPasswordChanged;

        return $this;
    }
    
    /**
     * @return UserIdentity|null
     */
    public function getUserIdentity(): ?UserIdentity
    {
        return $this->userIdentity;
    }

}
