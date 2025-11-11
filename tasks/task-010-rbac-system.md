# Task ID: 010
# Title: Implement Role-Based Access Control (RBAC)
# Status: pending
# Dependencies: task-009
# Priority: high

# Description:
Set up comprehensive role-based access control system with proper permissions for different user types.

# Details:
- Define role hierarchy (ROLE_ADMIN, ROLE_DISPATCHER, ROLE_USER)
- Configure Symfony security voters for fine-grained permissions
- Implement access control for API endpoints
- Create custom security attributes/permissions
- Set up role inheritance if needed
- Add permission checking in controllers/services
- Create role management functionality
- Document permission matrix for all roles

## Role Definitions:
- **ROLE_ADMIN**: Full system access, user management, tenant configuration
- **ROLE_DISPATCHER**: Order management, client management, truck assignment
- **ROLE_USER**: Read-only access to assigned orders (future driver role)

## Access Control Matrix:
- Tenant management: ROLE_ADMIN only
- User management: ROLE_ADMIN only
- Client CRUD: ROLE_ADMIN, ROLE_DISPATCHER
- Truck CRUD: ROLE_ADMIN, ROLE_DISPATCHER
- Order CRUD: ROLE_ADMIN, ROLE_DISPATCHER
- Order assignment: ROLE_ADMIN, ROLE_DISPATCHER

## Implementation Components:
- Security voters for entity-level permissions
- Method-level security annotations
- API Platform access control configuration
- Custom permission checking services

# Test Strategy:
- Test each role has correct access permissions
- Verify role hierarchy works correctly
- Test access denied scenarios
- Verify API endpoints respect role restrictions
- Test cross-tenant access is blocked
- Test role assignment and modification
- Verify security voters work with complex scenarios
- Test permission inheritance