<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Tenant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Security voter for Tenant entity access control.
 *
 * Enforces strict permissions for tenant management.
 * Only ROLE_ADMIN can view and edit their own tenant.
 * Tenant creation and deletion are handled at system level (not via API).
 *
 * @extends Voter<string, Tenant>
 */
final class TenantVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Tenant;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be authenticated
        if (!$user instanceof User) {
            return false;
        }

        /** @var Tenant $tenant */
        $tenant = $subject;

        // Only ROLE_ADMIN can manage tenant settings
        if (!\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        // Can only manage their own tenant
        $userTenant = $user->getTenant();

        if (null === $userTenant || $userTenant->getId() !== $tenant->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => true,
            default => false,
        };
    }
}
