<?php

namespace App\Service;

use App\Dto\GoogleAuthDto;
use App\Dto\GoogleUserDto;
use App\Entity\User;
use App\Mapper\UserMapper;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly HttpClientInterface $httpClient,
        private readonly AuthService $authService,
        private readonly ApiResponseService $apiResponse,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly UserMapper $userMapper
    ) {
    }

    /**
     * Get Google authorization URL
     *
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $redirectUri = $_ENV['GOOGLE_CALLBACK_URL'];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'email profile',
                'access_type' => 'online',
            ]);
    }

    /**
     * Exchange authorization code for tokens and get user info
     *
     * @param string $code
     * @return GoogleAuthDto|JsonResponse
     * @throws \Exception
     */
    public function handleCallback(string $code): GoogleAuthDto|JsonResponse
    {
        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                'code' => $code,
                'redirect_uri' => $_ENV['GOOGLE_CALLBACK_URL'],
                'grant_type' => 'authorization_code',
            ],
        ]);

        $data = $response->toArray();
        if (!isset($data['access_token'])) {
            throw new \Exception('Failed to obtain access token');
        }

        $userInfoResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['access_token'],
            ],
        ]);

        $userInfo = $userInfoResponse->toArray();

        return new GoogleAuthDto(
            $userInfo['sub'],
            $userInfo['email'],
            $userInfo['name'] ?? null,
            $userInfo['picture'] ?? null
        );
    }

    /**
     * Process Google login or registration
     *
     * @param GoogleAuthDto $googleAuthDto
     * @return array
     */
    public function processGoogleLogin(GoogleAuthDto $googleAuthDto): array
    {
        // Create a GoogleUserDto from the GoogleAuthDto
        $googleUserDto = new GoogleUserDto(
            $googleAuthDto->email,
            $googleAuthDto->sub,
            $googleAuthDto->name,
            $googleAuthDto->picture
        );

        // Try to find user by Google ID
        $user = $this->userRepository->findOneBy(['googleId' => $googleAuthDto->sub]);

        if (!$user) {
            // Try to find user by email
            $user = $this->userRepository->findOneByEmail($googleAuthDto->email);

            if ($user) {
                // Update existing user with Google info
                $this->userMapper->updateUserFromGoogle($user, $googleUserDto);
            }
        }

        if (!$user) {
            // Create new user from Google info
            $user = $this->userMapper->mapGoogleToUser($googleUserDto);
        }

        // Always update last seen timestamp
        $user->setLastSeen(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, ((new \DateTime())->modify('+30 days'))->getTimestamp());

        return [
            'user' => $this->authService->getUserData($user),
            'token' => $token,
            'refresh_token' => $refreshToken->getRefreshToken(),
        ];
    }
}