<?php

namespace App\Repository;

use App\Entity\Attachment;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attachment>
 */
class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attachment::class);
    }

    /**
     * Find all attachments for a message
     */
    public function findByMessage(Message $message): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.message = :message')
            ->setParameter('message', $message)
            ->orderBy('a.uploadedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find attachments by file type
     */
    public function findByFileType(string $fileType): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.fileType = :fileType')
            ->setParameter('fileType', $fileType)
            ->orderBy('a.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find attachments by file name pattern
     */
    public function findByFileNamePattern(string $pattern): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.fileName LIKE :pattern')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('a.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all attachments in conversations where user is a participant
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.message', 'm')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired attachments (past their expiration date)
     */
    public function findExpired(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('a')
            ->where('a.expiresAt IS NOT NULL')
            ->andWhere('a.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total storage used by attachments (in bytes)
     */
    public function calculateTotalStorageUsed(): string
    {
        // Note: This assumes fileSize is stored as number of bytes in string format
        return $this->createQueryBuilder('a')
            ->select('SUM(CAST(a.fileSize AS BIGINT))')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count storage used by a specific user (in bytes)
     */
    public function calculateStorageUsedByUser(User $user): string
    {
        // Note: This assumes fileSize is stored as number of bytes in string format
        return $this->createQueryBuilder('a')
            ->select('SUM(CAST(a.fileSize AS BIGINT))')
            ->join('a.message', 'm')
            ->where('m.sender = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}