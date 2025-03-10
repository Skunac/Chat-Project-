<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\MessageReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['reaction:collection:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['reaction:item:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['reaction:write']],
            security: "is_granted('REACTION_CREATE', object)"
        ),
        new Delete(
            security: "is_granted('REACTION_DELETE', object)"
        )
    ],
    normalizationContext: ['groups' => ['reaction:read']],
    denormalizationContext: ['groups' => ['reaction:write']]
)]
#[ORM\Entity(repositoryClass: MessageReactionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'unique_message_user_reaction',
    columns: ['message_id', 'user_id', 'reaction']
)]
class MessageReaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['reaction:collection:read', 'reaction:item:read', 'message:item:read'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['reaction:collection:read', 'reaction:item:read', 'reaction:write'])]
    private ?Message $message = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['reaction:collection:read', 'reaction:item:read', 'reaction:write', 'message:item:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['reaction:collection:read', 'reaction:item:read', 'reaction:write', 'message:item:read'])]
    #[Assert\NotBlank(message: 'Reaction cannot be blank')]
    #[Assert\Length(max: 50, maxMessage: 'Reaction cannot be longer than {{ limit }} characters')]
    private string $reaction = '';

    #[ORM\Column(type: 'datetime')]
    #[Groups(['reaction:item:read', 'message:item:read'])]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->createdAt = new \DateTime();
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

    public function getReaction(): string
    {
        return $this->reaction;
    }

    public function setReaction(string $reaction): static
    {
        $this->reaction = $reaction;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}