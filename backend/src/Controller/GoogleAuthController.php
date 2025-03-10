<?php

namespace App\Controller;

use App\Dto\GoogleCallbackParamsDto;
use App\Service\ApiResponseService;
use App\Service\GoogleAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth/google', name: 'api_auth_google_', format: 'json')]
class GoogleAuthController extends AbstractController
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
        private readonly ApiResponseService $apiResponse,
    ) {
    }

    /**
     * Get Google OAuth authorization URL
     */
    #[Route('/connect', name: 'connect', methods: ['GET'])]
    public function connect(): JsonResponse
    {
        try {
            $redirectUrl = $this->googleAuthService->getAuthorizationUrl();

            return $this->apiResponse->success(
                ['redirect_url' => $redirectUrl],
                Response::HTTP_OK,
                'Google authorization URL generated'
            )->setEncodingOptions(JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Failed to generate authorization URL', $e);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    #[Route('/callback', name: 'callback', methods: ['GET'])]
    public function callback(#[MapQueryString] GoogleCallbackParamsDto $params): JsonResponse
    {
        try {
            $googleAuthDto = $this->googleAuthService->handleCallback($params->code);

            $result = $this->googleAuthService->processGoogleLogin($googleAuthDto);

            return $this->apiResponse->success(
                $result,
                Response::HTTP_OK,
                'Google authentication successful'
            );

        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Google authentication failed', $e);
        }
    }
}