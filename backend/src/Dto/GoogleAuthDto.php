<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class GoogleAuthDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Google user ID is required')]
        public readonly string $sub,

        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
        public readonly string $email,

        public readonly ?string $name = null,

        public readonly ?string $picture = null,
    ) {
    }
}
