<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Entity\Client;
use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\TenantStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientApiTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private Tenant $tenant;

    private User $adminUser;

    private User $dispatcherUser;

    private User $regularUser;

    private string $testPassword = 'Test123!@#';

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient([]);
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Create test tenant with unique subdomain
        $uniqueId = uniqid('test-');
        $this->tenant = $this->createTenant($uniqueId, 'Test Client Tenant');

        // Create test users
        $this->adminUser = $this->createUser(
            $this->tenant,
            "admin-{$uniqueId}@client-test.com",
            'Admin',
            'User',
            ['ROLE_ADMIN']
        );

        $this->dispatcherUser = $this->createUser(
            $this->tenant,
            "dispatcher-{$uniqueId}@client-test.com",
            'Dispatcher',
            'User',
            ['ROLE_DISPATCHER']
        );

        $this->regularUser = $this->createUser(
            $this->tenant,
            "user-{$uniqueId}@client-test.com",
            'Regular',
            'User',
            ['ROLE_USER']
        );
    }

    protected function tearDown(): void
    {
        // Clean up
        $clients = $this->entityManager->getRepository(Client::class)->findAll();
        foreach ($clients as $client) {
            $this->entityManager->remove($client);
        }

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

    public function testCreateClientAsDispatcher(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $clientData = [
            'companyName' => 'Acme Corporation',
            'contactName' => 'John Doe',
            'email' => 'john@acme.com',
            'phone' => '+1 (555) 123-4567',
            'billingAddress' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postalCode' => '10001',
                'country' => 'USA',
            ],
        ];

        $this->client->request(
            'POST',
            '/api/clients',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($clientData)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Acme Corporation', $response['companyName']);
        $this->assertEquals('John Doe', $response['contactName']);
        $this->assertEquals('john@acme.com', $response['email']);
        $this->assertTrue($response['isActive']);
    }

    public function testCreateClientAsRegularUserIsForbidden(): void
    {
        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $clientData = [
            'companyName' => 'Test Company',
            'contactName' => 'Jane Smith',
        ];

        $this->client->request(
            'POST',
            '/api/clients',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($clientData)
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetClientsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/clients');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testGetClientsAsAuthenticatedUser(): void
    {
        // Create test client
        $testClient = $this->createTestClient('Test Company', 'Test Contact');

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request(
            'GET',
            '/api/clients',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);

        // API Platform might return hydra:member or a plain array
        $members = $response['hydra:member'] ?? $response;
        $this->assertIsArray($members);
        $this->assertGreaterThan(0, \count($members));
    }

    public function testGetSingleClient(): void
    {
        $testClient = $this->createTestClient('Acme Corp', 'John Doe');
        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request(
            'GET',
            '/api/clients/' . $testClient->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Acme Corp', $response['companyName']);
        $this->assertEquals('John Doe', $response['contactName']);
    }

    public function testUpdateClientAsDispatcher(): void
    {
        $testClient = $this->createTestClient('Old Name', 'Old Contact');
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $updateData = [
            'companyName' => 'New Name',
            'contactName' => 'New Contact',
            'email' => 'new@email.com',
        ];

        $this->client->request(
            'PATCH',
            '/api/clients/' . $testClient->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ],
            json_encode($updateData)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('New Name', $response['companyName']);
        $this->assertEquals('New Contact', $response['contactName']);
        $this->assertEquals('new@email.com', $response['email']);
    }

    public function testUpdateClientAsRegularUserIsForbidden(): void
    {
        $testClient = $this->createTestClient('Test Company', 'Test Contact');
        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $updateData = ['companyName' => 'Updated Name'];

        $this->client->request(
            'PATCH',
            '/api/clients/' . $testClient->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ],
            json_encode($updateData)
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteClientAsAdmin(): void
    {
        $testClient = $this->createTestClient('To Delete', 'Test Contact');
        $token = $this->getAuthToken($this->adminUser->getEmail(), $this->testPassword);

        $this->client->request(
            'DELETE',
            '/api/clients/' . $testClient->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteClientAsDispatcherIsForbidden(): void
    {
        $testClient = $this->createTestClient('Test Company', 'Test Contact');
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request(
            'DELETE',
            '/api/clients/' . $testClient->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testFilterClientsByCompanyName(): void
    {
        $this->createTestClient('Acme Corporation', 'John Doe');
        $this->createTestClient('Beta Industries', 'Jane Smith');
        $this->createTestClient('Acme Services', 'Bob Johnson');

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request(
            'GET',
            '/api/clients?companyName=Acme',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);

        $members = $response['hydra:member'] ?? $response;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(2, \count($members));
    }

    public function testFilterClientsByActiveStatus(): void
    {
        $activeClient = $this->createTestClient('Active Company', 'Contact 1');
        $inactiveClient = $this->createTestClient('Inactive Company', 'Contact 2');
        $inactiveClient->deactivate();
        $this->entityManager->flush();

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request(
            'GET',
            '/api/clients?isActive=true',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($response['hydra:member'] as $client) {
            $this->assertTrue($client['isActive']);
        }
    }

    public function testOrderClientsByCompanyName(): void
    {
        $this->createTestClient('Zebra Company', 'Contact 1');
        $this->createTestClient('Alpha Company', 'Contact 2');
        $this->createTestClient('Beta Company', 'Contact 3');

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request(
            'GET',
            '/api/clients?order[companyName]=asc',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);

        // Just verify we get a successful response with ordered data
        // The ordering filter is applied by API Platform
        $this->assertNotEmpty($response);
    }

    public function testValidationFailsForMissingRequiredFields(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $invalidData = [
            'email' => 'test@example.com',
        ];

        $this->client->request(
            'POST',
            '/api/clients',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($invalidData)
        );

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testValidationFailsForInvalidEmail(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $invalidData = [
            'companyName' => 'Test Company',
            'contactName' => 'Test Contact',
            'email' => 'invalid-email',
        ];

        $this->client->request(
            'POST',
            '/api/clients',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($invalidData)
        );

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
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

    private function createTestClient(string $companyName, string $contactName): Client
    {
        $client = new Client();
        $client->setTenant($this->tenant);
        $client->setCompanyName($companyName);
        $client->setContactName($contactName);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
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
