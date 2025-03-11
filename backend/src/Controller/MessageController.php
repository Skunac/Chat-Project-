<?php
// src/Controller/MessageController.php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Service\ApiResponseService;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/messages', name: 'api_message_', format: 'json')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $apiResponse,
        private readonly MessageService $messageService,
        private readonly SerializerInterface $serializer,
        private readonly ConversationRepository $conversationRepository
    ) {
    }

    /**
     * Send a message
     */
    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMessage(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate input
            if (!isset($data['conversationId']) || !isset($data['content'])) {
                return $this->apiResponse->error('Missing required fields', Response::HTTP_BAD_REQUEST);
            }

            // Get conversation
            $conversation = $this->conversationRepository->find($data['conversationId']);
            if (!$conversation) {
                return $this->apiResponse->error('Conversation not found', Response::HTTP_NOT_FOUND);
            }

            // Check if user is participant
            if (!$this->isUserInConversation($user, $conversation)) {
                return $this->apiResponse->error('You are not a participant in this conversation', Response::HTTP_FORBIDDEN);
            }

            // Create message
            $message = new Message();
            $message->setConversation($conversation);
            $message->setSender($user);
            $message->setContent($data['content']);
            $message->setSentAt(new \DateTime());
            $message->setMetadata([]);

            // Parent message if reply
            if (isset($data['parentMessageId'])) {
                $parentMessage = $this->messageRepository->find($data['parentMessageId']);
                if ($parentMessage) {
                    $message->setParentMessage($parentMessage);
                }
            }

            // Send message using service
            $this->messageService->sendMessage($message);

            return $this->apiResponse->success(
                ['message' => $message],
                Response::HTTP_CREATED,
                'Message sent successfully'
            );

        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Failed to send message: ' . $e->getMessage());
        }
    }

    /**
     * Get messages for a conversation
     */
    #[Route('/conversation/{id}', name: 'get_conversation_messages', methods: ['GET'])]
    public function getConversationMessages(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            // Get conversation
            $conversation = $this->conversationRepository->find($id);
            if (!$conversation) {
                return $this->apiResponse->error('Conversation not found', Response::HTTP_NOT_FOUND);
            }

            // Check if user is participant
            if (!$this->isUserInConversation($user, $conversation)) {
                return $this->apiResponse->error('You are not a participant in this conversation', Response::HTTP_FORBIDDEN);
            }

            // Get limit parameter
            $limit = (int)$request->query->get('limit', 20);

            // Get messages using service
            $messages = $this->messageService->getRecentMessages($conversation, $limit);

            // Mark messages as read
            $messageIds = array_map(fn(Message $m) => $m->getId(), $messages);
            $this->messageService->markAsRead($messageIds, $user);

            return $this->apiResponse->success(
                ['messages' => $messages],
                Response::HTTP_OK,
                'Messages retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->apiResponse->serverError('Failed to get messages: ' . $e->getMessage());
        }
    }

    /**
     * Check if user is a participant in the conversation
     */
    private function isUserInConversation(User $user, Conversation $conversation): bool
    {
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getUser()->getId() === $user->getId() && $participant->getLeftAt() === null) {
                return true;
            }
        }
        return false;
    }
}