# Task ID: 016
# Title: Create OrderStatusHistory Entity
# Status: pending
# Dependencies: task-015
# Priority: high

# Description:
Build the OrderStatusHistory entity to maintain an audit trail of all order status changes with timestamps and user tracking.

# Details:
- Create OrderStatusHistory entity for audit logging
- Add relationships to Order and User entities
- Implement automatic history creation on status changes
- Add Doctrine event listeners for order status changes
- Create read-only API endpoint for order history
- Set up proper indexes for performance
- Implement history retention policies
- Add history filtering and search capabilities

## Entity Fields:
- id (UUID, primary key)
- order (ManyToOne -> Order, not null)
- changedByUser (ManyToOne -> User, not null)
- fromStatus (enum, nullable for initial status)
- toStatus (enum, not null)
- notes (text, optional)
- changedAt (datetime, not null)

## API Endpoints:
- **GET /api/orders/{id}/history** - Get order status history
- **GET /api/order-status-history** - List all history (admin only)

## Event Integration:
- Doctrine entity listener on Order status changes
- Automatic history entry creation
- User context capture from security
- Timestamp automatic assignment

## Business Rules:
- History entries are immutable once created
- Every status change must be logged
- User who made change must be recorded
- Optional notes for change reason

## Performance Considerations:
- Index on order_id for fast history lookup
- Index on changed_at for chronological queries
- Consider partitioning for large datasets (future)

# Test Strategy:
- Test automatic history creation on status change
- Verify user context is captured correctly
- Test history API endpoint returns correct data
- Verify history entries are immutable
- Test history filtering and pagination
- Verify performance with large datasets
- Test event listener reliability
- Test history with different user roles