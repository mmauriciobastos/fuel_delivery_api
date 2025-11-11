# Task ID: 007
# Title: Create User Entity with Tenant Relationship
# Status: pending
# Dependencies: task-006
# Priority: high

# Description:
Build the User entity with proper tenant relationship and prepare for authentication system integration.

# Details:
- Create User entity implementing UserInterface
- Add tenant relationship (ManyToOne to Tenant)
- Implement all required fields (email, password, names, roles, etc.)
- Add validation constraints (email format, unique email per tenant)
- Set up password hashing configuration
- Create role system (ROLE_ADMIN, ROLE_DISPATCHER, etc.)
- Add user status management (active/inactive)
- Create Doctrine migration for users table
- Set up proper indexes for performance and uniqueness

## Entity Fields:
- id (UUID, primary key)
- tenant (ManyToOne -> Tenant, not null)
- email (string, unique within tenant)
- password (hashed string)
- firstName, lastName (strings)
- roles (array of strings)
- isActive (boolean, default true)
- createdAt, lastLoginAt (datetime)

## Validation Rules:
- Email format validation
- Unique constraint: tenant_id + email
- Password requirements (will be set by security config)
- Required fields validation

# Test Strategy:
- Create users and verify tenant relationship
- Test unique email constraint within tenant scope
- Verify password hashing works correctly
- Test role assignment and validation
- Verify user can belong to only one tenant
- Test user activation/deactivation
- Run migration and verify database schema
- Test user entity with Symfony security system