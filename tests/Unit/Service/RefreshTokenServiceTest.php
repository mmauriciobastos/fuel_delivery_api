<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\RefreshToken;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Service\RefreshTokenService;
use PHPUnit\Framework\TestCase;

class RefreshTokenServiceTest extends TestCase
{
    private RefreshTokenRepository $repository;
    private RefreshTokenService $service;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RefreshTokenRepository::class);
        $this->service = new RefreshTokenService($this->repository);

        $tenant = new Tenant();
        $tenant->setName('Test Tenant');
        $tenant->setSubdomain('test');

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('hashed_password');
        $this->user->setFirstName('John');
        $this->user->setLastName('Doe');
        $this->user->setTenant($tenant);
    }

    public function testCreateRefreshToken(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RefreshToken $token) {
                return $token->getUser() === $this->user
                    && $token->getToken() !== null
                    && strlen($token->getToken()) === 128 // 64 bytes hex = 128 chars
                    && $token->getValidUntil() !== null;
            }));

        $refreshToken = $this->service->createRefreshToken($this->user);

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
        $this->assertSame($this->user, $refreshToken->getUser());
        $this->assertNotNull($refreshToken->getToken());
        $this->assertEquals(128, strlen($refreshToken->getToken()));
    }

    public function testGetValidRefreshToken(): void
    {
        $tokenString = 'valid_token_string';
        $expectedToken = new RefreshToken();

        $this->repository
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenString)
            ->willReturn($expectedToken);

        $result = $this->service->getValidRefreshToken($tokenString);

        $this->assertSame($expectedToken, $result);
    }

    public function testGetValidRefreshTokenReturnsNull(): void
    {
        $tokenString = 'invalid_token_string';

        $this->repository
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenString)
            ->willReturn(null);

        $result = $this->service->getValidRefreshToken($tokenString);

        $this->assertNull($result);
    }

    public function testRevokeRefreshToken(): void
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setToken('token_to_revoke');
        $refreshToken->setUser($this->user);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (RefreshToken $token) {
                return $token->isRevoked() === true;
            }));

        $this->service->revokeRefreshToken($refreshToken);

        $this->assertTrue($refreshToken->isRevoked());
    }

    public function testRevokeAllUserTokens(): void
    {
        $expectedCount = 3;

        $this->repository
            ->expects($this->once())
            ->method('revokeAllForUser')
            ->with($this->user)
            ->willReturn($expectedCount);

        $result = $this->service->revokeAllUserTokens($this->user);

        $this->assertEquals($expectedCount, $result);
    }

    public function testDeleteExpiredTokens(): void
    {
        $expectedCount = 5;

        $this->repository
            ->expects($this->once())
            ->method('deleteExpired')
            ->willReturn($expectedCount);

        $result = $this->service->deleteExpiredTokens();

        $this->assertEquals($expectedCount, $result);
    }
}
