<?php

namespace App\Dto;

class GoogleUserDto
{
    public function __construct(
        public string $email,
        public string $googleId,
        public ?string $displayName = null,
        public ?string $avatarUrl = null,
        public bool $isVerified = true
    ) {
    }
}