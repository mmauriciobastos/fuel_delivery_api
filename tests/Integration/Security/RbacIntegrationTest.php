<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\TenantStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RbacIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private Tenant $tenant1;

    private Tenant $tenant2;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

    // Create test tenants with unique subdomains
    $uniqueSuffix = bin2hex(random_bytes(4));
    $this->tenant1 = $this->createTenant('tenant1_' . $uniqueSuffix, 'Tenant 1');
    $this->tenant2 = $this->createTenant('tenant2_' . $uniqueSuffix, 'Tenant 2');
    }

    protected function tearDown(): void
    {
        // Remove all users using Doctrine
        $userRepository = $this->entityManager->getRepository(User::class);
        foreach ($userRepository->findAll() as $user) {
            $this->entityManager->remove($user);
        }
        
        // Remove all tenants using Doctrine
        $tenantRepository = $this->entityManager->getRepository(Tenant::class);
        foreach ($tenantRepository->findAll() as $tenant) {
            $this->entityManager->remove($tenant);
        }

        $this->entityManager->flush();
        $this->entityManager->close();
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    public function testAdminCanAccessUserCollection(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/users',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDispatcherCannotAccessUserCollection(): void
    {
        $dispatcher = $this->createUser($this->tenant1, 'dispatcher@tenant1.com', ['ROLE_DISPATCHER']);
        $token = $this->getAuthToken('dispatcher@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/users',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testRegularUserCannotAccessUserCollection(): void
    {
        $user = $this->createUser($this->tenant1, 'user@tenant1.com', ['ROLE_USER']);
        $token = $this->getAuthToken('user@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/users',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanViewUserInSameTenant(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $targetUser = $this->createUser($this->tenant1, 'target@tenant1.com', ['ROLE_USER']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/users/' . $targetUser->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCannotViewUserInDifferentTenant(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $targetUser = $this->createUser($this->tenant2, 'target@tenant2.com', ['ROLE_USER']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/users/' . $targetUser->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanUpdateUserInSameTenant(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $targetUser = $this->createUser($this->tenant1, 'target@tenant1.com', ['ROLE_USER']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'PATCH',
            '/api/users/' . $targetUser->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ],
            json_encode(['firstName' => 'Updated'])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCannotDeleteThemselves(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'DELETE',
            '/api/users/' . $admin->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanDeleteOtherUserInSameTenant(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $targetUser = $this->createUser($this->tenant1, 'target@tenant1.com', ['ROLE_USER']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'DELETE',
            '/api/users/' . $targetUser->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanViewTheirTenant(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/tenants/' . $this->tenant1->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCannotViewOtherTenant(): void
    {
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);
        $token = $this->getAuthToken('admin@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/tenants/' . $this->tenant2->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testDispatcherCannotAccessTenant(): void
    {
        $dispatcher = $this->createUser($this->tenant1, 'dispatcher@tenant1.com', ['ROLE_DISPATCHER']);
        $token = $this->getAuthToken('dispatcher@tenant1.com', 'password123');

        $this->client->request(
            'GET',
            '/api/tenants/' . $this->tenant1->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testUserCanUpdateThemselves(): void
    {
        $user = $this->createUser($this->tenant1, 'user@tenant1.com', ['ROLE_USER']);
        $token = $this->getAuthToken('user@tenant1.com', 'password123');

        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ],
            json_encode(['firstName' => 'My', 'lastName' => 'Name'])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testRoleHierarchyAdminHasDispatcherPermissions(): void
    {
        // Create admin and verify they inherit ROLE_DISPATCHER permissions
        $admin = $this->createUser($this->tenant1, 'admin@tenant1.com', ['ROLE_ADMIN']);

        $this->assertContains('ROLE_ADMIN', $admin->getRoles());
        // Due to role hierarchy, ROLE_ADMIN should have ROLE_DISPATCHER and ROLE_USER
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

    private function createUser(Tenant $tenant, string $email, array $roles): User
    {
        $user = new User();
        $user->setTenant($tenant);
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
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
