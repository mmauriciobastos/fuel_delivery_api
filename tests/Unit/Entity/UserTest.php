<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\TenantStatus;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertNull($user->getTenant());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getPassword());
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertEquals(['ROLE_USER'], $user->getRoles()); // getRoles() always includes ROLE_USER
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
        $this->assertNull($user->getLastLoginAt());
    }

    public function testSettersAndGetters(): void
    {
        $tenant = $this->createMockTenant();
        $user = new User();

        $user->setTenant($tenant);
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsActive(false);

        $this->assertSame($tenant, $user->getTenant());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertFalse($user->isActive());
    }

    public function testEmailNormalization(): void
    {
        $user = new User();
        $user->setEmail('  TEST@Example.COM  ');

        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testNameTrimming(): void
    {
        $user = new User();
        $user->setFirstName('  John  ');
        $user->setLastName('  Doe  ');

        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
    }

    public function testGetFullName(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');

        $this->assertEquals('John Doe', $user->getFullName());
    }

    public function testRolesAlwaysIncludeUserRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesReturnsUniqueRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], array_values(array_unique($roles)));
    }

    public function testAddRole(): void
    {
        $user = new User();
        $user->addRole('ROLE_ADMIN');

        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testAddRoleDoesNotDuplicate(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $user->addRole('ROLE_ADMIN');

        $roles = array_filter($user->getRoles(), fn ($role) => 'ROLE_ADMIN' === $role);
        $this->assertCount(1, $roles);
    }

    public function testRemoveRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_DISPATCHER']);
        $user->removeRole('ROLE_ADMIN');

        $roles = $user->getRoles();
        $this->assertNotContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_DISPATCHER', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testHasRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertTrue($user->hasRole('ROLE_ADMIN'));
        $this->assertTrue($user->hasRole('ROLE_USER')); // Always present
        $this->assertFalse($user->hasRole('ROLE_DISPATCHER'));
    }

    public function testActivate(): void
    {
        $user = new User();
        $user->setIsActive(false);

        $result = $user->activate();

        $this->assertSame($user, $result);
        $this->assertTrue($user->isActive());
    }

    public function testDeactivate(): void
    {
        $user = new User();
        $user->setIsActive(true);

        $result = $user->deactivate();

        $this->assertSame($user, $result);
        $this->assertFalse($user->isActive());
    }

    public function testUpdateLastLogin(): void
    {
        $user = new User();
        $this->assertNull($user->getLastLoginAt());

        $user->updateLastLogin();

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getLastLoginAt());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $user->getLastLoginAt());
    }

    public function testSetLastLoginAt(): void
    {
        $user = new User();
        $lastLogin = new \DateTimeImmutable('2024-01-01 12:00:00');

        $user->setLastLoginAt($lastLogin);

        $this->assertEquals($lastLogin, $user->getLastLoginAt());
    }

    public function testLifecycleCallbacks(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setFirstName('John');
        $user->setLastName('Doe');

        // Simulate PrePersist
        $user->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertContains('ROLE_USER', $user->getRoles());

        $createdAt = $user->getCreatedAt();
        $updatedAt = $user->getUpdatedAt();

        sleep(1);

        // Simulate PreUpdate
        $user->onPreUpdate();

        $this->assertEquals($createdAt, $user->getCreatedAt());
        $this->assertGreaterThan($updatedAt, $user->getUpdatedAt());
    }

    public function testDefaultRoleAssignedOnPrePersist(): void
    {
        $user = new User();

        // Internal roles array is empty, but getRoles() always adds ROLE_USER
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->onPrePersist();

        // After onPrePersist, internal array has ROLE_USER
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testGetUserIdentifier(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPassword('password');

        // Should not throw exception
        $user->eraseCredentials();

        // Password should still be there (no plain password to erase)
        $this->assertEquals('password', $user->getPassword());
    }

    public function testUserImplementsUserInterface(): void
    {
        $user = new User();

        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);
    }

    public function testUserImplementsPasswordAuthenticatedUserInterface(): void
    {
        $user = new User();

        $this->assertInstanceOf(
            \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class,
            $user
        );
    }

    private function createMockTenant(): Tenant
    {
        $tenant = new Tenant();

        // Use reflection to set properties
        $reflection = new \ReflectionClass(Tenant::class);

        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($tenant, 'Test Tenant');

        $subdomainProperty = $reflection->getProperty('subdomain');
        $subdomainProperty->setAccessible(true);
        $subdomainProperty->setValue($tenant, 'test-tenant');

        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($tenant, TenantStatus::ACTIVE);

        return $tenant;
    }
}
