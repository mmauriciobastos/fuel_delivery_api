<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\TenantContext;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to handle JWT authentication events.
 *
 * Sets the tenant context when a JWT token is successfully authenticated,
 * enabling tenant-scoped queries throughout the application.
 */
readonly class JWTAuthenticatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TenantContext $tenantContext
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_AUTHENTICATED => 'onJWTAuthenticated',
        ];
    }

    /**
     * Set tenant context after successful JWT authentication.
     */
    public function onJWTAuthenticated(JWTAuthenticatedEvent $event): void
    {
        $token = $event->getToken();
        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Set tenant context for this request
        $this->tenantContext->setCurrentTenant($user->getTenant());
    }
}
