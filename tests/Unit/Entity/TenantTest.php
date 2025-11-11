<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Tenant;
use App\Enum\TenantStatus;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    public function testTenantCreation(): void
    {
        $tenant = new Tenant();

        $this->assertNull($tenant->getId());
        $this->assertNull($tenant->getName());
        $this->assertNull($tenant->getSubdomain());
        $this->assertEquals(TenantStatus::TRIAL, $tenant->getStatus());
        $this->assertNull($tenant->getCreatedAt());
        $this->assertNull($tenant->getUpdatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Test Company');
        $tenant->setSubdomain('test-company');
        $tenant->setStatus(TenantStatus::ACTIVE);

        $this->assertEquals('Test Company', $tenant->getName());
        $this->assertEquals('test-company', $tenant->getSubdomain());
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->getStatus());
    }

    public function testSubdomainNormalization(): void
    {
        $tenant = new Tenant();
        $tenant->setSubdomain('  TEST-Company  ');

        $this->assertEquals('test-company', $tenant->getSubdomain());
    }

    public function testLifecycleCallbacks(): void
    {
        $tenant = new Tenant();
        $tenant->setName('Test Company');
        $tenant->setSubdomain('test-company');

        // Simulate PrePersist
        $tenant->onPrePersist();
        $this->assertInstanceOf(\DateTimeImmutable::class, $tenant->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $tenant->getUpdatedAt());

        $createdAt = $tenant->getCreatedAt();
        $updatedAt = $tenant->getUpdatedAt();

        sleep(1);

        // Simulate PreUpdate
        $tenant->onPreUpdate();
        $this->assertEquals($createdAt, $tenant->getCreatedAt());
        $this->assertGreaterThan($updatedAt, $tenant->getUpdatedAt());
    }

    public function testDefaultStatus(): void
    {
        $tenant = new Tenant();

        $this->assertEquals(TenantStatus::TRIAL, $tenant->getStatus());
    }

    public function testIsOperational(): void
    {
        $tenant = new Tenant();

        // Trial status should be operational
        $tenant->setStatus(TenantStatus::TRIAL);
        $this->assertTrue($tenant->isOperational());

        // Active status should be operational
        $tenant->setStatus(TenantStatus::ACTIVE);
        $this->assertTrue($tenant->isOperational());

        // Suspended status should not be operational
        $tenant->setStatus(TenantStatus::SUSPENDED);
        $this->assertFalse($tenant->isOperational());
    }

    public function testActivateMethod(): void
    {
        $tenant = new Tenant();
        $tenant->setStatus(TenantStatus::TRIAL);

        $result = $tenant->activate();

        $this->assertSame($tenant, $result);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->getStatus());
    }

    public function testSuspendMethod(): void
    {
        $tenant = new Tenant();
        $tenant->setStatus(TenantStatus::ACTIVE);

        $result = $tenant->suspend();

        $this->assertSame($tenant, $result);
        $this->assertEquals(TenantStatus::SUSPENDED, $tenant->getStatus());
    }

    public function testConvertToTrialMethod(): void
    {
        $tenant = new Tenant();
        $tenant->setStatus(TenantStatus::ACTIVE);

        $result = $tenant->convertToTrial();

        $this->assertSame($tenant, $result);
        $this->assertEquals(TenantStatus::TRIAL, $tenant->getStatus());
    }
}
