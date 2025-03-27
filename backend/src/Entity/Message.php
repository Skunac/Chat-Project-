<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\OpenApi;
use App\Dto\MessageInput;
use App\Repository\MessageRepository;
use App\State\MessageProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\ConversationMessageProvider;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['message:collection:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new GetCollection(
            uriTemplate: '/conversations/{id}/messages',
            uriVariables: ['id' => new Link(fromProperty: 'id', fromClass: Conversation::class)],
            normalizationContext: ['groups' => ['message:collection:read']],
            security: "is_granted('ROLE_USER')",
            provider: ConversationMessageProvider::class
        ),
        new Get(
            normalizationContext: ['groups' => ['message:item:read']],
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            denormalizationContext: ['groups' => ['message:write']],
            security: "is_granted('ROLE_USER')",
            input: MessageInput::class,
            processor: MessageProcessor::class
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

    public function __construct()
    {
        $this->id = Uuid::v4()->__toString();
        $this->sentAt = new \DateTime();
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
}