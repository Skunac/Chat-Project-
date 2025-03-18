<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ConversationReceiptRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['conv_receipt:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['conv_receipt:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['conv_receipt:write']],
            security: "is_granted('CONV_RECEIPT_CREATE', object)"
        ),
        new Put(
            denormalizationContext: ['groups' => ['conv_receipt:update']],
            security: "is_granted('CONV_RECEIPT_UPDATE', object)"
        )
    ],
    normalizationContext: ['groups' => ['conv_receipt:read']],
    denormalizationContext: ['groups' => ['conv_receipt:write']]
)]
#[ORM\Entity(repositoryClass: ConversationReceiptRepository::class)]
#[ORM\UniqueConstraint(
    name: 'unique_conversation_user',
    columns: ['conversation_id', 'user_id']
)]
class ConversationReceipt
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['conv_receipt:collection:read', 'conv_receipt:item:read'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['conv_receipt:collection:read', 'conv_receipt:item:read', 'conv_receipt:write'])]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['conv_receipt:collection:read', 'conv_receipt:item:read', 'conv_receipt:write'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['conv_receipt:collection:read', 'conv_receipt:item:read', 'conv_receipt:write', 'conv_receipt:update', 'message:item:read'])]
    private ?Message $lastReadMessage = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['conv_receipt:collection:read', 'conv_receipt:item:read', 'conv_receipt:write', 'conv_receipt:update'])]
    private ?\DateTimeInterface $lastReadAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['conv_receipt:collection:read', 'conv_receipt:item:read', 'conv_receipt:write', 'conv_receipt:update'])]
    private ?\DateTimeInterface $lastDeliveredAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;
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

    public function getLastReadMessage(): ?Message
    {
        return $this->lastReadMessage;
    }

    public function setLastReadMessage(?Message $lastReadMessage): static
    {
        $this->lastReadMessage = $lastReadMessage;
        if ($lastReadMessage) {
            $this->lastReadAt = new \DateTime();
        }
        return $this;
    }

    public function getLastReadAt(): ?\DateTimeInterface
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(?\DateTimeInterface $lastReadAt): static
    {
        $this->lastReadAt = $lastReadAt;
        return $this;
    }

    public function getLastDeliveredAt(): ?\DateTimeInterface
    {
        return $this->lastDeliveredAt;
    }

    public function setLastDeliveredAt(?\DateTimeInterface $lastDeliveredAt): static
    {
        $this->lastDeliveredAt = $lastDeliveredAt;
        return $this;
    }
}