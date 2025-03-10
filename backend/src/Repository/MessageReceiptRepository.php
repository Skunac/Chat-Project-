<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\MessageReceipt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReceipt>
 */
class MessageReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReceipt::class);
    }

    /**
     * Find receipt for a specific message and user
     */
    public function findByMessageAndUser(Message $message, User $user): ?MessageReceipt
    {
        return $this->createQueryBuilder('r')
            ->where('r.message = :message')
            ->andWhere('r.user = :user')
            ->setParameter('message', $message)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all receipts for a message
     */
    public function findByMessage(Message $message): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.message = :message')
            ->setParameter('message', $message)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count delivered but unread messages for a user
     */
    public function countDeliveredButUnread(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->where('r.user = :user')
            ->andWhere('r.deliveredAt IS NOT NULL')
            ->andWhere('r.readAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all receipts for multiple messages
     */
    public function findByMessages(array $messages): array
    {
        if (empty($messages)) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->where('r.message IN (:messages)')
            ->setParameter('messages', $messages)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark messages as delivered for a user
     */
    public function markAsDelivered(array $messages, User $user): int
    {
        if (empty($messages)) {
            return 0;
        }

        $now = new \DateTime();

        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.deliveredAt', ':now')
            ->where('r.message IN (:messages)')
            ->andWhere('r.user = :user')
            ->andWhere('r.deliveredAt IS NULL')
            ->setParameter('messages', $messages)
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    /**
     * Mark messages as read for a user
     */
    public function markAsRead(array $messages, User $user): int
    {
        if (empty($messages)) {
            return 0;
        }

        $now = new \DateTime();

        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.readAt', ':now')
            ->where('r.message IN (:messages)')
            ->andWhere('r.user = :user')
            ->andWhere('r.readAt IS NULL')
            ->setParameter('messages', $messages)
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    /**
     * Get all users who have read a message
     */
    public function findReadByMessage(Message $message): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->select('u', 'r.readAt')
            ->where('r.message = :message')
            ->andWhere('r.readAt IS NOT NULL')
            ->setParameter('message', $message)
            ->orderBy('r.readAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all users who have received but not read a message
     */
    public function findDeliveredNotReadByMessage(Message $message): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->select('u', 'r.deliveredAt')
            ->where('r.message = :message')
            ->andWhere('r.deliveredAt IS NOT NULL')
            ->andWhere('r.readAt IS NULL')
            ->setParameter('message', $message)
            ->orderBy('r.deliveredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}