<?php

namespace App\Entity;

use App\Entity\Base\Base;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Repository\TemporaryUploadRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TemporaryUploadRepository::class)
 */
class TemporaryUpload extends Base implements ReturnableDocumentInterface
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $originalFileName;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $fileSize;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $temporaryUploadPath;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $mimeType;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $docType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $filePath;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $localFileName;

    /**
     * @return string|null
     */
    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    /**
     * @param string|null $originalFileName
     * @return $this
     */
    public function setOriginalFileName(?string $originalFileName): self
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getFileSize(): ?float
    {
        return $this->fileSize;
    }

    /**
     * @param float|null $fileSize
     * @return $this
     */
    public function setFileSize(?float $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemporaryUploadPath(): ?string
    {
        return $this->temporaryUploadPath;
    }

    /**
     * @param string|null $temporaryUploadPath
     * @return $this
     */
    public function setTemporaryUploadPath(?string $temporaryUploadPath): self
    {
        $this->temporaryUploadPath = $temporaryUploadPath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     * @return $this
     */
    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDocType(): ?string
    {
        return $this->docType;
    }

    /**
     * @param string|null $docType
     * @return $this
     */
    public function setDocType(?string $docType): self
    {
        $this->docType = $docType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @param string|null $filePath
     * @return $this
     */
    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocalFileName(): ?string
    {
        return $this->localFileName;
    }

    /**
     * @param string|null $localFileName
     * @return $this
     */
    public function setLocalFileName(?string $localFileName): self
    {
        $this->localFileName = $localFileName;

        return $this;
    }
}
