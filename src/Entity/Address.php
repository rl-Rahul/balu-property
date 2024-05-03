<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AddressRepository::class)
 */
class Address extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $street;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $streetNumber;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $city;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $state;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $country;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $countryCode;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $zipCode;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $phone;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $landLine;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $latitude;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $longitude;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="addresses")
     */
    private ?UserIdentity $user;

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     * @return $this
     */
    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    /**
     * @param string|null $streetNumber
     * @return $this
     */
    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return $this
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     * @return $this
     */
    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     * @return $this
     */
    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * @param string|null $countryCode
     * @return $this
     */
    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string|null $zipCode
     * @return $this
     */
    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return $this
     */
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLandLine(): ?string
    {
        return $this->landLine;
    }

    /**
     * @param string|null $landLine
     * @return $this
     */
    public function setLandLine(?string $landLine): self
    {
        $this->landLine = $landLine;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * @param string|null $latitude
     * @return $this
     */
    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
     * @param string|null $longitude
     * @return $this
     */
    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

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
