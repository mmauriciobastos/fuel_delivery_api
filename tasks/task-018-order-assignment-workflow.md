# Task ID: 018
# Title: Implement Order Assignment to Trucks
# Status: pending
# Dependencies: task-017
# Priority: high

# Description:
Build order assignment workflow to assign pending orders to available trucks with capacity validation and business rules.

# Details:
- Create order assignment service with business logic
- Implement truck availability checking
- Add truck capacity validation against order quantity
- Create order assignment API endpoint
- Implement status transition from "pending" to "assigned"
- Add automatic status history logging
- Implement truck status management during assignment
- Add order assignment validation and error handling

## Order Assignment Service:
- Validate order is in "pending" status
- Check truck exists and belongs to current tenant
- Verify truck is in "available" status
- Validate truck capacity vs order quantity
- Update order with truck assignment
- Change order status to "assigned"
- Update truck status to "in_transit"
- Create status history entry

## API Endpoint:
- **PATCH /api/orders/{id}/assign**
- Input: truck_id
- Validates business rules before assignment
- Returns updated order with truck details
- Proper error responses for violations

## Business Rules:
- Only "pending" orders can be assigned
- Truck must be "available" status
- Truck capacity >= order quantity
- Truck must belong to same tenant as order
- Only one order per truck at a time (for MVP)

## Validation & Error Handling:
- Order not found or wrong status
- Truck not found or unavailable
- Insufficient truck capacity
- Cross-tenant assignment prevention
- Concurrent assignment prevention

# Test Strategy:
- Test successful order assignment workflow
- Test assignment of unavailable trucks (should fail)
- Test capacity validation (over-capacity should fail)
- Test cross-tenant assignment prevention
- Test order status transitions work correctly
- Test truck status updates correctly
- Test concurrent assignment scenarios
- Verify status history is created automatically