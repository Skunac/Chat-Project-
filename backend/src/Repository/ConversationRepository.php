<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Find conversations where a user is a participant
     */
    public function findByParticipant(User $user, bool $includeArchived = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC');

        if (!$includeArchived) {
            $qb->andWhere('p.isArchived = :archived')
                ->setParameter('archived', false);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find direct (one-to-one) conversation between two users
     */
    public function findDirectConversation(User $user1, User $user2): ?Conversation
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.isGroup = :isGroup')
            ->setParameter('isGroup', false)
            ->join('c.participants', 'p1')
            ->join('c.participants', 'p2')
            ->andWhere('p1.user = :user1')
            ->andWhere('p2.user = :user2')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setMaxResults(1);

        $result = $qb->getQuery()->getResult();
        return $result ? $result[0] : null;
    }

    /**
     * Find pinned conversations for a user
     */
    public function findPinnedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('p.isPinned = :pinned')
            ->setParameter('user', $user)
            ->setParameter('pinned', true)
            ->orderBy('p.pinPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find archived conversations for a user
     */
    public function findArchivedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('p.isArchived = :archived')
            ->setParameter('user', $user)
            ->setParameter('archived', true)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find conversations by name (for search)
     */
    public function findByNameContaining(string $query, User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->andWhere('c.name LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}