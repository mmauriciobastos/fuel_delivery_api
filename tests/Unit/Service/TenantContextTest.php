<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Tenant;
use App\Enum\TenantStatus;
use App\Service\TenantContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for TenantContext service.
 */
class TenantContextTest extends TestCase
{
    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        $this->tenantContext = new TenantContext();
    }

    public function testInitialStateHasNoTenant(): void
    {
        $this->assertFalse($this->tenantContext->hasTenant());
        $this->assertNull($this->tenantContext->getCurrentTenant());
        $this->assertNull($this->tenantContext->getCurrentTenantId());
    }

    public function testSetCurrentTenant(): void
    {
        $tenant = $this->createMockTenant();
        
        $this->tenantContext->setCurrentTenant($tenant);
        
        $this->assertTrue($this->tenantContext->hasTenant());
        $this->assertSame($tenant, $this->tenantContext->getCurrentTenant());
    }

    public function testGetCurrentTenantId(): void
    {
        $uuid = Uuid::v4();
        $tenant = $this->createMockTenant($uuid);
        
        $this->tenantContext->setCurrentTenant($tenant);
        
        $this->assertEquals($uuid->toRfc4122(), $this->tenantContext->getCurrentTenantId());
    }

    public function testClearRemovesTenant(): void
    {
        $tenant = $this->createMockTenant();
        $this->tenantContext->setCurrentTenant($tenant);
        
        $this->assertTrue($this->tenantContext->hasTenant());
        
        $this->tenantContext->clear();
        
        $this->assertFalse($this->tenantContext->hasTenant());
        $this->assertNull($this->tenantContext->getCurrentTenant());
        $this->assertNull($this->tenantContext->getCurrentTenantId());
    }

    public function testSetNullTenant(): void
    {
        $tenant = $this->createMockTenant();
        $this->tenantContext->setCurrentTenant($tenant);
        
        $this->tenantContext->setCurrentTenant(null);
        
        $this->assertFalse($this->tenantContext->hasTenant());
        $this->assertNull($this->tenantContext->getCurrentTenant());
    }

    public function testGetCurrentTenantIdReturnsNullWhenNoTenant(): void
    {
        $this->assertNull($this->tenantContext->getCurrentTenantId());
    }

    public function testMultipleTenantChanges(): void
    {
        $tenant1 = $this->createMockTenant(Uuid::v4(), 'Tenant 1');
        $tenant2 = $this->createMockTenant(Uuid::v4(), 'Tenant 2');
        
        $this->tenantContext->setCurrentTenant($tenant1);
        $this->assertSame($tenant1, $this->tenantContext->getCurrentTenant());
        
        $this->tenantContext->setCurrentTenant($tenant2);
        $this->assertSame($tenant2, $this->tenantContext->getCurrentTenant());
        
        $this->tenantContext->clear();
        $this->assertNull($this->tenantContext->getCurrentTenant());
    }

    /**
     * Helper method to create a mock tenant for testing.
     */
    private function createMockTenant(?Uuid $id = null, string $name = 'Test Tenant'): Tenant
    {
        $tenant = $this->getMockBuilder(Tenant::class)
            ->onlyMethods(['getId'])
            ->getMock();
        
        if ($id !== null) {
            $tenant->method('getId')->willReturn($id);
        }
        
        // Use reflection to set private properties
        $reflection = new \ReflectionClass(Tenant::class);
        
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($tenant, $name);
        
        $subdomainProperty = $reflection->getProperty('subdomain');
        $subdomainProperty->setAccessible(true);
        $subdomainProperty->setValue($tenant, strtolower(str_replace(' ', '-', $name)));
        
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($tenant, TenantStatus::ACTIVE);
        
        return $tenant;
    }
}
