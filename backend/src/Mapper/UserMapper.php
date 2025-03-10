<?php

namespace App\Mapper;

use App\Dto\GoogleUserDto;
use App\Dto\UserRegistrationDto;
use App\Entity\User;
use AutoMapperPlus\AutoMapperInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserMapper
{
    public function __construct(
        private readonly AutoMapperInterface $mapper,
        private readonly AutoMapperConfigInterface $config,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->configureMapper();
    }

    private function configureMapper(): void
    {
        // Registration mapping
        $this->config->registerMapping(UserRegistrationDto::class, User::class)
            ->forMember('password', function() {
                return null;
            });

        // Google user mapping
        $this->config->registerMapping(GoogleUserDto::class, User::class)
            ->forMember('avatarUrl', function(GoogleUserDto $source) {
                return $source->avatarUrl;
            })
            ->forMember('googleId', function(GoogleUserDto $source) {
                return $source->googleId;
            })
            ->forMember('isVerified', function(GoogleUserDto $source) {
                return $source->isVerified;
            });

        $this->mapper->getConfiguration()->registerMapping(UserRegistrationDto::class, User::class);
        $this->mapper->getConfiguration()->registerMapping(GoogleUserDto::class, User::class);
    }

    public function mapToUser(UserRegistrationDto $dto): User
    {
        // Create a new User instance
        $user = new User();

        // Map properties from DTO to the user
        $this->mapper->mapToObject($dto, $user);

        // Hash the password
        if ($dto->password) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        }

        return $user;
    }

    public function mapGoogleToUser(GoogleUserDto $dto): User
    {
        // Create a new User instance
        $user = new User();

        // Map properties from Google DTO to user
        $this->mapper->mapToObject($dto, $user);

        // Set roles
        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    public function updateUserFromGoogle(User $user, GoogleUserDto $dto): User
    {
        // Update only specific fields from Google
        if ($dto->googleId) {
            $user->setGoogleId($dto->googleId);
        }

        if ($dto->avatarUrl && !$user->getAvatarUrl()) {
            $user->setAvatarUrl($dto->avatarUrl);
        }

        if ($dto->displayName && !$user->getDisplayName()) {
            $user->setDisplayName($dto->displayName);
        }

        $user->setIsVerified(true);

        return $user;
    }
}