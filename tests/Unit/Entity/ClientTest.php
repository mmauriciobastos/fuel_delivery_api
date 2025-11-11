<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\Embeddable\Address;
use App\Entity\Tenant;
use App\Enum\TenantStatus;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Client $client;

    private Tenant $tenant;

    protected function setUp(): void
    {
        $this->tenant = new Tenant();
        $this->tenant->setName('Test Tenant');
        $this->tenant->setSubdomain('test');
        $this->tenant->setStatus(TenantStatus::ACTIVE);

        $this->client = new Client();
    }

    public function testClientCreationWithDefaults(): void
    {
        $this->assertNull($this->client->getId());
        $this->assertTrue($this->client->isActive());
        $this->assertInstanceOf(Address::class, $this->client->getBillingAddress());
        $this->assertNull($this->client->getCreatedAt());
        $this->assertNull($this->client->getUpdatedAt());
    }

    public function testSetAndGetCompanyName(): void
    {
        $companyName = 'Acme Corporation';
        $this->client->setCompanyName($companyName);

        $this->assertEquals($companyName, $this->client->getCompanyName());
    }

    public function testSetAndGetContactName(): void
    {
        $contactName = 'John Doe';
        $this->client->setContactName($contactName);

        $this->assertEquals($contactName, $this->client->getContactName());
    }

    public function testSetAndGetEmail(): void
    {
        $email = 'john.doe@acme.com';
        $this->client->setEmail($email);

        $this->assertEquals($email, $this->client->getEmail());
    }

    public function testSetAndGetPhone(): void
    {
        $phone = '+1 (555) 123-4567';
        $this->client->setPhone($phone);

        $this->assertEquals($phone, $this->client->getPhone());
    }

    public function testSetAndGetTenant(): void
    {
        $this->client->setTenant($this->tenant);

        $this->assertEquals($this->tenant, $this->client->getTenant());
    }

    public function testSetAndGetBillingAddress(): void
    {
        $address = new Address();
        $address->setStreet('123 Main St');
        $address->setCity('New York');
        $address->setState('NY');
        $address->setPostalCode('10001');
        $address->setCountry('USA');

        $this->client->setBillingAddress($address);

        $this->assertEquals($address, $this->client->getBillingAddress());
        $this->assertEquals('123 Main St', $this->client->getBillingAddress()->getStreet());
    }

    public function testActivate(): void
    {
        $this->client->setIsActive(false);
        $this->assertFalse($this->client->isActive());

        $this->client->activate();
        $this->assertTrue($this->client->isActive());
    }

    public function testDeactivate(): void
    {
        $this->assertTrue($this->client->isActive());

        $this->client->deactivate();
        $this->assertFalse($this->client->isActive());
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $this->assertNull($this->client->getCreatedAt());
        $this->assertNull($this->client->getUpdatedAt());

        $this->client->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->client->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->client->getUpdatedAt());
    }

    public function testOnPreUpdateUpdatesTimestamp(): void
    {
        $this->client->onPrePersist();
        $originalUpdatedAt = $this->client->getUpdatedAt();

        sleep(1); // Ensure time difference
        $this->client->onPreUpdate();

        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $this->client->getUpdatedAt()->getTimestamp()
        );
    }

    public function testFluentInterface(): void
    {
        $result = $this->client
            ->setCompanyName('Test Company')
            ->setContactName('Jane Smith')
            ->setEmail('jane@test.com')
            ->setPhone('555-1234')
            ->setTenant($this->tenant)
            ->setIsActive(true);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals('Test Company', $this->client->getCompanyName());
        $this->assertEquals('Jane Smith', $this->client->getContactName());
    }
}
