<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Embeddable\Address;
use App\Entity\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ClientFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Create clients for Acme Fuel Company
        /** @var Tenant $acmeTenant */
        $acmeTenant = $this->getReference(TenantFixtures::TENANT_ACME_REFERENCE, Tenant::class);
        $this->createClientsForTenant($manager, $acmeTenant, [
            [
                'companyName' => 'Vancouver Manufacturing Inc',
                'contactName' => 'John Smith',
                'email' => 'john.smith@vanmfg.ca',
                'phone' => '+1-604-555-1001',
                'address' => ['1234 Industrial Way', 'Vancouver', 'BC', 'V6B 1A1', 'Canada'],
            ],
            [
                'companyName' => 'Pacific Coast Logistics',
                'contactName' => 'Sarah Johnson',
                'email' => 'sarah.johnson@pclogistics.ca',
                'phone' => '+1-604-555-1002',
                'address' => ['5678 Harbour Road', 'Vancouver', 'BC', 'V6C 2B2', 'Canada'],
            ],
            [
                'companyName' => 'BC Tech Solutions Corp',
                'contactName' => 'Michael Brown',
                'email' => 'michael.brown@bctechsolutions.ca',
                'phone' => '+1-604-555-1003',
                'address' => ['910 Innovation Drive', 'Burnaby', 'BC', 'V5H 3C3', 'Canada'],
            ],
        ]);

        // Create clients for Global Petro Distribution
        /** @var Tenant $globalTenant */
        $globalTenant = $this->getReference(TenantFixtures::TENANT_GLOBAL_REFERENCE, Tenant::class);
        $this->createClientsForTenant($manager, $globalTenant, [
            [
                'companyName' => 'Surrey Transport Services',
                'contactName' => 'Emily Davis',
                'email' => 'emily.davis@surreytransport.ca',
                'phone' => '+1-604-555-2001',
                'address' => ['2345 Highway 1', 'Surrey', 'BC', 'V3T 4D4', 'Canada'],
            ],
            [
                'companyName' => 'Richmond Shipping Co',
                'contactName' => 'David Wilson',
                'email' => 'david.wilson@richmondshipping.ca',
                'phone' => '+1-604-555-2002',
                'address' => ['6789 Port Way', 'Richmond', 'BC', 'V7C 5E5', 'Canada'],
            ],
            [
                'companyName' => 'Fraser Valley Distributors',
                'contactName' => 'Lisa Martinez',
                'email' => 'lisa.martinez@fvdist.ca',
                'phone' => '+1-604-555-2003',
                'address' => ['3456 Distribution Centre Rd', 'Abbotsford', 'BC', 'V2S 6F6', 'Canada'],
            ],
        ]);

        // Create clients for Premium Energy Solutions
        /** @var Tenant $premiumTenant */
        $premiumTenant = $this->getReference(TenantFixtures::TENANT_PREMIUM_REFERENCE, Tenant::class);
        $this->createClientsForTenant($manager, $premiumTenant, [
            [
                'companyName' => 'Victoria Elite Manufacturing',
                'contactName' => 'Robert Taylor',
                'email' => 'robert.taylor@vicelitemfg.ca',
                'phone' => '+1-250-555-3001',
                'address' => ['1111 Corporate Plaza', 'Victoria', 'BC', 'V8W 1G1', 'Canada'],
            ],
            [
                'companyName' => 'Kelowna Logistics Partners',
                'contactName' => 'Jennifer Anderson',
                'email' => 'jennifer.anderson@kelownalogistics.ca',
                'phone' => '+1-250-555-3002',
                'address' => ['2222 Orchard Park Drive', 'Kelowna', 'BC', 'V1Y 2H2', 'Canada'],
            ],
            [
                'companyName' => 'Nanaimo Construction Ltd',
                'contactName' => 'William Thomas',
                'email' => 'william.thomas@nanaimoconstruction.ca',
                'phone' => '+1-250-555-3003',
                'address' => ['3333 Terminal Avenue', 'Nanaimo', 'BC', 'V9S 3I3', 'Canada'],
            ],
        ]);

        $manager->flush();
    }

    /**
     * @param array<int, array{companyName: string, contactName: string, email: string, phone: string, address: array<int, string>}> $clientsData
     */
    private function createClientsForTenant(ObjectManager $manager, Tenant $tenant, array $clientsData): void
    {
        foreach ($clientsData as $data) {
            $address = new Address();
            $address->setStreet($data['address'][0])
                ->setCity($data['address'][1])
                ->setState($data['address'][2])
                ->setPostalCode($data['address'][3])
                ->setCountry($data['address'][4]);

            $client = new Client();
            $client->setCompanyName($data['companyName'])
                ->setContactName($data['contactName'])
                ->setEmail($data['email'])
                ->setPhone($data['phone'])
                ->setBillingAddress($address)
                ->setTenant($tenant)
                ->activate();

            $manager->persist($client);
        }
    }

    public function getDependencies(): array
    {
        return [
            TenantFixtures::class,
        ];
    }
}
