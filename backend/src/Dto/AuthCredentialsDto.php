<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AuthCredentialsDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
        public readonly string $email,

        #[Assert\NotBlank(message: 'Password is required')]
        public readonly string $password,
    ) {
    }
}