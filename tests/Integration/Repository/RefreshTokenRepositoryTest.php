<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\RefreshToken;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RefreshTokenRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private RefreshTokenRepository $repository;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(RefreshTokenRepository::class);

        // Create test tenant
        $this->tenant = new Tenant();
        $this->tenant->setName('Test Tenant');
        $this->tenant->setSubdomain('test-tenant-' . bin2hex(random_bytes(8)));
        $this->entityManager->persist($this->tenant);

        // Create test user
        $this->user = new User();
        $this->user->setEmail('refresh-test-' . bin2hex(random_bytes(8)) . '@example.com');
        $this->user->setPassword('hashed_password');
        $this->user->setFirstName('Test');
        $this->user->setLastName('User');
        $this->user->setTenant($this->tenant);
        $this->entityManager->persist($this->user);

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->user && $this->user->getId()) {
            $user = $this->entityManager->find(User::class, $this->user->getId());
            if ($user) {
                $tokens = $this->repository->findByUser($user);
                foreach ($tokens as $token) {
                    $this->entityManager->remove($token);
                }
                $this->entityManager->remove($user);
            }
        }

        if ($this->tenant && $this->tenant->getId()) {
            $tenant = $this->entityManager->find(Tenant::class, $this->tenant->getId());
            if ($tenant) {
                $this->entityManager->remove($tenant);
            }
        }

        $this->entityManager->flush();

        parent::tearDown();
    }

    public function testSaveRefreshToken(): void
    {
        $token = new RefreshToken();
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setUser($this->user);
        $token->onPrePersist();

        $this->repository->save($token);

        $this->assertNotNull($token->getId());

        $foundToken = $this->repository->find($token->getId());
        $this->assertNotNull($foundToken);
        $this->assertEquals($token->getToken(), $foundToken->getToken());
    }

    public function testFindValidToken(): void
    {
        $tokenString = bin2hex(random_bytes(32));

        $token = new RefreshToken();
        $token->setToken($tokenString);
        $token->setUser($this->user);
        $token->setValidUntil(new \DateTimeImmutable('+7 days'));
        $token->onPrePersist();

        $this->repository->save($token);

        $foundToken = $this->repository->findValidToken($tokenString);

        $this->assertNotNull($foundToken);
        $this->assertEquals($tokenString, $foundToken->getToken());
        $this->assertTrue($foundToken->isValid());
    }

    public function testFindValidTokenWithExpiredToken(): void
    {
        $tokenString = bin2hex(random_bytes(32));

        $token = new RefreshToken();
        $token->setToken($tokenString);
        $token->setUser($this->user);
        $token->setValidUntil(new \DateTimeImmutable('-1 day'));
        $token->onPrePersist();

        $this->repository->save($token);

        $foundToken = $this->repository->findValidToken($tokenString);

        $this->assertNull($foundToken);
    }

    public function testFindValidTokenWithRevokedToken(): void
    {
        $tokenString = bin2hex(random_bytes(32));

        $token = new RefreshToken();
        $token->setToken($tokenString);
        $token->setUser($this->user);
        $token->setValidUntil(new \DateTimeImmutable('+7 days'));
        $token->revoke();
        $token->onPrePersist();

        $this->repository->save($token);

        $foundToken = $this->repository->findValidToken($tokenString);

        $this->assertNull($foundToken);
    }

    public function testFindByUser(): void
    {
        // Create multiple tokens for the user
        for ($i = 0; $i < 3; ++$i) {
            $token = new RefreshToken();
            $token->setToken(bin2hex(random_bytes(32)));
            $token->setUser($this->user);
            $token->onPrePersist();
            $this->repository->save($token, false);
        }
        $this->entityManager->flush();

        $tokens = $this->repository->findByUser($this->user);

        $this->assertCount(3, $tokens);
        foreach ($tokens as $token) {
            $this->assertEquals($this->user->getId(), $token->getUser()->getId());
        }
    }

    public function testRevokeAllForUser(): void
    {
        // Create multiple tokens for the user
        for ($i = 0; $i < 3; ++$i) {
            $token = new RefreshToken();
            $token->setToken(bin2hex(random_bytes(32)));
            $token->setUser($this->user);
            $token->onPrePersist();
            $this->repository->save($token, false);
        }
        $this->entityManager->flush();

        $revokedCount = $this->repository->revokeAllForUser($this->user);

        $this->assertEquals(3, $revokedCount);

        // Clear entity manager to get fresh data from database
        $this->entityManager->clear();

        // Refresh user reference
        $this->user = $this->entityManager->find(User::class, $this->user->getId());

        $tokens = $this->repository->findByUser($this->user);
        foreach ($tokens as $token) {
            $this->assertTrue($token->isRevoked());
        }
    }

    public function testDeleteExpired(): void
    {
        // Create expired token
        $expiredToken = new RefreshToken();
        $expiredToken->setToken(bin2hex(random_bytes(32)));
        $expiredToken->setUser($this->user);
        $expiredToken->setValidUntil(new \DateTimeImmutable('-1 day'));
        $expiredToken->onPrePersist();
        $this->repository->save($expiredToken, false);

        // Create valid token
        $validToken = new RefreshToken();
        $validToken->setToken(bin2hex(random_bytes(32)));
        $validToken->setUser($this->user);
        $validToken->setValidUntil(new \DateTimeImmutable('+7 days'));
        $validToken->onPrePersist();
        $this->repository->save($validToken, false);

        $this->entityManager->flush();

        $deletedCount = $this->repository->deleteExpired();

        $this->assertEquals(1, $deletedCount);

        // Verify only valid token remains
        $tokens = $this->repository->findByUser($this->user);
        $this->assertCount(1, $tokens);
        $this->assertTrue($tokens[0]->isValid());
    }

    public function testRemoveRefreshToken(): void
    {
        $token = new RefreshToken();
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setUser($this->user);
        $token->onPrePersist();

        $this->repository->save($token);
        $tokenId = $token->getId();

        $this->repository->remove($token);

        $foundToken = $this->repository->find($tokenId);
        $this->assertNull($foundToken);
    }
}
