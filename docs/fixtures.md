# Database Fixtures Documentation

## Overview
This document describes the seed data (fixtures) available for development and testing.

## Loading Fixtures

### Load All Fixtures
```bash
docker compose exec app php bin/console doctrine:fixtures:load
```

**Warning:** This command will **purge all existing data** and reload the fixtures.

### Load Fixtures Non-Interactively (CI/CD)
```bash
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

## Available Fixtures

### 1. TenantFixtures
Creates **3 tenant organizations** to test multi-tenancy:

| Tenant Name                  | Subdomain | Status  |
|------------------------------|-----------|---------|
| Acme Fuel Company            | `acme`    | active  |
| Global Petro Distribution    | `global`  | active  |
| Premium Energy Solutions     | `premium` | active  |

### 2. UserFixtures
Creates **9 users** (3 per tenant) with different roles:

#### Acme Fuel Company Users
| Email                          | Password        | Role             | Name            |
|--------------------------------|-----------------|------------------|-----------------|
| `acme.admin@example.com`       | `admin123`      | ROLE_ADMIN       | Acme Admin      |
| `acme.dispatcher@example.com`  | `dispatcher123` | ROLE_DISPATCHER  | Acme Dispatcher |
| `acme.user@example.com`        | `user123`       | ROLE_USER        | Acme User       |

#### Global Petro Distribution Users
| Email                           | Password        | Role             | Name              |
|---------------------------------|-----------------|------------------|-------------------|
| `global.admin@example.com`      | `admin123`      | ROLE_ADMIN       | Global Admin      |
| `global.dispatcher@example.com` | `dispatcher123` | ROLE_DISPATCHER  | Global Dispatcher |
| `global.user@example.com`       | `user123`       | ROLE_USER        | Global User       |

#### Premium Energy Solutions Users
| Email                            | Password        | Role             | Name               |
|----------------------------------|-----------------|------------------|--------------------|
| `premium.admin@example.com`      | `admin123`      | ROLE_ADMIN       | Premium Admin      |
| `premium.dispatcher@example.com` | `dispatcher123` | ROLE_DISPATCHER  | Premium Dispatcher |
| `premium.user@example.com`       | `user123`       | ROLE_USER        | Premium User       |

### 3. ClientFixtures
Creates **9 clients** (3 per tenant) with complete billing addresses:

#### Acme Fuel Company Clients
1. **ABC Manufacturing Inc**
   - Contact: John Smith (john.smith@abcmanufacturing.com)
   - Phone: +1-555-1001
   - Location: 123 Industrial Blvd, Chicago, IL 60601, USA

2. **XYZ Logistics LLC**
   - Contact: Sarah Johnson (sarah.johnson@xyzlogistics.com)
   - Phone: +1-555-1002
   - Location: 456 Warehouse Way, Chicago, IL 60602, USA

3. **Tech Solutions Corp**
   - Contact: Michael Brown (michael.brown@techsolutions.com)
   - Phone: +1-555-1003
   - Location: 789 Tech Park Dr, Chicago, IL 60603, USA

#### Global Petro Distribution Clients
1. **Mega Transport Services**
   - Contact: Emily Davis (emily.davis@megatransport.com)
   - Phone: +1-555-2001
   - Location: 321 Highway 101, Los Angeles, CA 90001, USA

2. **Pacific Shipping Co**
   - Contact: David Wilson (david.wilson@pacificshipping.com)
   - Phone: +1-555-2002
   - Location: 654 Port Avenue, Los Angeles, CA 90002, USA

3. **West Coast Distributors**
   - Contact: Lisa Martinez (lisa.martinez@westcoastdist.com)
   - Phone: +1-555-2003
   - Location: 987 Distribution Center, Los Angeles, CA 90003, USA

#### Premium Energy Solutions Clients
1. **Elite Manufacturing Group**
   - Contact: Robert Taylor (robert.taylor@elitemfg.com)
   - Phone: +1-555-3001
   - Location: 111 Corporate Plaza, New York, NY 10001, USA

2. **Premium Logistics Partners**
   - Contact: Jennifer Anderson (jennifer.anderson@premiumlogistics.com)
   - Phone: +1-555-3002
   - Location: 222 Business Park, New York, NY 10002, USA

3. **Metro Construction LLC**
   - Contact: William Thomas (william.thomas@metroconstruction.com)
   - Phone: +1-555-3003
   - Location: 333 Builder Street, New York, NY 10003, USA

## Testing with Fixtures

### Authenticate as Different Users

#### Login as Admin (Acme)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "acme.admin@example.com",
    "password": "admin123"
  }'
```

