<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\JwtTokenBlacklistService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class JwtTokenBlacklistServiceTest extends TestCase
{
    private JwtTokenBlacklistService $blacklistService;

    protected function setUp(): void
    {
        $cache = new ArrayAdapter();
        $this->blacklistService = new JwtTokenBlacklistService($cache);
    }

    public function testBlacklistToken(): void
    {
        $tokenId = bin2hex(random_bytes(16));
        $expiresAt = time() + 900; // 15 minutes

        $this->blacklistService->blacklist($tokenId, $expiresAt);

        $this->assertTrue($this->blacklistService->isBlacklisted($tokenId));
    }

    public function testTokenNotBlacklisted(): void
    {
        $tokenId = bin2hex(random_bytes(16));

        $this->assertFalse($this->blacklistService->isBlacklisted($tokenId));
    }

    public function testBlacklistExpiredToken(): void
    {
        $tokenId = bin2hex(random_bytes(16));
        $expiresAt = time() - 100; // Already expired

        $this->blacklistService->blacklist($tokenId, $expiresAt);

        // Should not be blacklisted since it's already expired
        $this->assertFalse($this->blacklistService->isBlacklisted($tokenId));
    }

    public function testRemoveFromBlacklist(): void
    {
        $tokenId = bin2hex(random_bytes(16));
        $expiresAt = time() + 900;

        $this->blacklistService->blacklist($tokenId, $expiresAt);
        $this->assertTrue($this->blacklistService->isBlacklisted($tokenId));

        $this->blacklistService->remove($tokenId);
        $this->assertFalse($this->blacklistService->isBlacklisted($tokenId));
    }

    public function testMultipleTokens(): void
    {
        $tokenId1 = bin2hex(random_bytes(16));
        $tokenId2 = bin2hex(random_bytes(16));
        $expiresAt = time() + 900;

        $this->blacklistService->blacklist($tokenId1, $expiresAt);
        $this->blacklistService->blacklist($tokenId2, $expiresAt);

        $this->assertTrue($this->blacklistService->isBlacklisted($tokenId1));
        $this->assertTrue($this->blacklistService->isBlacklisted($tokenId2));

        $this->blacklistService->remove($tokenId1);

        $this->assertFalse($this->blacklistService->isBlacklisted($tokenId1));
        $this->assertTrue($this->blacklistService->isBlacklisted($tokenId2));
    }
}
