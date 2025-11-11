<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    /**
     * Find a valid refresh token by token string.
     */
    public function findValidToken(string $token): ?RefreshToken
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.token = :token')
            ->andWhere('rt.isRevoked = false')
            ->andWhere('rt.validUntil > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all refresh tokens for a user.
     *
     * @return array<RefreshToken>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Revoke all refresh tokens for a user.
     *
     * @return int Number of tokens revoked
     */
    public function revokeAllForUser(User $user): int
    {
        return $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', 'true')
            ->where('rt.user = :user')
            ->andWhere('rt.isRevoked = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete expired refresh tokens (cleanup).
     *
     * @return int Number of tokens deleted
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.validUntil < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Save a refresh token.
     */
    public function save(RefreshToken $refreshToken, bool $flush = true): void
    {
        $this->getEntityManager()->persist($refreshToken);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a refresh token.
     */
    public function remove(RefreshToken $refreshToken, bool $flush = true): void
    {
        $this->getEntityManager()->remove($refreshToken);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
