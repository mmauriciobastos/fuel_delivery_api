<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\RefreshToken;
use App\Entity\Tenant;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class RefreshTokenTest extends TestCase
{
    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->tenant = new Tenant();
        $this->tenant->setName('Test Tenant');
        $this->tenant->setSubdomain('test-tenant');

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('hashed_password');
        $this->user->setFirstName('John');
        $this->user->setLastName('Doe');
        $this->user->setTenant($this->tenant);
    }

    public function testRefreshTokenCreation(): void
    {
        $token = new RefreshToken();

        $this->assertInstanceOf(Uuid::class, $token->getId());
        $this->assertNull($token->getToken());
        $this->assertNull($token->getUser());
        $this->assertFalse($token->isRevoked());
    }

    public function testSetToken(): void
    {
        $token = new RefreshToken();
        $tokenString = bin2hex(random_bytes(32));

        $token->setToken($tokenString);

        $this->assertEquals($tokenString, $token->getToken());
    }

    public function testSetUser(): void
    {
        $token = new RefreshToken();

        $token->setUser($this->user);

        $this->assertSame($this->user, $token->getUser());
    }

    public function testSetValidUntil(): void
    {
        $token = new RefreshToken();
        $validUntil = new \DateTimeImmutable('+7 days');

        $token->setValidUntil($validUntil);

        $this->assertEquals($validUntil, $token->getValidUntil());
    }

    public function testIsValidWithValidToken(): void
    {
        $token = new RefreshToken();
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setUser($this->user);
        $token->setValidUntil(new \DateTimeImmutable('+7 days'));

        $this->assertTrue($token->isValid());
    }

    public function testIsValidWithExpiredToken(): void
    {
        $token = new RefreshToken();
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setUser($this->user);
        $token->setValidUntil(new \DateTimeImmutable('-1 day'));

        $this->assertFalse($token->isValid());
    }

    public function testIsValidWithRevokedToken(): void
    {
        $token = new RefreshToken();
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setUser($this->user);
        $token->setValidUntil(new \DateTimeImmutable('+7 days'));
        $token->revoke();

        $this->assertFalse($token->isValid());
    }

    public function testRevoke(): void
    {
        $token = new RefreshToken();

        $this->assertFalse($token->isRevoked());

        $token->revoke();

        $this->assertTrue($token->isRevoked());
    }

    public function testLifecycleCallbacks(): void
    {
        $token = new RefreshToken();

        // Simulate PrePersist event
        $token->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getValidUntil());

        // Verify valid until is approximately 7 days from now
        $expectedValidUntil = (new \DateTimeImmutable())->modify('+7 days');
        $this->assertEqualsWithDelta(
            $expectedValidUntil->getTimestamp(),
            $token->getValidUntil()->getTimestamp(),
            60 // Allow 60 seconds difference
        );
    }
}
