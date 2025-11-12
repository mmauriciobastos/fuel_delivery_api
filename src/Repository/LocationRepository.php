<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Location;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Find all locations for a specific client.
     *
     * @return Location[]
     */
    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.client = :client')
            ->setParameter('client', $client)
            ->orderBy('l.isPrimary', 'DESC')
            ->addOrderBy('l.city', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the primary location for a client.
     */
    public function findPrimaryByClient(Client $client): ?Location
    {
        return $this->createQueryBuilder('l')
            ->where('l.client = :client')
            ->andWhere('l.isPrimary = :isPrimary')
            ->setParameter('client', $client)
            ->setParameter('isPrimary', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find locations by tenant with optional filters.
     *
     * @param array<string, mixed> $filters
     *
     * @return Location[]
     */
    public function findByTenantAndFilters(Tenant $tenant, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('l')
            ->join('l.client', 'c')
            ->where('c.tenant = :tenant')
            ->setParameter('tenant', $tenant);

        if (isset($filters['city'])) {
            $qb->andWhere('l.city LIKE :city')
               ->setParameter('city', '%' . $filters['city'] . '%');
        }

        if (isset($filters['state'])) {
            $qb->andWhere('l.state = :state')
               ->setParameter('state', $filters['state']);
        }

        if (isset($filters['isPrimary'])) {
            $qb->andWhere('l.isPrimary = :isPrimary')
               ->setParameter('isPrimary', $filters['isPrimary']);
        }

        return $qb->orderBy('l.city', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count locations for a specific client.
     */
    public function countByClient(Client $client): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find locations with coordinates (geocoded).
     *
     * @return Location[]
     */
    public function findGeocodedLocations(Tenant $tenant): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.client', 'c')
            ->where('c.tenant = :tenant')
            ->andWhere('l.latitude IS NOT NULL')
            ->andWhere('l.longitude IS NOT NULL')
            ->setParameter('tenant', $tenant)
            ->getQuery()
            ->getResult();
    }
}
