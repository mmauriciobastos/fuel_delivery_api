<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\Location;
use App\Entity\Tenant;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function testLocationCreationWithDefaults(): void
    {
        $location = new Location();

        $this->assertNull($location->getId());
        $this->assertNull($location->getTenant());
        $this->assertNull($location->getClient());
        $this->assertNull($location->getAddressLine1());
        $this->assertNull($location->getAddressLine2());
        $this->assertNull($location->getCity());
        $this->assertNull($location->getState());
        $this->assertNull($location->getPostalCode());
        $this->assertEquals('Canada', $location->getCountry());
        $this->assertNull($location->getLatitude());
        $this->assertNull($location->getLongitude());
        $this->assertNull($location->getSpecialInstructions());
        $this->assertFalse($location->isPrimary());
        $this->assertFalse($location->getIsPrimary());
        $this->assertNull($location->getCreatedAt());
        $this->assertNull($location->getUpdatedAt());
    }

    public function testSetAndGetTenant(): void
    {
        $location = new Location();
        $tenant = $this->createMock(Tenant::class);

        $result = $location->setTenant($tenant);

        $this->assertSame($location, $result);
        $this->assertSame($tenant, $location->getTenant());
    }

    public function testSetAndGetClient(): void
    {
        $location = new Location();
        $client = $this->createMock(Client::class);

        $result = $location->setClient($client);

        $this->assertSame($location, $result);
        $this->assertSame($client, $location->getClient());
    }

    public function testSetAndGetAddressLine1(): void
    {
        $location = new Location();

        $result = $location->setAddressLine1('1234 Main Street');

        $this->assertSame($location, $result);
        $this->assertEquals('1234 Main Street', $location->getAddressLine1());
    }

    public function testSetAndGetAddressLine2(): void
    {
        $location = new Location();

        $result = $location->setAddressLine2('Suite 100');

        $this->assertSame($location, $result);
        $this->assertEquals('Suite 100', $location->getAddressLine2());
    }

    public function testSetAndGetCity(): void
    {
        $location = new Location();

        $result = $location->setCity('Vancouver');

        $this->assertSame($location, $result);
        $this->assertEquals('Vancouver', $location->getCity());
    }

    public function testSetAndGetState(): void
    {
        $location = new Location();

        $result = $location->setState('BC');

        $this->assertSame($location, $result);
        $this->assertEquals('BC', $location->getState());
    }

    public function testSetAndGetPostalCode(): void
    {
        $location = new Location();

        $result = $location->setPostalCode('V6B 1A1');

        $this->assertSame($location, $result);
        $this->assertEquals('V6B 1A1', $location->getPostalCode());
    }

    public function testSetAndGetCountry(): void
    {
        $location = new Location();

        $result = $location->setCountry('USA');

        $this->assertSame($location, $result);
        $this->assertEquals('USA', $location->getCountry());
    }

    public function testSetAndGetLatitude(): void
    {
        $location = new Location();

        $result = $location->setLatitude('49.2827');

        $this->assertSame($location, $result);
        $this->assertEquals('49.2827', $location->getLatitude());
    }

    public function testSetAndGetLongitude(): void
    {
        $location = new Location();

        $result = $location->setLongitude('-123.1207');

        $this->assertSame($location, $result);
        $this->assertEquals('-123.1207', $location->getLongitude());
    }

    public function testSetAndGetSpecialInstructions(): void
    {
        $location = new Location();

        $result = $location->setSpecialInstructions('Use back entrance');

        $this->assertSame($location, $result);
        $this->assertEquals('Use back entrance', $location->getSpecialInstructions());
    }

    public function testSetAndGetIsPrimary(): void
    {
        $location = new Location();

        $result = $location->setIsPrimary(true);

        $this->assertSame($location, $result);
        $this->assertTrue($location->isPrimary());
        $this->assertTrue($location->getIsPrimary());
    }

    public function testMarkAsPrimary(): void
    {
        $location = new Location();

        $result = $location->markAsPrimary();

        $this->assertSame($location, $result);
        $this->assertTrue($location->isPrimary());
    }

    public function testUnmarkAsPrimary(): void
    {
        $location = new Location();
        $location->markAsPrimary();

        $result = $location->unmarkAsPrimary();

        $this->assertSame($location, $result);
        $this->assertFalse($location->isPrimary());
    }

    public function testGetFormattedAddressWithoutAddressLine2(): void
    {
        $location = new Location();
        $location->setAddressLine1('1234 Main Street')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');

        $expected = "1234 Main Street\nVancouver, BC V6B 1A1\nCanada";

        $this->assertEquals($expected, $location->getFormattedAddress());
    }

    public function testGetFormattedAddressWithAddressLine2(): void
    {
        $location = new Location();
        $location->setAddressLine1('1234 Main Street')
                 ->setAddressLine2('Suite 100')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');

        $expected = "1234 Main Street\nSuite 100\nVancouver, BC V6B 1A1\nCanada";

        $this->assertEquals($expected, $location->getFormattedAddress());
    }

    public function testGetOneLineAddressWithoutAddressLine2(): void
    {
        $location = new Location();
        $location->setAddressLine1('1234 Main Street')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');

        $expected = '1234 Main Street, Vancouver, BC, V6B 1A1, Canada';

        $this->assertEquals($expected, $location->getOneLineAddress());
    }

    public function testGetOneLineAddressWithAddressLine2(): void
    {
        $location = new Location();
        $location->setAddressLine1('1234 Main Street')
                 ->setAddressLine2('Suite 100')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');

        $expected = '1234 Main Street, Suite 100, Vancouver, BC, V6B 1A1, Canada';

        $this->assertEquals($expected, $location->getOneLineAddress());
    }

    public function testHasCoordinatesReturnsFalseWhenNoCoordinates(): void
    {
        $location = new Location();

        $this->assertFalse($location->hasCoordinates());
    }

    public function testHasCoordinatesReturnsFalseWhenOnlyLatitude(): void
    {
        $location = new Location();
        $location->setLatitude('49.2827');

        $this->assertFalse($location->hasCoordinates());
    }

    public function testHasCoordinatesReturnsFalseWhenOnlyLongitude(): void
    {
        $location = new Location();
        $location->setLongitude('-123.1207');

        $this->assertFalse($location->hasCoordinates());
    }

    public function testHasCoordinatesReturnsTrueWhenBothSet(): void
    {
        $location = new Location();
        $location->setLatitude('49.2827')
                 ->setLongitude('-123.1207');

        $this->assertTrue($location->hasCoordinates());
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $location = new Location();

        $this->assertNull($location->getCreatedAt());
        $this->assertNull($location->getUpdatedAt());

        $location->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $location->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $location->getUpdatedAt());
        $this->assertEquals(
            $location->getCreatedAt()->getTimestamp(),
            $location->getUpdatedAt()->getTimestamp()
        );
    }

    public function testOnPreUpdateUpdatesTimestamp(): void
    {
        $location = new Location();
        $location->onPrePersist();

        $originalUpdatedAt = $location->getUpdatedAt();
        sleep(1);

        $location->onPreUpdate();

        $this->assertNotEquals($originalUpdatedAt, $location->getUpdatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $location->getUpdatedAt());
    }
}
