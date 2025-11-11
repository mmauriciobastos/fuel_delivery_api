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
                'companyName' => 'ABC Manufacturing Inc',
                'contactName' => 'John Smith',
                'email' => 'john.smith@abcmanufacturing.com',
                'phone' => '+1-555-1001',
                'address' => ['123 Industrial Blvd', 'Chicago', 'IL', '60601', 'USA'],
            ],
            [
                'companyName' => 'XYZ Logistics LLC',
                'contactName' => 'Sarah Johnson',
                'email' => 'sarah.johnson@xyzlogistics.com',
                'phone' => '+1-555-1002',
                'address' => ['456 Warehouse Way', 'Chicago', 'IL', '60602', 'USA'],
            ],
            [
                'companyName' => 'Tech Solutions Corp',
                'contactName' => 'Michael Brown',
                'email' => 'michael.brown@techsolutions.com',
                'phone' => '+1-555-1003',
                'address' => ['789 Tech Park Dr', 'Chicago', 'IL', '60603', 'USA'],
            ],
        ]);

        // Create clients for Global Petro Distribution
        /** @var Tenant $globalTenant */
        $globalTenant = $this->getReference(TenantFixtures::TENANT_GLOBAL_REFERENCE, Tenant::class);
        $this->createClientsForTenant($manager, $globalTenant, [
            [
                'companyName' => 'Mega Transport Services',
                'contactName' => 'Emily Davis',
                'email' => 'emily.davis@megatransport.com',
                'phone' => '+1-555-2001',
                'address' => ['321 Highway 101', 'Los Angeles', 'CA', '90001', 'USA'],
            ],
            [
                'companyName' => 'Pacific Shipping Co',
                'contactName' => 'David Wilson',
                'email' => 'david.wilson@pacificshipping.com',
                'phone' => '+1-555-2002',
                'address' => ['654 Port Avenue', 'Los Angeles', 'CA', '90002', 'USA'],
            ],
            [
                'companyName' => 'West Coast Distributors',
                'contactName' => 'Lisa Martinez',
                'email' => 'lisa.martinez@westcoastdist.com',
                'phone' => '+1-555-2003',
                'address' => ['987 Distribution Center', 'Los Angeles', 'CA', '90003', 'USA'],
            ],
        ]);

        // Create clients for Premium Energy Solutions
        /** @var Tenant $premiumTenant */
        $premiumTenant = $this->getReference(TenantFixtures::TENANT_PREMIUM_REFERENCE, Tenant::class);
        $this->createClientsForTenant($manager, $premiumTenant, [
            [
                'companyName' => 'Elite Manufacturing Group',
                'contactName' => 'Robert Taylor',
                'email' => 'robert.taylor@elitemfg.com',
                'phone' => '+1-555-3001',
                'address' => ['111 Corporate Plaza', 'New York', 'NY', '10001', 'USA'],
            ],
            [
                'companyName' => 'Premium Logistics Partners',
                'contactName' => 'Jennifer Anderson',
                'email' => 'jennifer.anderson@premiumlogistics.com',
                'phone' => '+1-555-3002',
                'address' => ['222 Business Park', 'New York', 'NY', '10002', 'USA'],
            ],
            [
                'companyName' => 'Metro Construction LLC',
                'contactName' => 'William Thomas',
                'email' => 'william.thomas@metroconstruction.com',
                'phone' => '+1-555-3003',
                'address' => ['333 Builder Street', 'New York', 'NY', '10003', 'USA'],
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
