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
}
