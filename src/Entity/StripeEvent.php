<?php

namespace App\Entity;

use App\Entity\Base\Base;
use Doctrine\ORM\Mapping as ORM;

/**
 * StripeEvent
 *
 * @ORM\Entity
 */
class StripeEvent extends Base
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $eventId;

    /**
     * @return string|null
     */
    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    /**
     * @param string $eventId
     * @return $this
     */
    public function setEventId(string $eventId): self
    {
        $this->eventId = $eventId;

        return $this;
    }
}

