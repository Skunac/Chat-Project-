<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
        #[Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters')]
        public readonly string $email,

        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(
            min: 8,
            max: 4096,
            minMessage: 'Your password must be at least {{ limit }} characters long',
            maxMessage: 'Your password cannot be longer than {{ limit }} characters'
        )]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter, and one number'
        )]
        public readonly string $password,

        #[Assert\Length(max: 255, maxMessage: 'Display name cannot be longer than {{ limit }} characters')]
        public readonly ?string $displayName = null,

        #[Assert\Url(message: 'The avatar URL {{ value }} is not a valid URL')]
        #[Assert\Length(max: 2048, maxMessage: 'Avatar URL cannot be longer than {{ limit }} characters')]
        public readonly ?string $avatarUrl = null,
    ) {
    }
}