<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ConversationParticipantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['participant:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['participant:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['participant:write']],
            security: "is_granted('CONVERSATION_EDIT', object.getConversation())"
        ),
        new Put(
            denormalizationContext: ['groups' => ['participant:update']],
            security: "is_granted('PARTICIPANT_EDIT', object)"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['participant:update']],
            security: "is_granted('PARTICIPANT_EDIT', object)"
        ),
        new Delete(
            security: "is_granted('PARTICIPANT_DELETE', object)"
        )
    ],
    normalizationContext: ['groups' => ['participant:read']],
    denormalizationContext: ['groups' => ['participant:write']]
)]
#[ORM\Entity(repositoryClass: ConversationParticipantRepository::class)]
#[ORM\Table(name: 'conversation_participant')]
class ConversationParticipant
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['participant:collection:read', 'participant:item:read', 'conversation:item:read'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['participant:collection:read', 'participant:item:read', 'participant:write', 'conversation:item:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['participant:item:read', 'participant:write'])]
    private ?Conversation $conversation = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Groups(['participant:collection:read', 'participant:item:read', 'participant:write', 'participant:update', 'conversation:item:read'])]
    #[Assert\Choice(choices: ['ADMIN', 'MODERATOR', 'MEMBER'], message: 'Invalid role value')]
    private string $role = 'MEMBER';

    #[ORM\Column(type: 'datetime', nullable: false)]
    #[Groups(['participant:item:read', 'conversation:item:read'])]
    private \DateTimeInterface $joinedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['participant:item:read', 'conversation:item:read'])]
    private ?\DateTimeInterface $leftAt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['participant:item:read', 'participant:write', 'participant:update', 'conversation:item:read'])]
    private bool $isAdmin = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['participant:item:read', 'participant:write', 'participant:update'])]
    private bool $isMuted = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['participant:item:read', 'participant:update'])]
    private ?\DateTimeInterface $mutedUntil = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['participant:item:read', 'participant:write', 'participant:update'])]
    private bool $notificationsEnabled = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['participant:item:read', 'participant:update'])]
    private bool $isArchived = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['participant:item:read', 'participant:update'])]
    private bool $isPinned = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['participant:item:read', 'participant:update'])]
    private ?int $pinPosition = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['participant:item:read'])]
    private ?\DateTimeInterface $lastReadAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->joinedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt(): \DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLeftAt(): ?\DateTimeInterface
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeInterface $leftAt): static
    {
        $this->leftAt = $leftAt;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    public function isMuted(): bool
    {
        return $this->isMuted;
    }

    public function setIsMuted(bool $isMuted): static
    {
        $this->isMuted = $isMuted;
        return $this;
    }

    public function getMutedUntil(): ?\DateTimeInterface
    {
        return $this->mutedUntil;
    }

    public function setMutedUntil(?\DateTimeInterface $mutedUntil): static
    {
        $this->mutedUntil = $mutedUntil;
        return $this;
    }

    public function hasNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $notificationsEnabled): static
    {
        $this->notificationsEnabled = $notificationsEnabled;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): static
    {
        $this->isPinned = $isPinned;
        return $this;
    }

    public function getPinPosition(): ?int
    {
        return $this->pinPosition;
    }

    public function setPinPosition(?int $pinPosition): static
    {
        $this->pinPosition = $pinPosition;
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
}