<?php

namespace App\Service;

use App\Dto\AuthCredentialsDto;
use App\Dto\UserRegistrationDto;
use App\Entity\User;
use App\Mapper\UserMapper;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
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
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly MessageRepository $messageRepository
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
            throw new \Exception('User already exists');
        }

        // Use the new mapper method
        $user = $this->userMapper->mapToUser($dto);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, ((new \DateTime())->modify('+30 days'))->getTimestamp());

        return [
            'user' => $this->getUserData($user),
            'token' => $token,
            'refresh_token' => $refreshToken->getRefreshToken(),
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
        $conversations = [];

        foreach ($user->getConversationParticipations() as $participation) {
            $conversation = $participation->getConversation();

            if ($conversation) {
                $lastMessage = $this->messageRepository->getLastMessageOfConversation($conversation);

                $conversationData = [
                    'id' => $conversation->getId(),
                    'name' => $conversation->getName(),
                    'avatarUrl' => $conversation->getAvatarUrl(),
                    'createdAt' => $conversation->getCreatedAt()->format('c'),
                    'updatedAt' => $conversation->getUpdatedAt()->format('c'),
                    'role' => $participation->getRole(),
                    'lastMessage' => null
                ];

                if ($lastMessage) {
                    $conversationData['lastMessage'] = [
                        'content' => $lastMessage->getContent(),
                        'senderName' => $lastMessage->getSender()->getDisplayName(),
                        'sentAt' => $lastMessage->getSentAt()->format('c'),
                    ];
                }

                $conversations[] = $conversationData;
            }
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'avatarUrl' => $user->getAvatarUrl(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'lastSeen' => $user->getLastSeen()?->format('c'),
            'conversations' => $conversations
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