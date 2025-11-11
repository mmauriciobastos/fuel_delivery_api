# Task ID: 012
# Title: Create Client Entity with CRUD Operations
# Status: pending
# Dependencies: task-006
# Priority: high

# Description:
Build the Client entity representing fuel delivery customers with full CRUD API endpoints.

# Details:
- Create Client entity with all required fields
- Add tenant relationship (ManyToOne to Tenant)
- Implement validation constraints
- Set up relationship with Location entity (OneToMany)
- Create Doctrine migration for clients table
- Build CRUD API endpoints with API Platform
- Add proper serialization groups
- Implement soft delete functionality
- Add client search and filtering capabilities

## Entity Fields:
- id (UUID, primary key)
- tenant (ManyToOne -> Tenant, not null)
- companyName (string, required)
- contactName (string, required)
- email (string, email format)
- phone (string)
- billingAddress (embedded object)
- deliveryAddresses (OneToMany -> Location)
- isActive (boolean, default true)
- createdAt, updatedAt (datetime)

## API Endpoints:
- **GET /api/clients** - List clients with filtering
- **POST /api/clients** - Create new client
- **GET /api/clients/{id}** - Get client details
- **PATCH /api/clients/{id}** - Update client
- **DELETE /api/clients/{id}** - Soft delete client

## Validation Rules:
- Required fields validation
- Email format validation
- Phone number format (optional)
- Billing address validation

# Test Strategy:
- Test client creation with all fields
- Verify tenant isolation works correctly
- Test client update operations
- Verify soft delete functionality
- Test client search and filtering
- Test relationship with locations
- Verify validation constraints work
- Test API endpoints with different user roles