#### Login as Dispatcher (Global)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "global.dispatcher@example.com",
    "password": "dispatcher123"
  }'
```

#### Login as Regular User (Premium)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "premium.user@example.com",
    "password": "user123"
  }'
```

### Test Multi-Tenancy Isolation

1. **Login as Acme Admin** and get clients:
```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"acme.admin@example.com","password":"admin123"}' \
  | jq -r '.token')

# Get clients (should only see Acme's 3 clients)
curl -X GET http://localhost:8000/api/clients \
  -H "Authorization: Bearer $TOKEN"
```

2. **Login as Global Admin** and get clients:
```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"global.admin@example.com","password":"admin123"}' \
  | jq -r '.token')

# Get clients (should only see Global's 3 clients)
curl -X GET http://localhost:8000/api/clients \
  -H "Authorization: Bearer $TOKEN"
```

### Test RBAC Permissions

#### Test Dispatcher Can Create Client
```bash
# Login as dispatcher
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"acme.dispatcher@example.com","password":"dispatcher123"}' \
  | jq -r '.token')

# Create client (should succeed)
curl -X POST http://localhost:8000/api/clients \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "companyName": "New Test Company",
    "contactName": "Test Contact",
    "email": "test@example.com",
    "phone": "+1-555-9999"
  }'
```

#### Test Regular User Cannot Create Client
```bash
# Login as regular user
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"acme.user@example.com","password":"user123"}' \
  | jq -r '.token')

# Try to create client (should return 403 Forbidden)
curl -X POST http://localhost:8000/api/clients \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "companyName": "Should Fail",
    "contactName": "Fail",
    "email": "fail@example.com"
  }'
```

## Fixture Dependencies

The fixtures have dependencies to ensure proper loading order:

1. **TenantFixtures** (no dependencies) - Loaded first
2. **UserFixtures** (depends on TenantFixtures) - Loaded after tenants
3. **ClientFixtures** (depends on TenantFixtures) - Loaded after tenants

Doctrine Fixtures Bundle automatically handles the dependency order.

## Development Workflow

### Initial Setup
```bash
# Run migrations
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

### Reset Database to Clean State
```bash
# Drop and recreate database
docker compose exec app php bin/console doctrine:database:drop --force --if-exists
docker compose exec app php bin/console doctrine:database:create

# Run migrations
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

### Quick Reset (Keep Schema)
```bash
# Just reload fixtures (purges data and reloads)
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

## Verification Commands

### Check Tenants
```bash
docker compose exec app php bin/console dbal:run-sql "SELECT name, subdomain, status FROM tenants ORDER BY name"
```

### Check Users
```bash
docker compose exec app php bin/console dbal:run-sql "SELECT email, first_name, last_name, roles FROM users ORDER BY email"
```

### Check Clients
```bash
docker compose exec app php bin/console dbal:run-sql "SELECT company_name, contact_name, email, billing_city FROM clients ORDER BY company_name"
```

### Check Counts
```bash
docker compose exec app php bin/console dbal:run-sql "SELECT 
  (SELECT COUNT(*) FROM tenants) as tenants,
  (SELECT COUNT(*) FROM users) as users,
  (SELECT COUNT(*) FROM clients) as clients"
```

## Adding More Fixtures

### Create a New Fixture Class
```php
<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MyNewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Your fixture logic here
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TenantFixtures::class,
            // Add other dependencies if needed
        ];
    }
}
```

### Best Practices
1. **Use references** to link fixtures (e.g., `$this->getReference()`)
2. **Implement DependentFixtureInterface** when fixtures depend on others
3. **Use realistic data** to better simulate production scenarios
4. **Keep passwords simple** for development (e.g., `admin123`)
5. **Document credentials** clearly for other developers

## Security Notes

⚠️ **WARNING**: These fixtures are for **development and testing only**.

- **Never use these credentials in production**
- All passwords are weak and publicly documented
- Email addresses use `@example.com` domain
- Data should be considered public/insecure

## Fixture File Locations

```
src/DataFixtures/
├── TenantFixtures.php   # Creates 3 tenants
├── UserFixtures.php     # Creates 9 users (3 per tenant)
└── ClientFixtures.php   # Creates 9 clients (3 per tenant)
```

## Summary

- **3 Tenants** with unique subdomains
- **9 Users** (3 per tenant) covering all roles
- **9 Clients** (3 per tenant) with complete data
- **All passwords**: `admin123`, `dispatcher123`, or `user123`
- **Multi-tenancy isolation** enforced automatically
- **RBAC permissions** ready for testing

---
**Last Updated:** 2024-11-11
**Fixtures Version:** 1.0
