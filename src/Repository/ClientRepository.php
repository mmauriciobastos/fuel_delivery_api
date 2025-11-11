<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Find active clients for a tenant.
     *
     * @return Client[]
     */
    public function findActiveByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenant = :tenant')
            ->andWhere('c.isActive = :active')
            ->setParameter('tenant', $tenant)
            ->setParameter('active', true)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find client by email within a tenant.
     */
    public function findByEmailAndTenant(string $email, Tenant $tenant): ?Client
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenant = :tenant')
            ->andWhere('c.email = :email')
            ->setParameter('tenant', $tenant)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search clients by company name within a tenant.
     *
     * @return Client[]
     */
    public function searchByCompanyName(string $query, Tenant $tenant): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tenant = :tenant')
            ->andWhere('LOWER(c.companyName) LIKE LOWER(:query)')
            ->setParameter('tenant', $tenant)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count active clients for a tenant.
     */
    public function countActiveByTenant(Tenant $tenant): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.tenant = :tenant')
            ->andWhere('c.isActive = :active')
            ->setParameter('tenant', $tenant)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
