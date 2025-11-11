<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\Tenant;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Doctrine filter to automatically isolate tenant data.
 * This filter adds a WHERE clause to all queries to filter by tenant_id.
 */
class TenantFilter extends SQLFilter
{
    /**
     * Add the tenant_id filter to the SQL query.
     *
     * @param ClassMetadata<object> $targetEntity The target entity metadata
     * @param string $targetTableAlias The alias of the target table
     *
     * @return string The SQL condition to add to the WHERE clause
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Don't filter the Tenant entity itself
        if (Tenant::class === $targetEntity->getReflectionClass()->getName()) {
            return '';
        }

        // Check if the entity has a tenant relationship
        if (!$targetEntity->hasAssociation('tenant')) {
            return '';
        }

        // Try to get the tenant_id parameter from the filter
        // During authentication, this parameter may not be set yet
        try {
            $tenantId = $this->getParameter('tenant_id');
        } catch (\InvalidArgumentException $e) {
            // Parameter not set - this can happen during authentication
            // Return empty string to allow query without tenant filtering
            return '';
        }

        // Check if tenant_id is empty or null string
        if ('' === $tenantId || 'null' === $tenantId) {
            // If no tenant is set, prevent all access by returning impossible condition
            // This is a security measure to prevent accidental data leaks
            return '1 = 0';
        }

        // Get the tenant column name (usually tenant_id)
        $association = $targetEntity->getAssociationMapping('tenant');
        /**
         * @var array<\Doctrine\ORM\Mapping\JoinColumnMapping> $joinColumns
         *
         * @phpstan-ignore property.notFound
         */
        $joinColumns = $association->joinColumns ?? [];

        if (empty($joinColumns)) {
            return '';
        }

        $tenantColumn = $joinColumns[0]->name ?? 'tenant_id';

        // Return the SQL condition to filter by tenant_id
        return \sprintf('%s.%s = %s', $targetTableAlias, $tenantColumn, $tenantId);
    }
}
