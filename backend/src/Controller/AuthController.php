<?php

namespace App\Controller;

use App\Dto\UserRegistrationDto;
use App\Dto\AuthCredentialsDto;
use App\Entity\User;
use App\Service\ApiResponseService;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_', format: 'json')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ApiResponseService $apiResponse,
    ) {
    }

    /**
     * Register a new user account without oauth
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(#[MapRequestPayload] UserRegistrationDto $dto): JsonResponse
    {
        try {
            $result = $this->authService->registerUser($dto);

            return $this->apiResponse->success(
                $result,
                Response::HTTP_CREATED,
                'User registered successfully'
            );
        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Registration failed', $e);
        }
    }

    /**
     * Login user
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        return $this->apiResponse->success();
    }

    /**
     * Get current user information
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->apiResponse->success(
            $this->authService->getUserData($user),
            Response::HTTP_OK,
            'User profile retrieved'
        );
    }
}