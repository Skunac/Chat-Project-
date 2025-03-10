<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\MessageReaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageReaction>
 */
class MessageReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageReaction::class);
    }

    /**
     * Find reaction by message, user, and reaction type
     */
    public function findOneByMessageUserAndReaction(Message $message, User $user, string $reaction): ?MessageReaction
    {
        return $this->createQueryBuilder('r')
            ->where('r.message = :message')
            ->andWhere('r.user = :user')
            ->andWhere('r.reaction = :reaction')
            ->setParameter('message', $message)
            ->setParameter('user', $user)
            ->setParameter('reaction', $reaction)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all reactions for a message
     */
    public function findByMessage(Message $message): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.message = :message')
            ->setParameter('message', $message)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all reactions by a specific user for a message
     */
    public function findByMessageAndUser(Message $message, User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.message = :message')
            ->andWhere('r.user = :user')
            ->setParameter('message', $message)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count reactions by type for a message
     */
    public function countByMessageAndReaction(Message $message, string $reaction): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->where('r.message = :message')
            ->andWhere('r.reaction = :reaction')
            ->setParameter('message', $message)
            ->setParameter('reaction', $reaction)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get reaction counts grouped by reaction type for a message
     */
    public function getReactionCountsByMessage(Message $message): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.reaction, COUNT(r) as count')
            ->where('r.message = :message')
            ->setParameter('message', $message)
            ->groupBy('r.reaction')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'count', 'reaction');
    }

    /**
     * Get users who reacted with a specific reaction to a message
     */
    public function getUsersByMessageAndReaction(Message $message, string $reaction): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->select('u')
            ->where('r.message = :message')
            ->andWhere('r.reaction = :reaction')
            ->setParameter('message', $message)
            ->setParameter('reaction', $reaction)
            ->getQuery()
            ->getResult();
    }
}
