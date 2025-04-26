<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Conversation;
use App\Entity\Message;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class MessageInput
{
    #[ApiProperty(description: 'Conversation the message is in', example: '/api/conversations/c5806c83-3f04-4486-a112-645066194641')]
    #[Groups(['message:write'])]
    public Conversation $conversation;

    #[ApiProperty(description: 'Conversation the message is sent', example: 'Hello this is a message')]
    #[Assert\NotBlank(message: 'Message content cannot be blank')]
    #[Groups(['message:write'])]
    public string $content;
}
