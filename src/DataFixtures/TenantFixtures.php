<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TenantFixtures extends Fixture
{
    public const TENANT_ACME_REFERENCE = 'tenant-acme';
    public const TENANT_GLOBAL_REFERENCE = 'tenant-global';
    public const TENANT_PREMIUM_REFERENCE = 'tenant-premium';

    public function load(ObjectManager $manager): void
    {
        // Tenant 1: Acme Fuel Company
        $acme = new Tenant();
        $acme->setName('Acme Fuel Company')
            ->setSubdomain('acme')
            ->activate();
        $manager->persist($acme);
        $this->addReference(self::TENANT_ACME_REFERENCE, $acme);

        // Tenant 2: Global Petro Distribution
        $global = new Tenant();
        $global->setName('Global Petro Distribution')
            ->setSubdomain('global')
            ->activate();
        $manager->persist($global);
        $this->addReference(self::TENANT_GLOBAL_REFERENCE, $global);

        // Tenant 3: Premium Energy Solutions
        $premium = new Tenant();
        $premium->setName('Premium Energy Solutions')
            ->setSubdomain('premium')
            ->activate();
        $manager->persist($premium);
        $this->addReference(self::TENANT_PREMIUM_REFERENCE, $premium);

        $manager->flush();
    }
}
