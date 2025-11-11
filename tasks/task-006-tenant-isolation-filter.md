# Task ID: 006
# Title: Implement Doctrine Filter for Tenant Isolation
# Status: completed
# Dependencies: task-005
# Priority: high

# Description:
Create Doctrine filter system to automatically isolate tenant data and prevent cross-tenant data access.

# Details:
- Create custom Doctrine filter class for tenant isolation
- Configure filter to automatically add tenant_id WHERE clauses
- Register filter globally in Doctrine configuration
- Create tenant context service to manage current tenant
- Implement filter enabling/disabling mechanism
- Add exception handling for missing tenant context
- Create base repository class that respects tenant filtering
- Ensure filter works with all query types (DQL, QueryBuilder, native)

## Components to Build:
- `TenantFilter` class extending Doctrine's SQLFilter
- `TenantContext` service for managing current tenant
- `BaseTenantRepository` for common tenant-aware queries
- Configuration in doctrine.yaml
- Event subscriber for automatic filter activation

## Security Considerations:
- Filter must be enabled by default
- No way to bypass filter without explicit admin permission
- Fail-safe behavior when tenant context is missing
- Audit logging for filter disable operations

# Test Strategy:
- Create multiple tenants and verify data isolation
- Test queries with different tenant contexts
- Verify filter prevents cross-tenant data access
- Test filter with complex joins and relationships
- Verify performance impact is minimal
- Test error handling when tenant context is missing
- Create integration tests for multi-tenant scenarios