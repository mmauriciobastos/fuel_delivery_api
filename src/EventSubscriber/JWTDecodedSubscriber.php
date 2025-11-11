<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\JwtTokenBlacklistService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to check if JWT token is blacklisted.
 *
 * Validates JWT tokens against the blacklist before authentication,
 * preventing the use of logged-out tokens.
 */
readonly class JWTDecodedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JwtTokenBlacklistService $blacklistService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_DECODED => 'onJWTDecoded',
        ];
    }

    /**
     * Check if token is blacklisted before authentication.
     */
    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        // Check if token has a JTI (JWT ID) claim
        if (!isset($payload['jti'])) {
            return;
        }

        $tokenId = $payload['jti'];

        // Mark token as invalid if it's blacklisted
        if ($this->blacklistService->isBlacklisted($tokenId)) {
            $event->markAsInvalid();
        }
    }
}
