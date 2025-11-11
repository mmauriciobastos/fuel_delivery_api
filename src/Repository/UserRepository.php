<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use App\Entity\User;
use App\Service\TenantContext;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository for User entity with tenant-aware queries.
 *
 * @extends BaseTenantRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method User[] findAll()
 * @method User[] findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends BaseTenantRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry, TenantContext $tenantContext)
    {
        parent::__construct($registry, User::class, $tenantContext);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find a user by email within the current tenant.
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findOneByForTenant(['email' => strtolower(trim($email))]);

        return $user;
    }

    /**
     * Find a user by email and tenant.
     */
    public function findByEmailAndTenant(string $email, Tenant $tenant): ?User
    {
        return $this->findOneBy([
            'email' => strtolower(trim($email)),
            'tenant' => $tenant,
        ]);
    }

    /**
     * Check if email exists within the current tenant.
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $qb = $this->createTenantQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', strtolower(trim($email)));

        if (null !== $excludeUserId) {
            $qb->andWhere('u.id != :excludeId')
                ->setParameter('excludeId', $excludeUserId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Find all active users for the current tenant.
     *
     * @return User[]
     */
    public function findActiveUsers(): array
    {
        return $this->createTenantQueryBuilder('u')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users by role within the current tenant.
     *
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        $allUsers = $this->findActiveUsers();

        return array_filter($allUsers, fn (User $user) => $user->hasRole($role));
    }

    /**
     * Find administrators within the current tenant.
     *
     * @return User[]
     */
    public function findAdmins(): array
    {
        return $this->findByRole('ROLE_ADMIN');
    }

    /**
     * Count active users for the current tenant.
     */
    public function countActiveUsers(): int
    {
        return $this->countForTenant(['isActive' => true]);
    }

    /**
     * Count users by role within the current tenant.
     */
    public function countByRole(string $role): int
    {
        return \count($this->findByRole($role));
    }

    /**
     * Find recently created users.
     *
     * @return User[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createTenantQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users who haven't logged in recently.
     *
     * @return User[]
     */
    public function findInactiveUsers(\DateTimeInterface $since): array
    {
        return $this->createTenantQueryBuilder('u')
            ->where('u.lastLoginAt < :since OR u.lastLoginAt IS NULL')
            ->andWhere('u.isActive = :active')
            ->setParameter('since', $since)
            ->setParameter('active', true)
            ->orderBy('u.lastLoginAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search users by name or email within the current tenant.
     *
     * @return User[]
     */
    public function searchUsers(string $query): array
    {
        $searchTerm = '%' . strtolower(trim($query)) . '%';

        return $this->createTenantQueryBuilder('u')
            ->where('LOWER(u.email) LIKE :search')
            ->orWhere('LOWER(u.firstName) LIKE :search')
            ->orWhere('LOWER(u.lastName) LIKE :search')
            ->setParameter('search', $searchTerm)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
