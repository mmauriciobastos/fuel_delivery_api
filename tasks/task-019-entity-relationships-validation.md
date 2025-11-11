# Task ID: 019
# Title: Setup Entity Relationships and Validation
# Status: pending
# Dependencies: task-015
# Priority: high

# Description:
Configure and validate all entity relationships, foreign key constraints, and cross-entity validation rules.

# Details:
- Set up proper Doctrine relationships between all entities
- Configure cascade operations (persist, remove, etc.)
- Add foreign key constraints and referential integrity
- Implement cross-entity validation rules
- Set up proper entity loading strategies (lazy/eager)
- Configure orphan removal where appropriate
- Add database-level constraints and indexes
- Test all relationship operations thoroughly

## Key Relationships:
- **Tenant -> User** (OneToMany)
- **Tenant -> Client** (OneToMany) 
- **Tenant -> Truck** (OneToMany)
- **Tenant -> Order** (OneToMany)
- **Client -> Location** (OneToMany)
- **Client -> Order** (OneToMany)
- **Location -> Order** (OneToMany)
- **Truck -> Order** (OneToMany)
- **User -> Order** (OneToMany, as creator)
- **Order -> OrderStatusHistory** (OneToMany)

## Cascade Operations:
- Client deletion -> soft delete related locations
- Order deletion -> keep history entries (audit trail)
- Tenant deletion -> cascade to all related entities
- User deactivation -> preserve order history

## Validation Rules:
- Location must belong to same tenant as client
- Order delivery location must belong to order's client
- Truck assignment must be same tenant as order
- User creating order must belong to same tenant

## Performance Optimization:
- Lazy loading for large collections
- Eager loading for frequently accessed relationships
- Proper indexing for foreign keys
- Query optimization for common access patterns

# Test Strategy:
- Test all relationship CRUD operations
- Verify cascade operations work correctly
- Test referential integrity constraints
- Test cross-entity validation rules
- Verify lazy/eager loading works as expected
- Test orphan removal functionality
- Test relationship queries for performance
- Verify tenant isolation across all relationships