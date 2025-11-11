<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Embeddable;

use App\Entity\Embeddable\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    private Address $address;

    protected function setUp(): void
    {
        $this->address = new Address();
    }

    public function testSetAndGetStreet(): void
    {
        $street = '123 Main Street';
        $this->address->setStreet($street);

        $this->assertEquals($street, $this->address->getStreet());
    }

    public function testSetAndGetCity(): void
    {
        $city = 'New York';
        $this->address->setCity($city);

        $this->assertEquals($city, $this->address->getCity());
    }

    public function testSetAndGetState(): void
    {
        $state = 'NY';
        $this->address->setState($state);

        $this->assertEquals($state, $this->address->getState());
    }

    public function testSetAndGetPostalCode(): void
    {
        $postalCode = '10001';
        $this->address->setPostalCode($postalCode);

        $this->assertEquals($postalCode, $this->address->getPostalCode());
    }

    public function testSetAndGetCountry(): void
    {
        $country = 'USA';
        $this->address->setCountry($country);

        $this->assertEquals($country, $this->address->getCountry());
    }

    public function testIsEmptyWhenNoDataSet(): void
    {
        $this->assertTrue($this->address->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenAnyFieldSet(): void
    {
        $this->address->setStreet('123 Main St');
        $this->assertFalse($this->address->isEmpty());
    }

    public function testGetFormattedWithAllFields(): void
    {
        $this->address
            ->setStreet('123 Main Street')
            ->setCity('New York')
            ->setState('NY')
            ->setPostalCode('10001')
            ->setCountry('USA');

        $formatted = $this->address->getFormatted();
        $this->assertEquals('123 Main Street, New York, NY, 10001, USA', $formatted);
    }

    public function testGetFormattedWithPartialFields(): void
    {
        $this->address
            ->setCity('New York')
            ->setState('NY');

        $formatted = $this->address->getFormatted();
        $this->assertEquals('New York, NY', $formatted);
    }

    public function testGetFormattedWhenEmpty(): void
    {
        $formatted = $this->address->getFormatted();
        $this->assertEquals('', $formatted);
    }

    public function testFluentInterface(): void
    {
        $result = $this->address
            ->setStreet('456 Oak Ave')
            ->setCity('Los Angeles')
            ->setState('CA')
            ->setPostalCode('90001')
            ->setCountry('USA');

        $this->assertInstanceOf(Address::class, $result);
        $this->assertEquals('456 Oak Ave', $this->address->getStreet());
    }
}
