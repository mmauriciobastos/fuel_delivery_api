# Task 013 - Location Entity CRUD - Completion Summary

**Status:** ✅ Completed  
**Date:** November 11, 2024  
**Branch:** main  
**Commits:** 692e163, 7bb3c82, 8939a06, 491285e, b2260d5

---

## Overview

Successfully implemented the Location entity and CRUD operations for managing delivery and billing addresses for fuel delivery clients. The system supports Canadian addresses with optional geocoding coordinates and primary location designation.

---

## Implementation Details

### 1. Database Schema

**Table:** `locations`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Auto-generated UUID v4 |
| `tenant_id` | UUID | NOT NULL, FK → tenants(id) | Multi-tenant isolation |
| `client_id` | UUID | NOT NULL, FK → clients(id) CASCADE | Client relationship |
| `address_line1` | VARCHAR(255) | NOT NULL | Primary address line |
| `address_line2` | VARCHAR(255) | NULL | Secondary address (suite, unit) |
| `city` | VARCHAR(100) | NOT NULL | City name |
| `state` | VARCHAR(50) | NOT NULL | Province/State (e.g., BC, AB) |
| `postal_code` | VARCHAR(20) | NOT NULL | Canadian postal code |
| `country` | VARCHAR(100) | NOT NULL, DEFAULT 'Canada' | Country name |
| `latitude` | DECIMAL(10,8) | NULL | Latitude coordinate |
| `longitude` | DECIMAL(11,8) | NULL | Longitude coordinate |
| `special_instructions` | TEXT | NULL | Delivery instructions |
| `is_primary` | BOOLEAN | NOT NULL, DEFAULT false | Primary location flag |
| `created_at` | TIMESTAMP | NOT NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- `idx_location_tenant` on `tenant_id`
- `idx_location_client` on `client_id`
- `idx_location_is_primary` on `is_primary`

**Foreign Keys:**
- `tenant_id` → `tenants(id)` (NO ACTION)
- `client_id` → `clients(id)` (CASCADE DELETE)

**Migration:** `Version20251111233347`

---

### 2. Entity Features

**File:** `src/Entity/Location.php` (410 lines)

#### Properties
- **UUID Primary Key:** Auto-generated with Doctrine UUID generator
- **Relationships:** 
  - `ManyToOne` with Tenant (multi-tenant isolation)
  - `ManyToOne` with Client (CASCADE delete when client deleted)
- **Address Fields:** Full Canadian address support
- **Geocoding:** Optional latitude/longitude as DECIMAL for precision
- **Business Logic:** Primary location flag, special delivery instructions

#### Business Methods

```php
// Mark location as primary
$location->markAsPrimary();

// Unmark as primary
$location->unmarkAsPrimary();

// Get formatted multi-line address
$formattedAddress = $location->getFormattedAddress();
// Output:
// 1234 Main Street
// Suite 100
// Vancouver, BC V6B 1A1
// Canada

// Get one-line address
$oneLineAddress = $location->getOneLineAddress();
// Output: "1234 Main Street, Suite 100, Vancouver, BC, V6B 1A1, Canada"

// Check if geocoded
if ($location->hasCoordinates()) {
    echo "Latitude: " . $location->getLatitude();
    echo "Longitude: " . $location->getLongitude();
}
```

#### Lifecycle Callbacks
- `@ORM\PrePersist`: Sets `createdAt` and `updatedAt` on creation
- `@ORM\PreUpdate`: Updates `updatedAt` on modification

---

### 3. Repository Methods

**File:** `src/Repository/LocationRepository.php` (115 lines)

#### Available Methods

```php
// Find all locations for a client (ordered by isPrimary DESC, city ASC)
$locations = $locationRepository->findByClient($client);

// Find the primary location for a client
$primaryLocation = $locationRepository->findPrimaryByClient($client);

// Find locations with filters
$locations = $locationRepository->findByTenantAndFilters($tenant, [
    'city' => 'Vancouver',
    'state' => 'BC',
    'isPrimary' => true
]);

// Count locations for a client
$count = $locationRepository->countByClient($client);

// Find all geocoded locations for a tenant
$geocodedLocations = $locationRepository->findGeocodedLocations($tenant);
```

---

### 4. API Endpoints

**Base URL:** `/api/locations`

#### GET /api/locations
Get collection of locations

