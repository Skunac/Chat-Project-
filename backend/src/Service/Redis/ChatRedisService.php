<?php
// src/Service/Redis/ChatRedisService.php

namespace App\Service\Redis;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ChatRedisService
{
    private Client $redis;
    private string $prefix = 'chat:';
    private int $ttl;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer
    ) {
        $this->redis = new Client($_ENV['REDIS_URL'] ?? 'redis://localhost:6379');
        $this->ttl = (int)($_ENV['REDIS_CHAT_TTL'] ?? 86400); // 1 day default
    }

    /**
     * Store a message in Redis cache
     */
    public function cacheMessage(Message $message): bool
    {
        try {
            $messageId = $message->getId();
            $conversationId = $message->getConversation()->getId();

            // Serialize message with minimal data
            $messageData = $this->serializer->serialize($message, 'json', [
                'groups' => ['message:read']
            ]);

            // Store message by ID
            $this->redis->setex(
                $this->prefix . "message:{$messageId}",
                $this->ttl,
                $messageData
            );

            // Add to recent messages for conversation
            $this->redis->zadd(
                $this->prefix . "conversation:{$conversationId}:messages",
                [$messageId => $message->getSentAt()->getTimestamp()]
            );

            // Trim to last 50 messages
            $this->redis->zremrangebyrank(
                $this->prefix . "conversation:{$conversationId}:messages",
                0,
                -51
            );

            // Set expiration on conversation messages list
            $this->redis->expire(
                $this->prefix . "conversation:{$conversationId}:messages",
                $this->ttl
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache message in Redis', [
                'messageId' => $message->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get a cached message by ID
     */
    public function getMessage(string $messageId): ?Message
    {
        try {
            $data = $this->redis->get($this->prefix . "message:{$messageId}");
            if (!$data) {
                return null;
            }

            return $this->serializer->deserialize(
                $data,
                Message::class,
                'json',
                ['groups' => ['message:read']]
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to get message from Redis', [
                'messageId' => $messageId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get recent messages for a conversation
     */
    public function getRecentMessages(Conversation $conversation, int $limit = 20): array
    {
        try {
            $conversationId = $conversation->getId();

            // Get the most recent message IDs
            $messageIds = $this->redis->zrevrange(
                $this->prefix . "conversation:{$conversationId}:messages",
                0,
                $limit - 1
            );

            if (empty($messageIds)) {
                return [];
            }

            // Get each message
            $messages = [];
            foreach ($messageIds as $messageId) {
                $message = $this->getMessage($messageId);
                if ($message) {
                    $messages[] = $message;
                }
            }

            return $messages;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get recent messages from Redis', [
                'conversationId' => $conversation->getId(),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Track unread messages for a user
     */
    public function addUnreadMessage(Message $message, User $user): bool
    {
        try {
            // Skip if sender is the user
            if ($message->getSender()->getId() === $user->getId()) {
                return true;
            }

            $userId = $user->getId();
            $messageId = $message->getId();

            // Add to user's unread messages
            $this->redis->zadd(
                $this->prefix . "user:{$userId}:unread",
                [$messageId => $message->getSentAt()->getTimestamp()]
            );

            // Set expiration
            $this->redis->expire(
                $this->prefix . "user:{$userId}:unread",
                $this->ttl
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to track unread message', [
                'userId' => $user->getId(),
                'messageId' => $message->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get count of unread messages for a user
     */
    public function getUnreadCount(User $user): int
    {
        try {
            $userId = $user->getId();
            return (int)$this->redis->zcard($this->prefix . "user:{$userId}:unread");
        } catch (\Exception $e) {
            $this->logger->error('Failed to get unread count', [
                'userId' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Mark messages as read for a user
     */
    public function markAsRead(array $messageIds, User $user): void
    {
        try {
            if (empty($messageIds)) {
                return;
            }

            $userId = $user->getId();
            $key = $this->prefix . "user:{$userId}:unread";

            // Remove messages from unread list
            $this->redis->zrem($key, $messageIds);

        } catch (\Exception $e) {
            $this->logger->error('Failed to mark messages as read', [
                'userId' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}