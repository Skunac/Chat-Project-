<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Dto\ConversationInput;
use App\Repository\ConversationRepository;
use App\State\ConversationProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['conversation:collection:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            normalizationContext: ['groups' => ['conversation:item:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['conversation:write']],
            security: "is_granted('ROLE_USER')",
            input: ConversationInput::class,
            processor: ConversationProcessor::class
        ),
        new Put(
            denormalizationContext: ['groups' => ['conversation:update']],
            security: "is_granted('ROLE_USER') and (object.getCreator() == user or is_granted('ROLE_ADMIN'))"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['conversation:update']],
            security: "is_granted('ROLE_USER') and (object.getCreator() == user or is_granted('ROLE_ADMIN'))"
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and (object.getCreator() == user or is_granted('ROLE_ADMIN'))"
        )
    ],
    normalizationContext: ['groups' => ['conversation:read']],
    denormalizationContext: ['groups' => ['conversation:write']]
)]
#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversation')]
#[ORM\HasLifecycleCallbacks]
class Conversation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['conversation:collection:read', 'conversation:item:read', 'message:read'])]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['conversation:collection:read', 'conversation:item:read', 'conversation:write', 'conversation:update'])]
    #[Assert\Length(max: 255, maxMessage: 'Conversation name cannot be longer than {{ limit }} characters')]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['conversation:collection:read', 'conversation:item:read', 'conversation:write', 'conversation:update'])]
    #[Assert\Length(max: 255, maxMessage: 'Avatar URL cannot be longer than {{ limit }} characters')]
    #[Assert\Url(message: 'The avatar URL {{ value }} is not a valid URL')]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['conversation:item:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['conversation:item:read'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['conversation:item:read','conversation:write'])]
    private ?User $creator = null;

    #[ORM\OneToMany(targetEntity: ConversationParticipant::class, mappedBy: 'conversation', orphanRemoval: true)]
    #[Groups(['conversation:item:read'])]
    private Collection $participants;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation')]
    private Collection $messages;

    #[ORM\OneToMany(targetEntity: ConversationReceipt::class, mappedBy: 'conversation', orphanRemoval: true)]
    private Collection $receipts;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->settings = [];
        $this->receipts = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return Collection<int, ConversationParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(ConversationParticipant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setConversation($this);
        }

        return $this;
    }

    public function removeParticipant(ConversationParticipant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getConversation() === $this) {
                $participant->setConversation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
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