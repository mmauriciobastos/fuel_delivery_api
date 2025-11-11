<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use App\Enum\TenantStatus;
use App\Service\TenantContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Repository for Tenant entity.
 * Note: TenantRepository does not extend BaseTenantRepository because
 * the Tenant entity itself is the root of the tenant hierarchy and
 * does not have a tenant relationship.
 *
 * @extends ServiceEntityRepository<Tenant>
 *
 * @method Tenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tenant[]    findAll()
 * @method Tenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TenantRepository extends ServiceEntityRepository
{
    private TenantContext $tenantContext;

    public function __construct(ManagerRegistry $registry, TenantContext $tenantContext)
    {
        parent::__construct($registry, Tenant::class);
        $this->tenantContext = $tenantContext;
    }

    /**
     * Save a tenant entity
     */
    public function save(Tenant $tenant, bool $flush = true): void
    {
        $this->getEntityManager()->persist($tenant);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a tenant entity
     */
    public function remove(Tenant $tenant, bool $flush = true): void
    {
        $this->getEntityManager()->remove($tenant);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find a tenant by subdomain
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return $this->findOneBy(['subdomain' => strtolower($subdomain)]);
    }

    /**
     * Find a tenant by UUID
     */
    public function findById(Uuid $id): ?Tenant
    {
        return $this->find($id);
    }

    /**
     * Find all active tenants
     *
     * @return Tenant[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', TenantStatus::ACTIVE)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all operational tenants (active or trial)
     *
     * @return Tenant[]
     */
    public function findOperational(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status IN (:statuses)')
            ->setParameter('statuses', [TenantStatus::ACTIVE, TenantStatus::TRIAL])
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tenants by status
     *
     * @return Tenant[]
     */
    public function findByStatus(TenantStatus $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if subdomain exists
     */
    public function subdomainExists(string $subdomain, ?Uuid $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.subdomain = :subdomain')
            ->setParameter('subdomain', strtolower($subdomain));

        if ($excludeId !== null) {
            $qb->andWhere('t.id != :excludeId')
                ->setParameter('excludeId', $excludeId, UuidType::NAME);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Count tenants by status
     */
    public function countByStatus(TenantStatus $status): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find recently created tenants
     *
     * @return Tenant[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
