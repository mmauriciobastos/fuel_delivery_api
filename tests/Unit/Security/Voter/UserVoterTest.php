<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Tenant;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

class UserVoterTest extends TestCase
{
    private UserVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
    }

    public function testSupportsUserEntity(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);
        $targetUser = $this->createUser(tenant: $tenant);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        // Vote should not abstain for supported attributes
        $this->assertNotEquals(UserVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $targetUser, ['view']));
        $this->assertNotEquals(UserVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $targetUser, ['edit']));
        $this->assertNotEquals(UserVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $targetUser, ['delete']));
    }

    public function testDoesNotSupportInvalidAttribute(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);
        $targetUser = $this->createUser(tenant: $tenant);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        // Vote should abstain for unsupported attributes
        $this->assertEquals(UserVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $targetUser, ['invalid']));
    }

    public function testDoesNotSupportNonUserSubject(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        // Vote should abstain for non-User subjects
        $this->assertEquals(UserVoter::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), ['view']));
    }

    public function testDeniesAccessForUnauthenticatedUser(): void
    {
        $targetUser = $this->createUser();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $targetUser, ['view']);

        $this->assertEquals(UserVoter::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessForNonAdminUser(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_DISPATCHER']);
        $targetUser = $this->createUser(tenant: $tenant);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, ['view']);

        $this->assertEquals(UserVoter::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessForDifferentTenant(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant1, roles: ['ROLE_ADMIN']);
        $targetUser = $this->createUser(tenant: $tenant2);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, ['view']);

        $this->assertEquals(UserVoter::ACCESS_DENIED, $result);
    }

    public function testAllowsAdminToViewUserInSameTenant(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);
        $targetUser = $this->createUser(tenant: $tenant);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, ['view']);

        $this->assertEquals(UserVoter::ACCESS_GRANTED, $result);
    }

    public function testAllowsAdminToEditUserInSameTenant(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);
        $targetUser = $this->createUser(tenant: $tenant);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, ['edit']);

        $this->assertEquals(UserVoter::ACCESS_GRANTED, $result);
    }

    public function testAllowsAdminToDeleteOtherUserInSameTenant(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);
        $targetUser = $this->createUser(tenant: $tenant);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $result = $this->voter->vote($token, $targetUser, ['delete']);

        $this->assertEquals(UserVoter::ACCESS_GRANTED, $result);
    }

    public function testDeniesAdminDeletingThemselves(): void
    {
        $tenant = $this->createTenant();
        $currentUser = $this->createUser(tenant: $tenant, roles: ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);

        $result = $this->voter->vote($token, $currentUser, ['delete']);

        $this->assertEquals(UserVoter::ACCESS_DENIED, $result);
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
