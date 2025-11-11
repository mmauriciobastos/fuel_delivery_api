<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserRepository $userRepository;

    private RefreshTokenRepository $refreshTokenRepository;

    private Tenant $tenant;

    private User $testUser;

    private string $testPassword = 'Test123!@#';

    protected function setUp(): void
    {
        // Ensure kernel is shutdown before creating new client
        static::ensureKernelShutdown();

        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->refreshTokenRepository = $container->get(RefreshTokenRepository::class);

        // Create test tenant
        $this->tenant = new Tenant();
        $this->tenant->setName('Test Tenant');
        $this->tenant->setSubdomain('test-auth-' . bin2hex(random_bytes(4)));
        $this->entityManager->persist($this->tenant);

        // Create test user
        $this->testUser = new User();
        $this->testUser->setEmail('auth-test-' . bin2hex(random_bytes(4)) . '@example.com');
        $this->testUser->setFirstName('Test');
        $this->testUser->setLastName('User');
        $this->testUser->setTenant($this->tenant);
        $this->testUser->setRoles(['ROLE_USER']);

        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->testUser, $this->testPassword);
        $this->testUser->setPassword($hashedPassword);

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        if ($this->testUser && $this->testUser->getId()) {
            $user = $this->userRepository->find($this->testUser->getId());
            if ($user) {
                $tokens = $this->refreshTokenRepository->findByUser($user);
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

        // Ensure kernel is shutdown to allow next test to boot cleanly
        static::ensureKernelShutdown();

        parent::tearDown();
    }

    public function testLoginSuccess(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->testUser->getEmail(),
                'password' => $this->testPassword,
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);
        $this->assertArrayHasKey('token_type', $responseData);
        $this->assertArrayHasKey('expires_in', $responseData);
        $this->assertArrayHasKey('user', $responseData);

        $this->assertEquals('Bearer', $responseData['token_type']);
        $this->assertEquals(900, $responseData['expires_in']);

        $this->assertArrayHasKey('id', $responseData['user']);
        $this->assertArrayHasKey('email', $responseData['user']);
        $this->assertArrayHasKey('first_name', $responseData['user']);
        $this->assertArrayHasKey('last_name', $responseData['user']);
        $this->assertArrayHasKey('roles', $responseData['user']);
        $this->assertArrayHasKey('tenant_id', $responseData['user']);

        $this->assertEquals($this->testUser->getEmail(), $responseData['user']['email']);
        $this->assertEquals($this->testUser->getFirstName(), $responseData['user']['first_name']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->testUser->getEmail(),
                'password' => 'wrong_password',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => $this->testPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshTokenSuccess(): void
    {
        // First, login to get tokens
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->testUser->getEmail(),
                'password' => $this->testPassword,
            ])
        );

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $loginResponse['refresh_token'];

        // Now refresh the token
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);
        $this->assertArrayHasKey('token_type', $responseData);
        $this->assertArrayHasKey('expires_in', $responseData);

        // Verify new refresh token is different
        $this->assertNotEquals($refreshToken, $responseData['refresh_token']);
    }

    public function testRefreshTokenWithInvalidToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => 'invalid_token_string',
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testRefreshTokenWithoutToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Refresh token is required', $responseData['error']);
    }

    public function testLogoutSuccess(): void
    {
        // First, login to get tokens
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->testUser->getEmail(),
                'password' => $this->testPassword,
            ])
        );

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $accessToken = $loginResponse['access_token'];
        $refreshToken = $loginResponse['refresh_token'];

        // Now logout
        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
            ],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Successfully logged out', $responseData['message']);
    }

    public function testLogoutWithoutAuthentication(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshTokenCannotBeReused(): void
    {
        // Login to get tokens
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->testUser->getEmail(),
                'password' => $this->testPassword,
            ])
        );

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $loginResponse['refresh_token'];

        // Use refresh token first time - should succeed
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseIsSuccessful();

        // Try to use same refresh token again - should fail
        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'refresh_token' => $refreshToken,
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
