<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to add custom claims to JWT token payload.
 *
 * Adds tenant_id and other user information to the JWT token
 * to enable tenant-scoped authentication and authorization.
 */
readonly class JWTCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJWTCreated',
        ];
    }

    /**
     * Add custom claims to JWT payload.
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $tenant = $user->getTenant();
        $userId = $user->getId();

        if (null === $tenant || null === $tenant->getId()) {
            return;
        }

        if (null === $userId) {
            return;
        }

        $payload = $event->getData();

        // Add JWT ID (jti) for token blacklist functionality
        $payload['jti'] = bin2hex(random_bytes(32));

        // Add tenant_id to payload for tenant isolation
        $payload['tenant_id'] = $tenant->getId()->toRfc4122();

        // Add user ID
        $payload['user_id'] = $userId->toRfc4122();

        // Add full name for convenience
        $payload['full_name'] = $user->getFullName();

        // Add isActive status
        $payload['is_active'] = $user->isActive();

        $event->setData($payload);
    }
}
