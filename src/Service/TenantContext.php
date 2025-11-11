<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant;

/**
 * Service to manage the current tenant context throughout the application lifecycle.
 * This service is used by the TenantFilter to automatically isolate tenant data.
 */
class TenantContext
{
    private ?Tenant $currentTenant = null;

    /**
     * Set the current tenant context.
     *
     * @param Tenant|null $tenant The tenant to set as current
     */
    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /**
     * Get the current tenant.
     *
     * @return Tenant|null The current tenant or null if not set
     */
    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Check if a tenant context is currently set.
     *
     * @return bool True if tenant is set, false otherwise
     */
    public function hasTenant(): bool
    {
        return null !== $this->currentTenant;
    }

    /**
     * Get the current tenant ID.
     *
     * @return string|null The tenant ID or null if not set
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenant?->getId()?->toRfc4122();
    }

    /**
     * Clear the current tenant context.
     */
    public function clear(): void
    {
        $this->currentTenant = null;
    }
}
