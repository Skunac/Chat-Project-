<?php

namespace App\Service;

use App\Dto\GoogleAuthDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly HttpClientInterface $httpClient,
        private readonly AuthService $authService,
        private readonly ApiResponseService $apiResponse,
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
     */
    public function handleCallback(string $code): GoogleAuthDto|JsonResponse
    {
        try {
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
                return $this->apiResponse->error('Failed to obtain access token');
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

        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Google authentication failed', $e);
        }
    }

    /**
     * Process Google login or registration
     *
     * @param GoogleAuthDto $googleAuthDto
     * @return array
     */
    public function processGoogleLogin(GoogleAuthDto $googleAuthDto): array
    {
        $user = $this->userRepository->findOneBy(['googleId' => $googleAuthDto->sub]);

        if (!$user) {
            $user = $this->userRepository->findOneByEmail($googleAuthDto->email);

            if ($user) {
                $user->setGoogleId($googleAuthDto->sub);
            }
        }

        if (!$user) {
            $user = new User();
            $user->setEmail($googleAuthDto->email);
            $user->setDisplayName($googleAuthDto->name ?? $googleAuthDto->email);
            $user->setGoogleId($googleAuthDto->sub);
            $user->setIsVerified(true);
            $user->setRoles(['ROLE_USER']);

            if ($googleAuthDto->picture) {
                $user->setAvatarUrl($googleAuthDto->picture);
            }
        }

        $user->setLastSeen(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return [
            'user' => $this->authService->getUserData($user),
            'token' => $token
        ];
    }
}
