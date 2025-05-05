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
}