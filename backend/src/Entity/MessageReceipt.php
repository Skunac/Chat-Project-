<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\MessageReceiptRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['receipt:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['receipt:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['receipt:write']],
            security: "is_granted('RECEIPT_CREATE', object)"
        ),
        new Put(
            denormalizationContext: ['groups' => ['receipt:update']],
            security: "is_granted('RECEIPT_UPDATE', object)"
        )
    ],
    normalizationContext: ['groups' => ['receipt:read']],
    denormalizationContext: ['groups' => ['receipt:write']]
)]
#[ORM\Entity(repositoryClass: MessageReceiptRepository::class)]
#[ORM\UniqueConstraint(
    name: 'unique_message_user',
    columns: ['message_id', 'user_id']
)]
class MessageReceipt
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['receipt:collection:read', 'receipt:item:read'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'receipts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['receipt:collection:read', 'receipt:item:read', 'receipt:write'])]
    private ?Message $message = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['receipt:collection:read', 'receipt:item:read', 'receipt:write'])]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['receipt:collection:read', 'receipt:item:read', 'receipt:write', 'receipt:update'])]
    private ?\DateTimeInterface $deliveredAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['receipt:collection:read', 'receipt:item:read', 'receipt:write', 'receipt:update', 'message:item:read'])]
    private ?\DateTimeInterface $readAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeInterface $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeInterface $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }
}