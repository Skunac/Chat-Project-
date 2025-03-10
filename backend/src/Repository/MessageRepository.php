<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Find messages in a conversation with pagination
     */
    public function findByConversation(Conversation $conversation, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unread messages for a user in a conversation
     */
    public function findUnreadMessages(User $user, Conversation $conversation): array
    {
        $participant = $this->getEntityManager()
            ->getRepository(ConversationParticipant::class)
            ->findOneByUserAndConversation($user, $conversation);

        if (!$participant || !$participant->getLastReadAt()) {
            // If no last read time, all messages are unread
            return $this->findByConversation($conversation);
        }

        $lastReadAt = $participant->getLastReadAt();

        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.sentAt > :lastReadAt')
            ->andWhere('m.sender != :user')
            ->setParameter('conversation', $conversation)
            ->setParameter('lastReadAt', $lastReadAt)
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread messages for a user in a conversation
     */
    public function countUnreadMessages(User $user, Conversation $conversation): int
    {
        $participant = $this->getEntityManager()
            ->getRepository(ConversationParticipant::class)
            ->findOneByUserAndConversation($user, $conversation);

        if (!$participant || !$participant->getLastReadAt()) {
            // Count all messages not sent by the user
            return $this->createQueryBuilder('m')
                ->select('COUNT(m)')
                ->where('m.conversation = :conversation')
                ->andWhere('m.sender != :user')
                ->setParameter('conversation', $conversation)
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();
        }

        $lastReadAt = $participant->getLastReadAt();

        return $this->createQueryBuilder('m')
            ->select('COUNT(m)')
            ->where('m.conversation = :conversation')
            ->andWhere('m.sentAt > :lastReadAt')
            ->andWhere('m.sender != :user')
            ->setParameter('conversation', $conversation)
            ->setParameter('lastReadAt', $lastReadAt)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find the latest message for each conversation a user is part of
     */
    public function findLatestByUserConversations(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // This needs to be adjusted based on your actual database schema
        $sql = '
            SELECT m.*
            FROM message m
            JOIN (
                SELECT MAX(m2.sent_at) as max_sent_at, m2.conversation_id
                FROM message m2
                JOIN conversation_participant cp ON cp.conversation_id = m2.conversation_id
                WHERE cp.user_id = :userId AND cp.left_at IS NULL
                GROUP BY m2.conversation_id
            ) latest ON m.sent_at = latest.max_sent_at AND m.conversation_id = latest.conversation_id
            ORDER BY m.sent_at DESC
        ';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('userId', $user->getId());
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result;
    }

    /**
     * Search messages by content
     */
    public function searchByContent(string $query, User $user, ?Conversation $conversation = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('m.content LIKE :query')
            ->andWhere('p.leftAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults(50);

        if ($conversation) {
            $qb->andWhere('m.conversation = :conversation')
                ->setParameter('conversation', $conversation);
        }

        return $qb->getQuery()->getResult();
    }
}