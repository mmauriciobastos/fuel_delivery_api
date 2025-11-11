<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Central service for permission validation and access control.
 *
 * Provides methods to check user permissions and enforce access rules
 * with tenant isolation and role-based access control.
 */
final readonly class PermissionService
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    /**
     * Check if current user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->authorizationChecker->isGranted($role);
    }

    /**
     * Check if current user has any of the given roles.
     *
     * @param string[] $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current user has all of the given roles.
     *
     * @param string[] $roles
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current user can perform an action on a subject.
     */
    public function can(string $attribute, mixed $subject): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $subject);
    }

    /**
     * Ensure current user has a specific role or throw exception.
     *
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGranted(string $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException($message);
        }
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Check if user is a dispatcher.
     */
    public function isDispatcher(): bool
    {
        return $this->hasRole('ROLE_DISPATCHER');
    }

    /**
     * Check if user is a regular user.
     */
    public function isUser(): bool
    {
        return $this->hasRole('ROLE_USER');
    }

    /**
     * Check if user can manage other users (admin only).
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can manage clients (admin or dispatcher).
     */
    public function canManageClients(): bool
    {
        return $this->hasAnyRole(['ROLE_ADMIN', 'ROLE_DISPATCHER']);
    }

    /**
     * Check if user can manage trucks (admin or dispatcher).
     */
    public function canManageTrucks(): bool
    {
        return $this->hasAnyRole(['ROLE_ADMIN', 'ROLE_DISPATCHER']);
    }

    /**
     * Check if user can manage orders (admin or dispatcher).
     */
    public function canManageOrders(): bool
    {
        return $this->hasAnyRole(['ROLE_ADMIN', 'ROLE_DISPATCHER']);
    }

    /**
     * Check if user can manage tenant settings (admin only).
     */
    public function canManageTenant(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Verify two users belong to the same tenant.
     */
    public function isSameTenant(User $user1, User $user2): bool
    {
        $tenant1 = $user1->getTenant();
        $tenant2 = $user2->getTenant();

        if (null === $tenant1 || null === $tenant2) {
            return false;
        }

        return $tenant1->getId() === $tenant2->getId();
    }
}
