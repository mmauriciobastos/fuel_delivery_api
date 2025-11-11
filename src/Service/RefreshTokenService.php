<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;

/**
 * Refresh Token Service.
 *
 * Manages refresh token creation, validation, and revocation
 * for JWT authentication workflow.
 */
readonly class RefreshTokenService
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    /**
     * Generate a new refresh token for a user.
     */
    public function createRefreshToken(User $user): RefreshToken
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setToken($this->generateTokenString());
        $refreshToken->setUser($user);
        $refreshToken->setValidUntil((new \DateTimeImmutable())->modify('+7 days'));
        $refreshToken->onPrePersist();

        $this->refreshTokenRepository->save($refreshToken);

        return $refreshToken;
    }

    /**
     * Validate and retrieve a refresh token.
     */
    public function getValidRefreshToken(string $tokenString): ?RefreshToken
    {
        return $this->refreshTokenRepository->findValidToken($tokenString);
    }

    /**
     * Revoke a specific refresh token.
     */
    public function revokeRefreshToken(RefreshToken $refreshToken): void
    {
        $refreshToken->revoke();
        $this->refreshTokenRepository->save($refreshToken);
    }

    /**
     * Revoke all refresh tokens for a user.
     */
    public function revokeAllUserTokens(User $user): int
    {
        return $this->refreshTokenRepository->revokeAllForUser($user);
    }

    /**
     * Generate a cryptographically secure token string.
     */
    private function generateTokenString(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Clean up expired refresh tokens.
     */
    public function deleteExpiredTokens(): int
    {
        return $this->refreshTokenRepository->deleteExpired();
    }
}
