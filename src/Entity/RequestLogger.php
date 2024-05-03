<?php

namespace App\Entity;

use App\Repository\RequestLoggerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RequestLoggerRepository::class)
 */
class RequestLogger 
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $identifier;
    
    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $requestParam = [];

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $clientIp;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $uri;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private ?string $requestMethod;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $requestedAt;

    public function __construct()
    {
        $this->requestedAt = new \DateTime();
    }

    /**
     * Get RequestParam
     *
     * @return array|null
     */
    public function getRequestParam(): ?array
    {
        return $this->requestParam;
    }

    /**
     * Set RequestParam
     *
     * @param array $requestParam
     * @return $this
     */
    public function setRequestParam(array $requestParam): self
    {
        $this->requestParam = $requestParam;

        return $this;
    }

    /**
     * Get Client Ip
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    /**
     * Set ClientIp
     *
     * @param string $clientIp
     * @return $this
     */
    public function setClientIp(string $clientIp): self
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * Get Uri
     *
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Set Uri
     *
     * @param string $uri
     * @return $this
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get RequestedAt
     *
     * @return \DateTimeInterface|null
     */
    public function getRequestedAt(): ?\DateTimeInterface
    {
        return $this->requestedAt;
    }

    /**
     * Set RequestedAt
     *
     * @param \DateTimeInterface $requestedAt
     * @return $this
     */
    public function setRequestedAt(\DateTimeInterface $requestedAt): self
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    /**
     * Get RequestMethod
     *
     * @return string|null
     */
    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    /**
     * Set RequestMethod
     *
     * @param string $requestMethod
     * @return $this
     */
    public function setRequestMethod(string $requestMethod): self
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }
}
