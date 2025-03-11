<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\Redis\ChatRedisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

class MessageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageRepository $messageRepository,
        private readonly ChatRedisService $redisService,
        private readonly HubInterface $mercureHub,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Send a new message
     */
    public function sendMessage(Message $message, bool $persist = true): Message
    {
        // First store in Redis for immediate availability
        $this->redisService->cacheMessage($message);

        // Notify participants via Mercure
        $this->publishMessageUpdate($message);

        // Mark as unread for all participants except sender
        $this->markAsUnreadForParticipants($message);

        // Persist to database if requested
        if ($persist) {
            $this->entityManager->persist($message);
            $this->entityManager->flush();
        }

        return $message;
    }

    /**
     * Get recent messages for a conversation
     * Combines Redis cache and database as needed
     */
    public function getRecentMessages(Conversation $conversation, int $limit = 20): array
    {
        // Try from Redis cache first
        $messages = $this->redisService->getRecentMessages($conversation, $limit);

        // If insufficient results from cache, fetch from database
        if (count($messages) < $limit) {
            $existingIds = array_map(fn(Message $m) => $m->getId(), $messages);
            $dbMessages = $this->messageRepository->findRecentExcluding($conversation, $existingIds, $limit - count($messages));

            // Cache the database messages
            foreach ($dbMessages as $message) {
                $this->redisService->cacheMessage($message);
            }

            // Combine results
            $messages = array_merge($messages, $dbMessages);

            // Sort by sent time (newest first)
            usort($messages, fn(Message $a, Message $b) =>
                $b->getSentAt()->getTimestamp() - $a->getSentAt()->getTimestamp()
            );

            // Limit to requested number
            $messages = array_slice($messages, 0, $limit);
        }

        return $messages;
    }

    /**
     * Mark messages as read for a user
     */
    public function markAsRead(array $messageIds, User $user): void
    {
        // Update Redis cache
        $this->redisService->markAsRead($messageIds, $user);

        // Update database (could be queued for better performance)
        $this->messageRepository->markAsRead($messageIds, $user);
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCountForUser(User $user): int
    {
        return $this->redisService->getUnreadCount($user);
    }

    /**
     * Mark a message as unread for all participants except sender
     */
    private function markAsUnreadForParticipants(Message $message): void
    {
        $sender = $message->getSender();
        $conversation = $message->getConversation();

        foreach ($conversation->getParticipants() as $participant) {
            $user = $participant->getUser();

            // Skip the sender
            if ($user->getId() === $sender->getId()) {
                continue;
            }

            $this->redisService->addUnreadMessage($message, $user);
        }
    }

    /**
     * Publish a message update to Mercure
     */
    private function publishMessageUpdate(Message $message): void
    {
        $conversationId = $message->getConversation()->getId();

        // Create a topic for the conversation
        $topic = "chat/conversation/{$conversationId}";

        // Serialize the message
        $messageData = $this->serializer->serialize($message, 'json', [
            'groups' => ['message:read']
        ]);

        // Create and publish the update
        $update = new Update(
            $topic,
            $messageData,
            false
        );

        $this->mercureHub->publish($update);
    }
}