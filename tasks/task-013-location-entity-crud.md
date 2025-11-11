# Task ID: 013
# Title: Create Location Entity with CRUD Operations
# Status: pending
# Dependencies: task-012
# Priority: high

# Description:
Build the Location entity for delivery addresses with relationship to clients and full CRUD functionality.

# Details:
- Create Location entity with address fields
- Add relationships to Tenant and Client entities
- Implement address validation and formatting
- Add optional geocoding fields (latitude/longitude)
- Create Doctrine migration for locations table
- Build CRUD API endpoints with API Platform
- Add primary location functionality
- Implement location search and filtering
- Set up proper cascade operations with clients

## Entity Fields:
- id (UUID, primary key)
- tenant (ManyToOne -> Tenant, not null)
- client (ManyToOne -> Client, not null)
- addressLine1 (string, required)
- addressLine2 (string, optional)
- city (string, required)
- state (string, required)
- postalCode (string, required)
- country (string, default 'US')
- latitude, longitude (decimal, optional)
- specialInstructions (text, optional)
- isPrimary (boolean, default false)

## API Endpoints:
- **GET /api/locations** - List all locations
- **GET /api/clients/{clientId}/locations** - List client locations
- **POST /api/locations** - Create new location
- **GET /api/locations/{id}** - Get location details
- **PATCH /api/locations/{id}** - Update location
- **DELETE /api/locations/{id}** - Delete location

## Business Rules:
- Only one primary location per client
- Location must belong to client's tenant
- Address validation for completeness
- Geocoding is optional for MVP

# Test Strategy:
- Test location creation with valid address
- Verify client-location relationship works
- Test primary location constraint (one per client)
- Verify tenant isolation for locations
- Test location updates and deletion
- Test cascade operations when client is deleted
- Verify address validation works
- Test location filtering by client