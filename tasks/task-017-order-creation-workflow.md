# Task ID: 017
# Title: Implement Order Creation Workflow
# Status: pending
# Dependencies: task-016
# Priority: high

# Description:
Build comprehensive order creation workflow with validation, business rules, and proper error handling.

# Details:
- Create order creation service with business logic
- Implement order validation (client exists, location belongs to client, etc.)
- Add fuel quantity and delivery date validation
- Create order number generation service
- Implement proper error handling and user feedback
- Add order creation API endpoint with validation
- Set up automatic status history entry creation
- Implement transaction management for data consistency

## Order Creation Service:
- Validate client belongs to current tenant
- Verify delivery location belongs to selected client
- Check fuel quantity is within reasonable limits
- Validate requested delivery date (not in past, business rules)
- Generate unique order number
- Set initial status to "pending"
- Create initial status history entry

## Validation Rules:
- Client must exist and belong to current tenant
- Location must belong to selected client
- Fuel quantity: 1-10,000 gallons/liters
- Delivery date: minimum 24 hours in advance (configurable)
- Required fields validation

## API Integration:
- POST /api/orders endpoint validation
- Proper error responses with field-specific messages
- Success response with created order details
- Integration with authentication/authorization

## Error Handling:
- Client not found or wrong tenant
- Location doesn't belong to client
- Invalid fuel quantity or date
- Database constraint violations
- Transaction rollback on errors

# Test Strategy:
- Test successful order creation end-to-end
- Test validation failures for each rule
- Verify client-location relationship validation
- Test fuel quantity boundary conditions
- Test delivery date validation rules
- Verify order number generation uniqueness
- Test transaction rollback on failures
- Test concurrent order creation