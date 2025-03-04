<?php

namespace App\Controller;

use App\Service\ApiResponseService;
use App\Service\GoogleAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth/google', name: 'api_auth_google_')]
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
    public function callback(Request $request): JsonResponse
    {
        $code = $request->query->get('code');
        if (!$code) {
            return $this->apiResponse->error('Authorization code missing', Response::HTTP_BAD_REQUEST);
        }

        try {
            $googleAuthDto = $this->googleAuthService->handleCallback($code);

            if ($googleAuthDto instanceof JsonResponse) {
                return $googleAuthDto;
            }

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