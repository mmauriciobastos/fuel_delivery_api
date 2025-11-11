<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use App\Service\TenantContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Base repository class that provides tenant-aware helper methods.
 * All entity repositories should extend this class to ensure tenant isolation.
 *
 * @template T of object
 * @template-extends ServiceEntityRepository<T>
 */
abstract class BaseTenantRepository extends ServiceEntityRepository
{
    protected TenantContext $tenantContext;

    public function __construct(ManagerRegistry $registry, string $entityClass, TenantContext $tenantContext)
    {
        parent::__construct($registry, $entityClass);
        $this->tenantContext = $tenantContext;
    }

    /**
     * Create a query builder with tenant context already applied.
     * The tenant filter will automatically be applied by Doctrine.
     *
     * @param string $alias The alias to use for the entity
     * @param string|null $indexBy The index to use
     * @return QueryBuilder
     */
    public function createTenantQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
    {
        return $this->createQueryBuilder($alias, $indexBy);
    }

    /**
     * Find an entity by ID, ensuring it belongs to the current tenant.
     * The tenant filter automatically handles this.
     *
     * @param mixed $id The entity ID
     * @return object|null The entity or null if not found
     */
    public function findByIdAndTenant($id): ?object
    {
        return $this->find($id);
    }

    /**
     * Find all entities for the current tenant.
     * The tenant filter automatically handles this.
     *
     * @return array<object> Array of entities
     */
    public function findAllForTenant(): array
    {
        return $this->findAll();
    }

    /**
     * Find entities by criteria for the current tenant.
     * The tenant filter automatically handles this.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array<object>
     */
    public function findByForTenant(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Find one entity by criteria for the current tenant.
     * The tenant filter automatically handles this.
     *
     * @param array<string, mixed> $criteria
     * @return object|null
     */
    public function findOneByForTenant(array $criteria): ?object
    {
        return $this->findOneBy($criteria);
    }

    /**
     * Count entities for the current tenant.
     * The tenant filter automatically handles this.
     *
     * @param array<string, mixed> $criteria
     * @return int
     */
    public function countForTenant(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("e.$field = :$field")
                ->setParameter($field, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get the current tenant from the tenant context.
     *
     * @return Tenant|null
     */
    protected function getCurrentTenant(): ?Tenant
    {
        return $this->tenantContext->getCurrentTenant();
    }

    /**
     * Check if a tenant context is set.
     *
     * @return bool
     */
    protected function hasTenant(): bool
    {
        return $this->tenantContext->hasTenant();
    }

    /**
     * Save an entity and flush changes.
     *
     * @param object $entity
     * @param bool $flush
     * @return object
     */
    public function save(object $entity, bool $flush = true): object
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $entity;
    }

    /**
     * Remove an entity and flush changes.
     *
     * @param object $entity
     * @param bool $flush
     * @return void
     */
    public function remove(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Flush all pending changes to the database.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
