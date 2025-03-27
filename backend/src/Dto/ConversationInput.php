<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ConversationInput
{
    #[ApiProperty(description: 'Name of the conversation', example: 'Team Chat')]
    #[Assert\Length(max: 255, maxMessage: 'Conversation name cannot be longer than {{ limit }} characters')]
    #[Groups(['conversation:write'])]
    public ?string $name = null;

    #[ApiProperty(description: 'URL for the conversation avatar', example: 'https://example.com/avatar.jpg')]
    #[Assert\Length(max: 255, maxMessage: 'Avatar URL cannot be longer than {{ limit }} characters')]
    #[Assert\Url(message: 'The avatar URL {{ value }} is not a valid URL')]
    #[Groups(['conversation:write'])]
    public ?string $avatarUrl = null;

    /**
     * List of participant user IDs to add to the conversation
     */
    #[ApiProperty(
        description: 'IDs of users to add as participants',
        example: ['550e8400-e29b-41d4-a716-446655440000']
    )]
    #[Assert\NotNull(message: 'Participant IDs are required')]
    #[Assert\Count(min: 1, minMessage: 'At least one participant is required')]
    #[Groups(['conversation:write'])]
    public array $participantIds = [];
}