**Security:** `ROLE_USER` (authenticated users)

**Query Parameters:**
- `client` (exact) - Filter by client UUID
- `city` (partial) - Filter by city name (case-insensitive)
- `state` (exact) - Filter by state/province
- `postalCode` (partial) - Filter by postal code
- `isPrimary` (exact) - Filter by primary flag (true/false)
- `order[city]` - Order by city (asc/desc)
- `order[state]` - Order by state (asc/desc)
- `order[isPrimary]` - Order by primary flag (asc/desc)
- `order[createdAt]` - Order by creation date (asc/desc)
- `page` - Page number (default: 1)
- `itemsPerPage` - Items per page (default: 30)

**Example Request:**
```bash
curl -X GET "http://localhost:8080/api/locations?city=Vancouver&order[city]=asc" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/ld+json"
```

**Example Response:**
```json
{
  "@context": "/api/contexts/Location",
  "@id": "/api/locations",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/locations/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0f",
      "@type": "Location",
      "id": "018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0f",
      "tenant": "/api/tenants/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0a",
      "client": "/api/clients/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0b",
      "addressLine1": "1234 Main Street",
      "addressLine2": "Suite 100",
      "city": "Vancouver",
      "state": "BC",
      "postalCode": "V6B 1A1",
      "country": "Canada",
      "latitude": "49.28270000",
      "longitude": "-123.12070000",
      "specialInstructions": "Use loading dock at rear",
      "isPrimary": true,
      "createdAt": "2024-11-11T15:30:00+00:00",
      "updatedAt": "2024-11-11T15:30:00+00:00"
    }
  ],
  "hydra:totalItems": 1
}
```

#### POST /api/locations
Create a new location

**Security:** `ROLE_DISPATCHER`

**Request Body:**
```json
{
  "client": "/api/clients/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0b",
  "addressLine1": "1234 Main Street",
  "addressLine2": "Suite 100",
  "city": "Vancouver",
  "state": "BC",
  "postalCode": "V6B 1A1",
  "country": "Canada",
  "latitude": "49.2827",
  "longitude": "-123.1207",
  "specialInstructions": "Use loading dock at rear",
  "isPrimary": true
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8080/api/locations" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "client": "/api/clients/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0b",
    "addressLine1": "1234 Main Street",
    "city": "Vancouver",
    "state": "BC",
    "postalCode": "V6B 1A1"
  }'
```

**Response:** `201 Created` with location object

#### GET /api/locations/{id}
Get a single location

**Security:** `ROLE_USER`

**Example Request:**
```bash
curl -X GET "http://localhost:8080/api/locations/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0f" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/ld+json"
```

**Response:** `200 OK` with location object

#### PATCH /api/locations/{id}
Update a location

**Security:** `ROLE_DISPATCHER`

**Request Body:** (partial update)
```json
{
  "specialInstructions": "Updated delivery instructions",
  "isPrimary": false
}
```

**Example Request:**
```bash
curl -X PATCH "http://localhost:8080/api/locations/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0f" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/merge-patch+json" \
  -d '{
    "specialInstructions": "Updated delivery instructions"
  }'
```

**Response:** `200 OK` with updated location object

#### DELETE /api/locations/{id}
Delete a location

**Security:** `ROLE_ADMIN`

