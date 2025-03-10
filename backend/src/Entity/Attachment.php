<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['attachment:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['attachment:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['attachment:write']],
            security: "is_granted('ATTACHMENT_CREATE', object)"
        ),
        new Delete(
            security: "is_granted('ATTACHMENT_DELETE', object)"
        )
    ],
    normalizationContext: ['groups' => ['attachment:read']],
    denormalizationContext: ['groups' => ['attachment:write']]
)]
#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
class Attachment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['attachment:collection:read', 'attachment:item:read', 'message:item:read'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['attachment:collection:read', 'attachment:item:read', 'attachment:write'])]
    private ?Message $message = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['attachment:collection:read', 'attachment:item:read', 'attachment:write', 'message:item:read'])]
    #[Assert\NotBlank(message: 'File name cannot be blank')]
    #[Assert\Length(max: 255, maxMessage: 'File name cannot be longer than {{ limit }} characters')]
    private string $fileName = '';

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['attachment:collection:read', 'attachment:item:read', 'attachment:write', 'message:item:read'])]
    #[Assert\NotBlank(message: 'File type cannot be blank')]
    #[Assert\Length(max: 100, maxMessage: 'File type cannot be longer than {{ limit }} characters')]
    private string $fileType = '';

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['attachment:collection:read', 'attachment:item:read', 'attachment:write', 'message:item:read'])]
    #[Assert\NotBlank(message: 'File size cannot be blank')]
    #[Assert\Length(max: 50, maxMessage: 'File size cannot be longer than {{ limit }} characters')]
    private string $fileSize = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['attachment:item:read', 'attachment:write'])]
    #[Assert\NotBlank(message: 'Storage path cannot be blank')]
    #[Assert\Length(max: 255, maxMessage: 'Storage path cannot be longer than {{ limit }} characters')]
    private string $storagePath = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['attachment:item:read', 'attachment:write', 'message:item:read'])]
    #[Assert\Length(max: 255, maxMessage: 'Thumbnail path cannot be longer than {{ limit }} characters')]
    private ?string $thumbnailPath = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['attachment:collection:read', 'attachment:item:read', 'message:item:read'])]
    private \DateTimeInterface $uploadedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['attachment:item:read', 'attachment:write'])]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['attachment:item:read', 'attachment:write', 'message:item:read'])]
    private array $metadata = [];

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->uploadedAt = new \DateTime();
        $this->metadata = [];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): static
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getFileSize(): string
    {
        return $this->fileSize;
    }

    public function setFileSize(string $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): static
    {
        $this->storagePath = $storagePath;
        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): static
    {
        $this->thumbnailPath = $thumbnailPath;
        return $this;
    }

    public function getUploadedAt(): \DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }
}