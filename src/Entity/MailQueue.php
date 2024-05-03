<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * MailQueue
 *
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class MailQueue extends Base
{
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $mailType;
    
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $subject;
    
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private ?string $toMail;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $bodyText;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?string $failCount;

    /**
     * @return string|null
     */
    public function getMailType(): ?string
    {
        return $this->mailType;
    }

    /**
     * @param string|null $mailType
     * @return $this
     */
    public function setMailType(?string $mailType): self
    {
        $this->mailType = $mailType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     * @return $this
     */
    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getToMail(): ?string
    {
        return $this->toMail;
    }

    /**
     * @param string|null $toMail
     * @return $this
     */
    public function setToMail(?string $toMail): self
    {
        $this->toMail = $toMail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }

    /**
     * @param string|null $bodyText
     * @return $this
     */
    public function setBodyText(?string $bodyText): self
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFailCount(): ?int
    {
        return $this->failCount;
    }

    /**
     * @param int|null $failCount
     * @return $this
     */
    public function setFailCount(?int $failCount): self
    {
        $this->failCount = $failCount;

        return $this;
    }
}
