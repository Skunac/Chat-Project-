<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['message:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['message:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['message:write']],
            security: "is_granted('MESSAGE_CREATE', object)"
        ),
        new Put(
            denormalizationContext: ['groups' => ['message:update']],
            security: "is_granted('MESSAGE_EDIT', object)"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['message:update']],
            security: "is_granted('MESSAGE_EDIT', object)"
        ),
        new Delete(
            security: "is_granted('MESSAGE_DELETE', object)"
        )
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']]
)]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['message:collection:read', 'message:item:read'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:collection:read', 'message:item:read', 'message:write'])]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:collection:read', 'message:item:read', 'message:write'])]
    private ?User $sender = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['message:collection:read', 'message:item:read', 'message:write', 'message:update'])]
    #[Assert\NotBlank(message: 'Message content cannot be blank')]
    private string $content = '';

    #[ORM\Column(type: 'datetime')]
    #[Groups(['message:collection:read', 'message:item:read'])]
    private \DateTimeInterface $sentAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['message:item:read'])]
    private ?\DateTimeInterface $editedAt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['message:collection:read', 'message:item:read', 'message:update'])]
    private bool $isDeleted = false;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[Groups(['message:collection:read', 'message:item:read', 'message:write'])]
    private ?self $parentMessage = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['message:item:read', 'message:write', 'message:update'])]
    private array $metadata = [];

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageReceipt::class, orphanRemoval: true)]
    private Collection $receipts;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageReaction::class, orphanRemoval: true)]
    #[Groups(['message:item:read'])]
    private Collection $reactions;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: Attachment::class, orphanRemoval: true)]
    #[Groups(['message:item:read'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->sentAt = new \DateTime();
        $this->receipts = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->metadata = [];
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

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getSentAt(): \DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getEditedAt(): ?\DateTimeInterface
    {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTimeInterface $editedAt): static
    {
        $this->editedAt = $editedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function getParentMessage(): ?self
    {
        return $this->parentMessage;
    }

    public function setParentMessage(?self $parentMessage): static
    {
        $this->parentMessage = $parentMessage;
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

    /**
     * @return Collection<int, MessageReceipt>
     */
    public function getReceipts(): Collection
    {
        return $this->receipts;
    }

    public function addReceipt(MessageReceipt $receipt): static
    {
        if (!$this->receipts->contains($receipt)) {
            $this->receipts->add($receipt);
            $receipt->setMessage($this);
        }

        return $this;
    }

    public function removeReceipt(MessageReceipt $receipt): static
    {
        if ($this->receipts->removeElement($receipt)) {
            // set the owning side to null (unless already changed)
            if ($receipt->getMessage() === $this) {
                $receipt->setMessage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MessageReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(MessageReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setMessage($this);
        }

        return $this;
    }

    public function removeReaction(MessageReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getMessage() === $this) {
                $reaction->setMessage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Attachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setMessage($this);
        }

        return $this;
    }

    public function removeAttachment(Attachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getMessage() === $this) {
                $attachment->setMessage(null);
            }
        }

        return $this;
    }
}