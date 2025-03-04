<?php

namespace App\Service;

use App\Dto\AuthCredentialsDto;
use App\Dto\UserRegistrationDto;
use App\Entity\User;
use App\Mapper\UserMapper;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepository $userRepository,
        private readonly UserMapper $userMapper,
        private readonly ApiResponseService $apiResponse,
    ) {
    }

    /**
     * Register a new user
     *
     * @param UserRegistrationDto $dto
     * @return array|JsonResponse
     */
    public function registerUser(UserRegistrationDto $dto): array|JsonResponse
    {
        if ($this->userRepository->findOneByEmail($dto->email)) {
            return $this->apiResponse->conflict('User already exists');
        }

        $user = $this->userMapper->mapToNewUser($dto);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return [
            'user' => $this->getUserData($user),
            'token' => $token
        ];
    }

    /**
     * Authenticate a user with credentials
     *
     * @param AuthCredentialsDto $credentials
     * @return array|JsonResponse
     */
    public function authenticateUser(AuthCredentialsDto $credentials): array|JsonResponse
    {
        $user = $this->userRepository->findOneByEmail($credentials->email);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $credentials->password)) {
            return $this->apiResponse->unauthorized('Invalid credentials');
        }

        $user->setLastSeen(new \DateTime());
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return [
            'user' => $this->getUserData($user),
            'token' => $token
        ];
    }

    /**
     * Get normalized user data for API responses
     *
     * @param User $user
     * @return array
     */
    public function getUserData(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'avatarUrl' => $user->getAvatarUrl(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'lastSeen' => $user->getLastSeen()?->format('c'),
        ];
    }

    /**
     * Refresh JWT token for a user
     *
     * @param User $user
     * @return string
     */
    public function refreshToken(User $user): string
    {
        // Update last seen timestamp
        $user->setLastSeen(new \DateTime());
        $this->entityManager->flush();

        // Generate new token
        return $this->jwtManager->create($user);
    }
}