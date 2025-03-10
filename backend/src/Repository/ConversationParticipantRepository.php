<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConversationParticipant>
 */
class ConversationParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConversationParticipant::class);
    }

    /**
     * Find participant record for a user in a conversation
     */
    public function findOneByUserAndConversation(User $user, Conversation $conversation): ?ConversationParticipant
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.conversation = :conversation')
            ->setParameter('user', $user)
            ->setParameter('conversation', $conversation)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all participants in a conversation
     */
    public function findByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.leftAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all active participants (ones who haven't left) in a conversation
     */
    public function findActiveParticipants(Conversation $conversation): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.leftAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all admin participants in a conversation
     */
    public function findAdmins(Conversation $conversation): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.conversation = :conversation')
            ->andWhere('p.isAdmin = :isAdmin')
            ->andWhere('p.leftAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('isAdmin', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a user is an admin of a conversation
     */
    public function isUserAdmin(User $user, Conversation $conversation): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->where('p.conversation = :conversation')
            ->andWhere('p.user = :user')
            ->andWhere('p.isAdmin = :isAdmin')
            ->andWhere('p.leftAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->setParameter('isAdmin', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$result > 0;
    }

    /**
     * Check if a user is a member of a conversation
     */
    public function isUserMember(User $user, Conversation $conversation): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->where('p.conversation = :conversation')
            ->andWhere('p.user = :user')
            ->andWhere('p.leftAt IS NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$result > 0;
    }
}
