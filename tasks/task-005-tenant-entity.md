# Task ID: 005
# Title: Create Tenant Entity and Multi-Tenancy Foundation
# Status: completed
# Dependencies: task-003
# Priority: high

# Description:
Implement the core Tenant entity and establish the multi-tenancy foundation that all other entities will depend on.

# Details:
- Create Tenant entity with all required fields (id, name, subdomain, status, timestamps)
- Implement UUID primary key strategy
- Add validation constraints (unique subdomain, required fields)
- Create Doctrine migration for tenant table
- Set up tenant status enum (active, suspended, trial)
- Add proper indexes for performance
- Create basic Tenant repository with common queries
- Add API Platform resource configuration

## Entity Fields:
- id (UUID, primary key)
- name (string, not null)
- subdomain (string, unique, not null) 
- status (enum: active/suspended/trial)
- createdAt (datetime)
- updatedAt (datetime)

## Database Considerations:
- Unique index on subdomain
- Index on status for filtering
- Timestamps with automatic updates

# Test Strategy:
- Create tenant entities and verify field validation
- Test unique subdomain constraint
- Verify UUID generation and assignment
- Test enum status values and validation
- Run migration and verify database schema
- Test basic CRUD operations through repository
- Verify API Platform exposes endpoints correctly