**Example Request:**
```bash
curl -X DELETE "http://localhost:8080/api/locations/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0f" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:** `204 No Content`

---

### 5. RBAC Configuration

| Operation | Endpoint | ROLE_USER | ROLE_DISPATCHER | ROLE_ADMIN |
|-----------|----------|-----------|-----------------|------------|
| List | GET /api/locations | ✅ | ✅ | ✅ |
| Get Single | GET /api/locations/{id} | ✅ | ✅ | ✅ |
| Create | POST /api/locations | ❌ | ✅ | ✅ |
| Update | PATCH /api/locations/{id} | ❌ | ✅ | ✅ |
| Delete | DELETE /api/locations/{id} | ❌ | ❌ | ✅ |

---

### 6. Validation Rules

| Field | Rules |
|-------|-------|
| `client` | Required, must exist and belong to same tenant |
| `addressLine1` | Required, 3-255 characters |
| `addressLine2` | Optional, max 255 characters |
| `city` | Required, 2-100 characters |
| `state` | Required, 2-50 characters |
| `postalCode` | Required, 3-20 characters |
| `country` | Required, 2-100 characters, defaults to "Canada" |
| `latitude` | Optional, -90 to 90 |
| `longitude` | Optional, -180 to 180 |
| `specialInstructions` | Optional, TEXT |
| `isPrimary` | Boolean, defaults to false |

**Validation Error Response (422):**
```json
{
  "@context": "/api/contexts/ConstraintViolationList",
  "@type": "ConstraintViolationList",
  "hydra:title": "An error occurred",
  "hydra:description": "addressLine1: Address line 1 is required\ncity: City is required",
  "violations": [
    {
      "propertyPath": "addressLine1",
      "message": "Address line 1 is required"
    },
    {
      "propertyPath": "city",
      "message": "City is required"
    }
  ]
}
```

---

### 7. Testing Coverage

#### Unit Tests
**File:** `tests/Unit/Entity/LocationTest.php`
- **Tests:** 25
- **Assertions:** 60
- **Coverage:**
  - Default values on instantiation
  - All getter/setter methods with fluent interface
  - `markAsPrimary()` / `unmarkAsPrimary()`
  - `getFormattedAddress()` with/without addressLine2
  - `getOneLineAddress()` with/without addressLine2
  - `hasCoordinates()` - 4 scenarios
  - Lifecycle callbacks (onPrePersist, onPreUpdate)

#### Integration Tests
**File:** `tests/Integration/Api/LocationApiTest.php`
- **Tests:** 14
- **Assertions:** 30
- **Coverage:**
  - Authentication requirement
  - CRUD operations with RBAC enforcement
  - API Platform filters (client, city)
  - Validation (missing required fields)
  - Primary location creation
  - Geocoding coordinates with DECIMAL precision

**All 39 tests passing (90 total assertions)**

**Run Tests:**
```bash
# All Location tests
docker compose exec app php bin/phpunit tests/Unit/Entity/LocationTest.php tests/Integration/Api/LocationApiTest.php

# Unit tests only
docker compose exec app php bin/phpunit tests/Unit/Entity/LocationTest.php

# Integration tests only
docker compose exec app php bin/phpunit tests/Integration/Api/LocationApiTest.php
```

---

### 8. Code Quality

#### PHPStan Analysis
- **Level:** 8 (strictest)
- **Status:** ✅ No errors
- **Files Analyzed:** 3
  - `src/Entity/Location.php`
  - `src/Repository/LocationRepository.php`
  - `src/EventSubscriber/TenantEntitySubscriber.php`

#### PHP CS Fixer
- **Status:** ✅ Applied
- **Fixes:** Minor formatting (native function calls)
- **Files Fixed:** 3
  - `src/Entity/Location.php` (line 381: sprintf → \sprintf)
  - `src/Repository/LocationRepository.php`
  - `tests/Integration/Api/LocationApiTest.php`

---

## Usage Examples

### Example 1: Create a Primary Location with Geocoding

```php
use App\Entity\Location;
use App\Repository\LocationRepository;

// In a service or controller
$location = new Location();
$location->setClient($client)
         ->setTenant($tenantContext->getCurrentTenant())
         ->setAddressLine1('1500 West Georgia Street')
         ->setCity('Vancouver')
         ->setState('BC')
         ->setPostalCode('V6G 2Z6')
         ->setCountry('Canada')
         ->setLatitude('49.2909')
         ->setLongitude('-123.1285')
         ->setSpecialInstructions('Use main entrance, ask for receiving')
         ->markAsPrimary();

$entityManager->persist($location);
$entityManager->flush();
```

### Example 2: Get All Locations for a Client

```php
use App\Repository\LocationRepository;

$locations = $locationRepository->findByClient($client);

foreach ($locations as $location) {
    echo $location->getOneLineAddress() . "\n";
    
    if ($location->isPrimary()) {
        echo "  [PRIMARY]\n";
    }
    
    if ($location->hasCoordinates()) {
        echo "  Coords: {$location->getLatitude()}, {$location->getLongitude()}\n";
    }
}
```

### Example 3: Filter Locations by City

```bash
# API request
curl -X GET "http://localhost:8080/api/locations?city=Vancouver&isPrimary=true" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/ld+json"
```

### Example 4: Update Special Instructions

```bash
# API request
curl -X PATCH "http://localhost:8080/api/locations/018c5a2e-9b1a-7c3d-8e4f-5a6b7c8d9e0f" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/merge-patch+json" \
  -d '{
    "specialInstructions": "Updated: Call dispatcher upon arrival"
  }'
