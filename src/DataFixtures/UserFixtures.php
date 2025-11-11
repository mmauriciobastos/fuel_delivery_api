<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create users for each tenant
        $tenants = [
            TenantFixtures::TENANT_ACME_REFERENCE => 'acme',
            TenantFixtures::TENANT_GLOBAL_REFERENCE => 'global',
            TenantFixtures::TENANT_PREMIUM_REFERENCE => 'premium',
        ];

        foreach ($tenants as $tenantReference => $tenantPrefix) {
            /** @var Tenant $tenant */
            $tenant = $this->getReference($tenantReference, Tenant::class);

            // Admin user for each tenant
            $admin = new User();
            $admin->setEmail("{$tenantPrefix}.admin@example.com")
                ->setFirstName(ucfirst($tenantPrefix))
                ->setLastName('Admin')
                ->setRoles(['ROLE_ADMIN'])
                ->setTenant($tenant)
                ->activate();
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
            $manager->persist($admin);

            // Dispatcher user for each tenant
            $dispatcher = new User();
            $dispatcher->setEmail("{$tenantPrefix}.dispatcher@example.com")
                ->setFirstName(ucfirst($tenantPrefix))
                ->setLastName('Dispatcher')
                ->setRoles(['ROLE_DISPATCHER'])
                ->setTenant($tenant)
                ->activate();
            $dispatcher->setPassword($this->passwordHasher->hashPassword($dispatcher, 'dispatcher123'));
            $manager->persist($dispatcher);

            // Regular user for each tenant
            $user = new User();
            $user->setEmail("{$tenantPrefix}.user@example.com")
                ->setFirstName(ucfirst($tenantPrefix))
                ->setLastName('User')
                ->setRoles(['ROLE_USER'])
                ->setTenant($tenant)
                ->activate();
            $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TenantFixtures::class,
        ];
    }
}
