<?php

namespace App\Entity;

use App\Repository\MessageDocumentRepository;
use Doctrine\ORM\Mapping as ORM; 
use App\Entity\Base\Base;
use App\Entity\Interfaces\ReturnableDocumentInterface;

/**
 * @ORM\Entity(repositoryClass=MessageDocumentRepository::class)
 */
class MessageDocument extends Base implements ReturnableDocumentInterface
{ 
    /**
     * @ORM\ManyToOne(targetEntity=Message::class, inversedBy="messageDocuments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $displayName;

    /**
     * @ORM\Column(type="text")
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mimeType;

    /**
     * @ORM\Column(type="float")
     */
    private $size;

    /**
     * @ORM\ManyToOne(targetEntity=Folder::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $folder; 

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): ?float
    {
        return $this->size;
    }

    public function setSize(float $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }
}
