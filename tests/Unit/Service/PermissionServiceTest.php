<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Tenant;
use App\Entity\User;
use App\Service\PermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

class PermissionServiceTest extends TestCase
{
    private AuthorizationCheckerInterface $authChecker;

    private PermissionService $permissionService;

    protected function setUp(): void
    {
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->permissionService = new PermissionService($this->authChecker);
    }

    public function testHasRole(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->hasRole('ROLE_ADMIN'));
    }

    public function testHasAnyRoleReturnsTrue(): void
    {
        $this->authChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return 'ROLE_DISPATCHER' === $role;
            });

        $this->assertTrue($this->permissionService->hasAnyRole(['ROLE_ADMIN', 'ROLE_DISPATCHER']));
    }

    public function testHasAnyRoleReturnsFalse(): void
    {
        $this->authChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturn(false);

        $this->assertFalse($this->permissionService->hasAnyRole(['ROLE_ADMIN', 'ROLE_DISPATCHER']));
    }

    public function testHasAllRolesReturnsTrue(): void
    {
        $this->authChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->hasAllRoles(['ROLE_USER', 'ROLE_DISPATCHER']));
    }

    public function testHasAllRolesReturnsFalse(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->assertFalse($this->permissionService->hasAllRoles(['ROLE_ADMIN', 'ROLE_DISPATCHER']));
    }

    public function testCanCheckPermission(): void
    {
        $user = $this->createUser();

        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('view', $user)
            ->willReturn(true);

        $this->assertTrue($this->permissionService->can('view', $user));
    }

    public function testDenyAccessUnlessGrantedThrowsException(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied.');

        $this->permissionService->denyAccessUnlessGranted('ROLE_ADMIN');
    }

    public function testDenyAccessUnlessGrantedDoesNotThrow(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->permissionService->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->assertTrue(true); // No exception thrown
    }

    public function testIsAdmin(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->isAdmin());
    }

    public function testIsDispatcher(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_DISPATCHER')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->isDispatcher());
    }

    public function testIsUser(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->isUser());
    }

    public function testCanManageUsers(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->canManageUsers());
    }

    public function testCanManageClients(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->canManageClients());
    }

    public function testCanManageTrucks(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->canManageTrucks());
    }

    public function testCanManageOrders(): void
    {
        $this->authChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return 'ROLE_DISPATCHER' === $role;
            });

        $this->assertTrue($this->permissionService->canManageOrders());
    }

    public function testCanManageTenant(): void
    {
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->assertTrue($this->permissionService->canManageTenant());
    }

    public function testIsSameTenantReturnsTrue(): void
    {
        $tenant = $this->createTenant();
        $user1 = $this->createUser($tenant);
        $user2 = $this->createUser($tenant);

        $this->assertTrue($this->permissionService->isSameTenant($user1, $user2));
    }

    public function testIsSameTenantReturnsFalse(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $user1 = $this->createUser($tenant1);
        $user2 = $this->createUser($tenant2);

        $this->assertFalse($this->permissionService->isSameTenant($user1, $user2));
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

    private function createUser(?Tenant $tenant = null): User
    {
        $tenant = $tenant ?? $this->createTenant();

        $user = new User();
        $reflection = new \ReflectionClass($user);

        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, Uuid::v4());

        $user->setTenant($tenant);

        return $user;
    }
}
