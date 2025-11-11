<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Security voter for User entity access control.
 *
 * Enforces tenant isolation and role-based permissions for user management.
 * Only ROLE_ADMIN can view, create, edit, and delete users within their tenant.
 *
 * @extends Voter<string, User>
 */
final class UserVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be authenticated
        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        // Only ROLE_ADMIN can manage users
        if (!\in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            return false;
        }

        // Ensure same tenant
        $currentTenant = $currentUser->getTenant();
        $targetTenant = $targetUser->getTenant();

        if (null === $currentTenant || null === $targetTenant || $currentTenant->getId() !== $targetTenant->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $targetUser),
            self::EDIT => $this->canEdit($currentUser, $targetUser),
            self::DELETE => $this->canDelete($currentUser, $targetUser),
            default => false,
        };
    }

    private function canView(User $currentUser, User $targetUser): bool
    {
        // ROLE_ADMIN can view any user in their tenant
        return true;
    }

    private function canEdit(User $currentUser, User $targetUser): bool
    {
        // ROLE_ADMIN can edit any user in their tenant
        return true;
    }

    private function canDelete(User $currentUser, User $targetUser): bool
    {
        // Cannot delete yourself
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        // ROLE_ADMIN can delete any other user in their tenant
        return true;
    }
}
