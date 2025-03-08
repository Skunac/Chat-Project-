<?php

namespace App\Controller;

use App\Service\ApiResponseService;
use App\Service\MercurePublisher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mercure-test', name: 'api_mercure_test_')]
class MercureTestController extends AbstractController
{
    private MercurePublisher $mercurePublisher;
    private ApiResponseService $apiResponse;

    public function __construct(
        MercurePublisher $mercurePublisher,
        ApiResponseService $apiResponse
    ) {
        $this->mercurePublisher = $mercurePublisher;
        $this->apiResponse = $apiResponse;
    }

    /**
     * Publish a test message to Mercure
     */
    #[Route('/publish', name: 'publish', methods: ['POST', 'OPTIONS'])]
    public function publish(Request $request): JsonResponse
    {
        // Handle CORS preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return $this->apiResponse->success();
        }

        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->apiResponse->error('Invalid JSON data');
            }

            $message = $data['message'] ?? null;
            if (!$message) {
                return $this->apiResponse->error('Message is required');
            }

            // Create a unique topic
            $topic = $data['topic'] ?? 'chat/test';

            // Prepare the data to publish
            $publishData = [
                'message' => $message,
                'timestamp' => (new \DateTime())->format('c')
            ];

            // Add sender if provided
            if (isset($data['sender'])) {
                $publishData['sender'] = $data['sender'];
            }

            // Publish to Mercure hub with the correct parameters
            $updateId = $this->mercurePublisher->publish(
                $topic,
                $publishData,
                [], // Empty targets array
                false // Not private
            );

            // Return success response
            return $this->apiResponse->success([
                'topic' => $topic,
                'updateId' => $updateId
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Failed to publish message: ' . $e->getMessage());
        }
    }

    /**
     * Get a test page with Mercure subscription info
     */
    #[Route('/subscribe', name: 'subscribe', methods: ['GET'])]
    public function subscribe(): JsonResponse
    {
        $hubUrl = $this->getParameter('mercure.default_hub');

        return $this->apiResponse->success([
            'mercureHubUrl' => $hubUrl,
            'topic' => 'chat/test'
        ]);
    }
}