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
            denormalizationContext: ['groups' => ['participant:write']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['participant:update']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['participant:update']]
        ),
        new Delete()
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
}