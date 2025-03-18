<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['user:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['user:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['user:update']],
            security: "is_granted('ROLE_ADMIN') or object == user"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['user:update']],
            security: "is_granted('ROLE_ADMIN') or object == user"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:collection:read', 'user:item:read', 'user:write', 'conversation:item:read', 'message:read', 'participant:read'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:collection:read', 'user:item:read', 'user:write', 'user:update', 'conversation:item:read', 'message:read', 'participant:read'])]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:collection:read', 'user:item:read', 'user:write', 'user:update', 'conversation:item:read', 'message:read', 'participant:read'])]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:item:read', 'user:update'])]
    private bool $isActive = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:item:read'])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:item:read', 'user:write', 'user:update'])]
    private array $notificationSettings = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:item:read', 'conversation:item:read'])]
    private ?\DateTimeInterface $lastSeen = null;

    #[ORM\Column]
    #[Groups(['user:item:read'])]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:write', 'user:update'])]
    #[Assert\NotBlank(groups: ['user:write'])]
    private ?string $password = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['user:item:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['user:item:read'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:item:read', 'user:write', 'user:update'])]
    private ?string $googleId = null;

    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'creator')]
    private Collection $createdConversations;

    #[ORM\OneToMany(targetEntity: ConversationParticipant::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $conversationParticipations;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $sentMessages;

    #[ORM\OneToMany(targetEntity: ConversationReceipt::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $conversationReceipts;

    #[ORM\OneToMany(targetEntity: MessageReaction::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $messageReactions;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->isActive = true;
        $this->notificationSettings = [];
        $this->lastSeen = new \DateTime();
        $this->roles = ["ROLE_USER"];
        $this->createdConversations = new ArrayCollection();
        $this->conversationParticipations = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->messageReceipts = new ArrayCollection();
        $this->messageReactions = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getNotificationSettings(): array
    {
        return $this->notificationSettings;
    }

    public function setNotificationSettings(array $notificationSettings): static
    {
        $this->notificationSettings = $notificationSettings;
        return $this;
    }

    public function getLastSeen(): ?\DateTimeInterface
    {
        return $this->lastSeen;
    }

    public function setLastSeen(?\DateTimeInterface $lastSeen): static
    {
        $this->lastSeen = $lastSeen;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getCreatedConversations(): Collection
    {
        return $this->createdConversations;
    }

    public function addCreatedConversation(Conversation $conversation): static
    {
        if (!$this->createdConversations->contains($conversation)) {
            $this->createdConversations->add($conversation);
            $conversation->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedConversation(Conversation $conversation): static
    {
        if ($this->createdConversations->removeElement($conversation)) {
            // set the owning side to null (unless already changed)
            if ($conversation->getCreator() === $this) {
                $conversation->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ConversationParticipant>
     */
    public function getConversationParticipations(): Collection
    {
        return $this->conversationParticipations;
    }

    public function addConversationParticipation(ConversationParticipant $participation): static
    {
        if (!$this->conversationParticipations->contains($participation)) {
            $this->conversationParticipations->add($participation);
            $participation->setUser($this);
        }

        return $this;
    }

    public function removeConversationParticipation(ConversationParticipant $participation): static
    {
        if ($this->conversationParticipations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getUser() === $this) {
                $participation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getSentMessages(): Collection
    {
        return $this->sentMessages;
    }

    public function addSentMessage(Message $message): static
    {
        if (!$this->sentMessages->contains($message)) {
            $this->sentMessages->add($message);
            $message->setSender($this);
        }

        return $this;
    }

    public function removeSentMessage(Message $message): static
    {
        if ($this->sentMessages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ConversationReceipt>
     */
    public function getConversationReceipts(): Collection
    {
        return $this->conversationReceipts;
    }

    public function addConversationReceipt(ConversationReceipt $receipt): static
    {
        if (!$this->conversationReceipts->contains($receipt)) {
            $this->conversationReceipts->add($receipt);
            $receipt->setUser($this);
        }

        return $this;
    }

    public function removeConversationReceipt(ConversationReceipt $receipt): static
    {
        if ($this->conversationReceipts->removeElement($receipt)) {
            // set the owning side to null (unless already changed)
            if ($receipt->getUser() === $this) {
                $receipt->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MessageReaction>
     */
    public function getMessageReactions(): Collection
    {
        return $this->messageReactions;
    }

    public function addMessageReaction(MessageReaction $reaction): static
    {
        if (!$this->messageReactions->contains($reaction)) {
            $this->messageReactions->add($reaction);
            $reaction->setUser($this);
        }

        return $this;
    }

    public function removeMessageReaction(MessageReaction $reaction): static
    {
        if ($this->messageReactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getUser() === $this) {
                $reaction->setUser(null);
            }
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }
}