<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\TenantStatus;
use App\Repository\UserRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Integration tests for UserRepository to verify tenant isolation and database operations.
 */
class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private TenantContext $tenantContext;

    private UserRepository $userRepository;

    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->tenantContext = $container->get(TenantContext::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Enable tenant filter
        $this->entityManager->getFilters()->enable('tenant_filter');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up tenant context
        $this->tenantContext->clear();

        // Disable the filter
        $filters = $this->entityManager->getFilters();
        if ($filters->isEnabled('tenant_filter')) {
            $filters->disable('tenant_filter');
        }

        $this->entityManager->close();
    }

    public function testCreateUserWithTenant(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-tenant');
        $this->tenantContext->setCurrentTenant($tenant);

        $user = new User();
        $user->setTenant($tenant);
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setRoles(['ROLE_USER']);

        $this->userRepository->save($user);

        $this->assertNotNull($user->getId());
        $this->assertEquals($tenant, $user->getTenant());

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    public function testFindByEmailWithinTenant(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-tenant-find');
        $this->tenantContext->setCurrentTenant($tenant);
        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant->getId()->toRfc4122());

        $user = $this->createUser($tenant, 'user@example.com', 'John', 'Doe');

        $foundUser = $this->userRepository->findByEmail('user@example.com');

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getId(), $foundUser->getId());
        $this->assertEquals('user@example.com', $foundUser->getEmail());

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    public function testEmailUniqueConstraintWithinTenant(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-unique-email');
        $this->tenantContext->setCurrentTenant($tenant);
        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant->getId()->toRfc4122());

        $user1 = $this->createUser($tenant, 'duplicate@example.com', 'User', 'One');

        $this->assertTrue($this->userRepository->emailExists('duplicate@example.com'));

        // Clean up
        $this->entityManager->remove($user1);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    public function testEmailCanBeUsedInDifferentTenants(): void
    {
        $tenant1 = $this->createTenant('Tenant 1', 'tenant-1-email');
        $tenant2 = $this->createTenant('Tenant 2', 'tenant-2-email');

        $user1 = $this->createUser($tenant1, 'same@example.com', 'User', 'One');
        $user2 = $this->createUser($tenant2, 'same@example.com', 'User', 'Two');

        $this->assertNotEquals($user1->getId(), $user2->getId());
        $this->assertEquals($user1->getEmail(), $user2->getEmail());
        $this->assertNotEquals($user1->getTenant()->getId(), $user2->getTenant()->getId());

        // Clean up
        $this->entityManager->remove($user1);
        $this->entityManager->remove($user2);
        $this->entityManager->remove($tenant1);
        $this->entityManager->remove($tenant2);
        $this->entityManager->flush();
    }

    public function testTenantIsolationInQueries(): void
    {
        $tenant1 = $this->createTenant('Tenant 1', 'tenant-1-isolation');
        $tenant2 = $this->createTenant('Tenant 2', 'tenant-2-isolation');

        $user1 = $this->createUser($tenant1, 'user1@example.com', 'User', 'One');
        $user2 = $this->createUser($tenant2, 'user2@example.com', 'User', 'Two');

        // Set tenant 1 context
        $this->tenantContext->setCurrentTenant($tenant1);
        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant1->getId()->toRfc4122());

        $users = $this->userRepository->findAll();

        $this->assertCount(1, $users);
        $this->assertEquals($user1->getId(), $users[0]->getId());

        // Set tenant 2 context
        $this->tenantContext->setCurrentTenant($tenant2);
        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant2->getId()->toRfc4122());

        $users = $this->userRepository->findAll();

        $this->assertCount(1, $users);
        $this->assertEquals($user2->getId(), $users[0]->getId());

        // Clean up
        $this->tenantContext->clear();
        $this->entityManager->getFilters()->disable('tenant_filter');
        $this->entityManager->remove($user1);
        $this->entityManager->remove($user2);
        $this->entityManager->remove($tenant1);
        $this->entityManager->remove($tenant2);
        $this->entityManager->flush();
    }

    public function testFindActiveUsers(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-active-users');
        $this->tenantContext->setCurrentTenant($tenant);

        $activeUser = $this->createUser($tenant, 'active@example.com', 'Active', 'User');
        $inactiveUser = $this->createUser($tenant, 'inactive@example.com', 'Inactive', 'User');
        $inactiveUser->setIsActive(false);
        $this->entityManager->flush();

        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant->getId()->toRfc4122());

        $activeUsers = $this->userRepository->findActiveUsers();

        $this->assertCount(1, $activeUsers);
        $this->assertEquals($activeUser->getId(), $activeUsers[0]->getId());

        // Clean up
        $this->entityManager->remove($activeUser);
        $this->entityManager->remove($inactiveUser);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    public function testFindByRole(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-find-role');
        $this->tenantContext->setCurrentTenant($tenant);

        $admin = $this->createUser($tenant, 'admin@example.com', 'Admin', 'User');
        $admin->setRoles(['ROLE_ADMIN']);

        $dispatcher = $this->createUser($tenant, 'dispatcher@example.com', 'Dispatcher', 'User');
        $dispatcher->setRoles(['ROLE_DISPATCHER']);

        $this->entityManager->flush();

        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant->getId()->toRfc4122());

        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        $this->assertCount(1, $admins);
        $this->assertEquals($admin->getId(), $admins[0]->getId());

        // Clean up
        $this->entityManager->remove($admin);
        $this->entityManager->remove($dispatcher);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    public function testPasswordHashing(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-password');
        $this->tenantContext->setCurrentTenant($tenant);

        $user = new User();
        $user->setTenant($tenant);
        $user->setEmail('password@example.com');
        $user->setFirstName('Password');
        $user->setLastName('Test');

        $plainPassword = 'mySecretPassword123';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        $this->assertNotEquals($plainPassword, $user->getPassword());
        $this->assertTrue($this->passwordHasher->isPasswordValid($user, $plainPassword));
        $this->assertFalse($this->passwordHasher->isPasswordValid($user, 'wrongPassword'));

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    public function testSearchUsers(): void
    {
        $tenant = $this->createTenant('Test Tenant', 'test-search');
        $this->tenantContext->setCurrentTenant($tenant);

        $user1 = $this->createUser($tenant, 'john.smith@example.com', 'John', 'Smith');
        $user2 = $this->createUser($tenant, 'jane.doe@example.com', 'Jane', 'Doe');

        $this->entityManager->getFilters()->getFilter('tenant_filter')->setParameter('tenant_id', $tenant->getId()->toRfc4122());

        $results = $this->userRepository->searchUsers('john');
        $this->assertCount(1, $results);
        $this->assertEquals($user1->getId(), $results[0]->getId());

        $results = $this->userRepository->searchUsers('doe');
        $this->assertCount(1, $results);
        $this->assertEquals($user2->getId(), $results[0]->getId());

        // Clean up
        $this->entityManager->remove($user1);
        $this->entityManager->remove($user2);
        $this->entityManager->remove($tenant);
        $this->entityManager->flush();
    }

    private function createTenant(string $name, string $subdomain): Tenant
    {
        $tenant = new Tenant();
        $tenant->setName($name);
        $tenant->setSubdomain($subdomain);
        $tenant->setStatus(TenantStatus::ACTIVE);

        $this->entityManager->persist($tenant);
        $this->entityManager->flush();

        return $tenant;
    }

    private function createUser(Tenant $tenant, string $email, string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setTenant($tenant);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
