<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Tenant;
use App\Entity\User;
use App\Security\Voter\TenantVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

class TenantVoterTest extends TestCase
{
    private TenantVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TenantVoter();
    }

    public function testSupportsViewAndEdit(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Voter should not abstain for supported attributes
        $this->assertNotEquals(TenantVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $tenant, ['view']));
        $this->assertNotEquals(TenantVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $tenant, ['edit']));
    }

    public function testDoesNotSupportDeleteOperation(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Delete is not supported, so voter should abstain
        $this->assertEquals(TenantVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $tenant, ['delete']));
    }

    public function testDoesNotSupportNonTenantSubject(): void
    {
        $user = $this->createUser(roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->assertEquals(TenantVoter::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), ['view']));
    }

    public function testDeniesAccessForUnauthenticatedUser(): void
    {
        $tenant = $this->createTenant();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $tenant, ['view']);

        $this->assertEquals(TenantVoter::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessForNonAdminUser(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(tenant: $tenant, roles: ['ROLE_DISPATCHER']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $tenant, ['view']);

        $this->assertEquals(TenantVoter::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessForDifferentTenant(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $user = $this->createUser(tenant: $tenant1, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $tenant2, ['view']);

        $this->assertEquals(TenantVoter::ACCESS_DENIED, $result);
    }

    public function testAllowsAdminToViewOwnTenant(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $tenant, ['view']);

        $this->assertEquals(TenantVoter::ACCESS_GRANTED, $result);
    }

    public function testAllowsAdminToEditOwnTenant(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $tenant, ['edit']);

        $this->assertEquals(TenantVoter::ACCESS_GRANTED, $result);
    }

    private function createTenant(): Tenant
    {
        $tenant = new Tenant();
        $reflection = new \ReflectionClass($tenant);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($tenant, Uuid::v4());

        return $tenant;
    }

    private function createUser(?Tenant $tenant = null, array $roles = ['ROLE_USER']): User
    {
        $tenant = $tenant ?? $this->createTenant();

        $user = new User();
        $reflection = new \ReflectionClass($user);

        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, Uuid::v4());

        $user->setTenant($tenant);
        $user->setRoles($roles);

        return $user;
    }
}
