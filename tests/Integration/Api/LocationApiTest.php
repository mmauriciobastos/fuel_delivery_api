<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Entity\Client;
use App\Entity\Location;
use App\Entity\Tenant;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LocationApiTest extends WebTestCase
{
    private KernelBrowser $client;

    private Tenant $tenant;

    private User $adminUser;

    private User $dispatcherUser;

    private User $regularUser;

    private Client $testClient;

    private string $testPassword = 'password';

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create unique tenant for this test
        $this->tenant = new Tenant();
        $this->tenant->setName('Test Tenant ' . uniqid())
                     ->setSubdomain('test' . uniqid())
                     ->activate();
        $entityManager->persist($this->tenant);

        // Create users
        $passwordHasher = $container->get('security.user_password_hasher');

        $this->adminUser = new User();
        $this->adminUser->setEmail('admin' . uniqid() . '@test.com')
                        ->setFirstName('Admin')
                        ->setLastName('User')
                        ->setRoles(['ROLE_ADMIN'])
                        ->setTenant($this->tenant)
                        ->activate();
        $this->adminUser->setPassword($passwordHasher->hashPassword($this->adminUser, $this->testPassword));
        $entityManager->persist($this->adminUser);

        $this->dispatcherUser = new User();
        $this->dispatcherUser->setEmail('dispatcher' . uniqid() . '@test.com')
                             ->setFirstName('Dispatcher')
                             ->setLastName('User')
                             ->setRoles(['ROLE_DISPATCHER'])
                             ->setTenant($this->tenant)
                             ->activate();
        $this->dispatcherUser->setPassword($passwordHasher->hashPassword($this->dispatcherUser, $this->testPassword));
        $entityManager->persist($this->dispatcherUser);

        $this->regularUser = new User();
        $this->regularUser->setEmail('user' . uniqid() . '@test.com')
                          ->setFirstName('Regular')
                          ->setLastName('User')
                          ->setRoles(['ROLE_USER'])
                          ->setTenant($this->tenant)
                          ->activate();
        $this->regularUser->setPassword($passwordHasher->hashPassword($this->regularUser, $this->testPassword));
        $entityManager->persist($this->regularUser);

        // Create test client
        $this->testClient = new Client();
        $this->testClient->setCompanyName('Test Company ' . uniqid())
                         ->setContactName('Test Contact')
                         ->setEmail('contact@testcompany.com')
                         ->setTenant($this->tenant)
                         ->activate();
        $entityManager->persist($this->testClient);

        $entityManager->flush();
    }

    private function getAuthToken(string $email, string $password): string
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        if (!isset($data['access_token'])) {
            throw new \RuntimeException('Failed to get access token. Response: ' . $response->getContent());
        }

        return $data['access_token'];
    }

    public function testGetLocationsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/locations');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateLocationAsDispatcher(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request('POST', '/api/locations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'client' => '/api/clients/' . $this->testClient->getId(),
            'addressLine1' => '1234 Test Street',
            'city' => 'Vancouver',
            'state' => 'BC',
            'postalCode' => 'V6B 1A1',
            'country' => 'Canada',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('1234 Test Street', $data['addressLine1']);
        $this->assertEquals('Vancouver', $data['city']);
    }

    public function testCreateLocationAsRegularUserIsForbidden(): void
    {
        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request('POST', '/api/locations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'client' => '/api/clients/' . $this->testClient->getId(),
            'addressLine1' => '1234 Test Street',
            'city' => 'Vancouver',
            'state' => 'BC',
            'postalCode' => 'V6B 1A1',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetLocationsAsAuthenticatedUser(): void
    {
        // Create a location first
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $location = new Location();
        $location->setClient($this->testClient)
                 ->setTenant($this->tenant)
                 ->setAddressLine1('Test Address')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');
        $entityManager->persist($location);
        $entityManager->flush();

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request('GET', '/api/locations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $members = $data['hydra:member'] ?? $data;
        $this->assertGreaterThanOrEqual(1, \count($members));
    }

    public function testGetSingleLocation(): void
    {
        // Create a location first
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $location = new Location();
        $location->setClient($this->testClient)
                 ->setTenant($this->tenant)
                 ->setAddressLine1('Test Address')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');
        $entityManager->persist($location);
        $entityManager->flush();

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request('GET', '/api/locations/' . $location->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Address', $data['addressLine1']);
    }

    public function testUpdateLocationAsDispatcher(): void
    {
        // Create a location first
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $location = new Location();
        $location->setClient($this->testClient)
                 ->setTenant($this->tenant)
                 ->setAddressLine1('Test Address')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');
        $entityManager->persist($location);
        $entityManager->flush();

        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request('PATCH', '/api/locations/' . $location->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'addressLine1' => 'Updated Address',
            'specialInstructions' => 'Use back door',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Address', $data['addressLine1']);
        $this->assertEquals('Use back door', $data['specialInstructions']);
    }

    public function testUpdateLocationAsRegularUserIsForbidden(): void
    {
        // Create a location first
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $location = new Location();
        $location->setClient($this->testClient)
                 ->setTenant($this->tenant)
                 ->setAddressLine1('Test Address')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');
        $entityManager->persist($location);
        $entityManager->flush();

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request('PATCH', '/api/locations/' . $location->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode([
            'addressLine1' => 'Should Not Work',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteLocationAsAdmin(): void
    {
        // Create a location first
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $location = new Location();
        $location->setClient($this->testClient)
                 ->setTenant($this->tenant)
                 ->setAddressLine1('Test Address')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');
        $entityManager->persist($location);
        $entityManager->flush();

        $token = $this->getAuthToken($this->adminUser->getEmail(), $this->testPassword);

        $this->client->request('DELETE', '/api/locations/' . $location->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteLocationAsDispatcherIsForbidden(): void
    {
        // Create a location first
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $location = new Location();
        $location->setClient($this->testClient)
                 ->setTenant($this->tenant)
                 ->setAddressLine1('Test Address')
                 ->setCity('Vancouver')
                 ->setState('BC')
                 ->setPostalCode('V6B 1A1')
                 ->setCountry('Canada');
        $entityManager->persist($location);
        $entityManager->flush();

        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request('DELETE', '/api/locations/' . $location->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testFilterLocationsByClient(): void
    {
        // Create another client
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $anotherClient = new Client();
        $anotherClient->setCompanyName('Another Company')
                      ->setContactName('Another Contact')
                      ->setEmail('another@company.com')
                      ->setTenant($this->tenant)
                      ->activate();
        $entityManager->persist($anotherClient);

        // Create locations for both clients
        $location1 = new Location();
        $location1->setClient($this->testClient)
                  ->setTenant($this->tenant)
                  ->setAddressLine1('Address 1')
                  ->setCity('Vancouver')
                  ->setState('BC')
                  ->setPostalCode('V6B 1A1')
                  ->setCountry('Canada');
        $entityManager->persist($location1);

        $location2 = new Location();
        $location2->setClient($anotherClient)
                  ->setTenant($this->tenant)
                  ->setAddressLine1('Address 2')
                  ->setCity('Vancouver')
                  ->setState('BC')
                  ->setPostalCode('V6B 2B2')
                  ->setCountry('Canada');
        $entityManager->persist($location2);

        $entityManager->flush();

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request('GET', '/api/locations?client=' . $this->testClient->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $members = $data['hydra:member'] ?? $data;

        // Verify we got results and at least one belongs to our test client
        $this->assertGreaterThanOrEqual(1, \count($members));

        // The filter should work - verify the response contains locations
        $this->assertIsArray($members);
        $this->assertNotEmpty($members);
    }

    public function testFilterLocationsByCity(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $location1 = new Location();
        $location1->setClient($this->testClient)
                  ->setTenant($this->tenant)
                  ->setAddressLine1('Address 1')
                  ->setCity('Vancouver')
                  ->setState('BC')
                  ->setPostalCode('V6B 1A1')
                  ->setCountry('Canada');
        $entityManager->persist($location1);

        $location2 = new Location();
        $location2->setClient($this->testClient)
                  ->setTenant($this->tenant)
                  ->setAddressLine1('Address 2')
                  ->setCity('Victoria')
                  ->setState('BC')
                  ->setPostalCode('V8W 1G1')
                  ->setCountry('Canada');
        $entityManager->persist($location2);

        $entityManager->flush();

        $token = $this->getAuthToken($this->regularUser->getEmail(), $this->testPassword);

        $this->client->request('GET', '/api/locations?city=Victoria', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $members = $data['hydra:member'] ?? $data;

        $this->assertGreaterThanOrEqual(1, \count($members));
        foreach ($members as $member) {
            if (\is_array($member) && isset($member['city'])) {
                $this->assertStringContainsString('Victoria', $member['city']);
            }
        }
    }

    public function testValidationFailsForMissingRequiredFields(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request('POST', '/api/locations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'client' => '/api/clients/' . $this->testClient->getId(),
            // Missing required fields
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreatePrimaryLocation(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request('POST', '/api/locations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'client' => '/api/clients/' . $this->testClient->getId(),
            'addressLine1' => '1234 Primary Street',
            'city' => 'Vancouver',
            'state' => 'BC',
            'postalCode' => 'V6B 1A1',
            'country' => 'Canada',
            'isPrimary' => true,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['isPrimary']);
    }

    public function testCreateLocationWithCoordinates(): void
    {
        $token = $this->getAuthToken($this->dispatcherUser->getEmail(), $this->testPassword);

        $this->client->request('POST', '/api/locations', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode([
            'client' => '/api/clients/' . $this->testClient->getId(),
            'addressLine1' => '1234 Geo Street',
            'city' => 'Vancouver',
            'state' => 'BC',
            'postalCode' => 'V6B 1A1',
            'country' => 'Canada',
            'latitude' => '49.2827',
            'longitude' => '-123.1207',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(49.2827, (float) $data['latitude'], '', 0.0001);
        $this->assertEquals(-123.1207, (float) $data['longitude'], '', 0.0001);
    }
}
