<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=FeedbackRepository::class)
 */
class Feedback extends Base
{

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $subject;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $message;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class, inversedBy="feedback")
     */
    private ?UserIdentity $sendBy;
    
    /**
     * 
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }
    
    /**
     * 
     * @param string|null $subject
     * @return self
     */
    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
    
    /**
     * 
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
    
    /**
     * 
     * @return UserIdentity|null
     */
    public function getSendBy(): ?UserIdentity
    {
        return $this->sendBy;
    }
    
    /**
     * 
     * @param UserIdentity|null $sendBy
     * @return self
     */
    public function setSendBy(?UserIdentity $sendBy): self
    {
        $this->sendBy = $sendBy;

        return $this;
    }
}
