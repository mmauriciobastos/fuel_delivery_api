<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * JWT Token Blacklist Service.
 *
 * Manages blacklisted JWT tokens using Redis for logout functionality.
 * Tokens are stored in the blacklist until they expire naturally.
 */
readonly class JwtTokenBlacklistService
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    /**
     * Add a JWT token to the blacklist.
     *
     * @param string $tokenId JWT token ID (jti claim)
     * @param int $expiresAt Token expiration timestamp
     */
    public function blacklist(string $tokenId, int $expiresAt): void
    {
        $ttl = $expiresAt - time();

        // Only store if token hasn't already expired
        if ($ttl > 0) {
            $this->cache->get(
                $this->getCacheKey($tokenId),
                function (ItemInterface $item) use ($ttl): bool {
                    $item->expiresAfter($ttl);

                    return true;
                }
            );
        }
    }

    /**
     * Check if a JWT token is blacklisted.
     *
     * @param string $tokenId JWT token ID (jti claim)
     */
    public function isBlacklisted(string $tokenId): bool
    {
        try {
            return $this->cache->get(
                $this->getCacheKey($tokenId),
                function (ItemInterface $item): bool {
                    return false;
                }
            );
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Remove a token from the blacklist (not typically used).
     *
     * @param string $tokenId JWT token ID (jti claim)
     */
    public function remove(string $tokenId): void
    {
        $this->cache->delete($this->getCacheKey($tokenId));
    }

    /**
     * Generate cache key for a token ID.
     */
    private function getCacheKey(string $tokenId): string
    {
        return 'jwt_blacklist_' . $tokenId;
    }
}
