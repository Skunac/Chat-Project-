<?php

namespace App\Mapper;

use App\Dto\UserRegistrationDto;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserMapper
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Map DTO to user entity
     */
    public function mapToNewUser(UserRegistrationDto $dto): User
    {
        $user = new User();

        // Map basic fields
        $user->setEmail($dto->email);

        // Hash password
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));

        // Map optional fields
        if ($dto->displayName) {
            $user->setDisplayName($dto->displayName);
        }

        if ($dto->avatarUrl) {
            $user->setAvatarUrl($dto->avatarUrl);
        }

        return $user;
    }

    /**
     * Update existing user from DTO
     */
    public function updateFromDto(User $user, object $dto): User
    {
        // Use reflection to update only properties that exist in both objects
        $dtoReflection = new \ReflectionObject($dto);

        foreach ($dtoReflection->getProperties() as $property) {
            $propertyName = $property->getName();

            // Skip password as it needs special handling
            if ($propertyName === 'password') {
                continue;
            }

            // Check if the user has a setter for this property
            $setterMethod = 'set' . ucfirst($propertyName);

            if (method_exists($user, $setterMethod) && $property->isInitialized($dto)) {
                $value = $property->getValue($dto);

                // Only update if value exists
                if ($value !== null) {
                    $user->$setterMethod($value);
                }
            }
        }

        // Handle password separately if it exists and is not empty
        if (property_exists($dto, 'password') && !empty($dto->password)) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        }

        return $user;
    }
}
