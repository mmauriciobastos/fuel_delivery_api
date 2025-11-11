<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\TenantStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private Tenant $tenant;

    private User $adminUser;

    private User $regularUser;

    private string $testPassword = 'Test123!@#';

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Create test tenant
        $this->tenant = $this->createTenant('test-tenant', 'Test Tenant');

        // Create admin user
        $this->adminUser = $this->createUser(
            $this->tenant,
            'admin@test.com',
            'Admin',
            'User',
            ['ROLE_ADMIN']
        );

        // Create regular user
        $this->regularUser = $this->createUser(
            $this->tenant,
            'user@test.com',
            'Regular',
            'User',
            ['ROLE_USER']
        );
    }

    protected function tearDown(): void
    {
        // Clean up
        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }

        $tenants = $this->entityManager->getRepository(Tenant::class)->findAll();
        foreach ($tenants as $tenant) {
            $this->entityManager->remove($tenant);
        }

        $this->entityManager->flush();
        $this->entityManager->close();
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    public function testGetProfileAsAuthenticatedUser(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'GET',
            '/api/profile',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $response = $this->client->getResponse();
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            dump($response->getContent());
        }
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('user@test.com', $responseData['email']);
        $this->assertEquals('Regular', $responseData['firstName']);
        $this->assertEquals('User', $responseData['lastName']);
    }

    public function testGetProfileWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/profile');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateProfileAsAuthenticatedUser(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'PATCH',
            '/api/profile',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated', $response['firstName']);
        $this->assertEquals('Name', $response['lastName']);
    }

    public function testChangeOwnPassword(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->regularUser->getId() . '/change-password',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'currentPassword' => $this->testPassword,
                'newPassword' => 'NewPassword123!@#',
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Password changed successfully', $response['message']);

        // Verify new password works
        $newToken = $this->getAuthToken('user@test.com', 'NewPassword123!@#');
        $this->assertNotEmpty($newToken);
    }

    public function testChangePasswordWithWrongCurrentPassword(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->regularUser->getId() . '/change-password',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'currentPassword' => 'wrongpassword',
                'newPassword' => 'NewPassword123!@#',
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Current password is incorrect', $response['error']);
    }

    public function testAdminCanChangeOtherUserPassword(): void
    {
        $token = $this->getAuthToken('admin@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->regularUser->getId() . '/change-password',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'newPassword' => 'AdminSetPassword123!@#',
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Verify new password works
        $newToken = $this->getAuthToken('user@test.com', 'AdminSetPassword123!@#');
        $this->assertNotEmpty($newToken);
    }

    public function testUserCannotChangeOtherUserPassword(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->adminUser->getId() . '/change-password',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'currentPassword' => $this->testPassword,
                'newPassword' => 'NewPassword123!@#',
            ])
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testChangePasswordWithWeakPassword(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->regularUser->getId() . '/change-password',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'currentPassword' => $this->testPassword,
                'newPassword' => 'weak',
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('at least 8 characters', $response['error']);
    }

    public function testAdminCanDeactivateUser(): void
    {
        $token = $this->getAuthToken('admin@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->regularUser->getId() . '/deactivate',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User deactivated successfully', $response['message']);

        // Verify user is deactivated - fetch fresh from DB
        $updatedUser = $this->entityManager->getRepository(User::class)->find($this->regularUser->getId());
        $this->assertFalse($updatedUser->isActive());
    }

    public function testAdminCannotDeactivateThemselves(): void
    {
        $token = $this->getAuthToken('admin@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->adminUser->getId() . '/deactivate',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('cannot deactivate your own account', $response['error']);
    }

    public function testUserCannotDeactivateOtherUser(): void
    {
        $token = $this->getAuthToken('user@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->adminUser->getId() . '/deactivate',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanActivateUser(): void
    {
        // First deactivate
        $this->regularUser->deactivate();
        $this->entityManager->flush();

        $token = $this->getAuthToken('admin@test.com', $this->testPassword);

        $this->client->request(
            'POST',
            '/api/users/' . $this->regularUser->getId() . '/activate',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User activated successfully', $response['message']);

        // Verify user is activated - fetch fresh from DB
        $updatedUser = $this->entityManager->getRepository(User::class)->find($this->regularUser->getId());
        $this->assertTrue($updatedUser->isActive());
    }

    private function createTenant(string $subdomain, string $name): Tenant
    {
        $tenant = new Tenant();
        $tenant->setSubdomain($subdomain);
        $tenant->setName($name);
        $tenant->setStatus(TenantStatus::ACTIVE);

        $this->entityManager->persist($tenant);
        $this->entityManager->flush();

        return $tenant;
    }

    private function createUser(
        Tenant $tenant,
        string $email,
        string $firstName,
        string $lastName,
        array $roles
    ): User {
        $user = new User();
        $user->setTenant($tenant);
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $this->testPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getAuthToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        return $response['access_token'];
    }
}