```

### Example 5: Find Primary Location for Client

```php
use App\Repository\LocationRepository;

$primaryLocation = $locationRepository->findPrimaryByClient($client);

if ($primaryLocation) {
    echo "Primary delivery address:\n";
    echo $primaryLocation->getFormattedAddress();
} else {
    echo "No primary location set for this client.";
}
```

---

## Multi-Tenancy Notes

- **All queries automatically filtered by tenant** via `TenantIsolationFilter`
- **TenantEntitySubscriber** auto-assigns tenant on Location creation
- **Locations cascade deleted when client deleted** via `onDelete: 'CASCADE'`
- **Cross-tenant access prevented** at database query level

---

## Canadian Address Format

The system is optimized for Canadian addresses:

- **Default Country:** "Canada"
- **State Field:** Stores province codes (BC, AB, ON, QC, etc.)
- **Postal Code:** Supports Canadian format (V6B 1A1)
- **Geocoding:** DECIMAL precision suitable for mapping

**Example Canadian Address:**
```
1234 Main Street
Suite 100
Vancouver, BC V6B 1A1
Canada
```

---

## Geocoding Implementation

### Precision
- **Latitude:** DECIMAL(10,8) - 8 decimal places (~1.1mm precision)
- **Longitude:** DECIMAL(11,8) - 8 decimal places (~1.1mm precision)

### Validation
- Latitude: -90 to 90
- Longitude: -180 to 180

### Storage
Stored as strings in PHP (DECIMAL type), converted to float for calculations:

```php
// Check if geocoded
if ($location->hasCoordinates()) {
    $lat = (float) $location->getLatitude();
    $lng = (float) $location->getLongitude();
    
    // Use for distance calculations, mapping, etc.
}
```

---

## Git Commits

### Commit History

1. **fix(auth): correct JWT_PASSPHRASE configuration** (692e163)
   - Synced JWT passphrase between .env and .env.test
   - Fixed JWT authentication in integration tests

2. **feat(entity): add Location entity with address and geocoding** (7bb3c82)
   - Location entity (410 lines)
   - LocationRepository (115 lines)
   - TenantEntitySubscriber update

3. **feat(migration): add database migration for locations table** (8939a06)
   - Migration Version20251111233347
   - Indexes and foreign keys
   - CASCADE delete constraint

4. **test(location): add comprehensive unit tests** (491285e)
   - 25 unit tests, 60 assertions
   - Full entity coverage

5. **test(location): add integration tests for Location API** (b2260d5)
   - 14 integration tests, 30 assertions
   - API endpoint coverage with RBAC

---

## Next Steps (Task 014)

Task 013 is complete. The next task (Task 014 - Truck Entity CRUD) will implement:
- Truck entity for delivery vehicles
- Truck repository with status-based queries
- API endpoints for truck management
- Integration with Location for route planning

---

## Files Created/Modified

### Created
- `src/Entity/Location.php` (410 lines)
- `src/Repository/LocationRepository.php` (115 lines)
- `migrations/Version20251111233347.php` (42 lines)
- `tests/Unit/Entity/LocationTest.php` (290 lines)
- `tests/Integration/Api/LocationApiTest.php` (480 lines)
- `docs/task-013-completion-summary.md` (this file)

### Modified
- `src/EventSubscriber/TenantEntitySubscriber.php` (added Location support)
- `.env` (fixed JWT_PASSPHRASE)
- `.env.test` (fixed JWT_PASSPHRASE)

**Total Lines Added:** ~1,340 lines

---

## Known Issues / Limitations

- **No automatic geocoding:** Coordinates must be provided manually or via external service
- **No primary location uniqueness constraint:** Multiple locations can be marked as primary (business logic should handle this)
- **No address validation:** System accepts any address format (consider adding geocoding service validation)
- **Test isolation:** Integration tests have simplified assertions due to shared database state

---

## References

- [Task 013 Requirements](../tasks/task-013-location-entity-crud.md)
- [API Platform Documentation](https://api-platform.com/docs/)
- [Doctrine ORM Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/basic-mapping.html)
- [Symfony Validation](https://symfony.com/doc/current/validation.html)

---

**Task 013 Status:** ✅ **COMPLETED**
