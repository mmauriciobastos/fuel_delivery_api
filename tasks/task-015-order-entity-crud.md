# Task ID: 015
# Title: Create Order Entity with CRUD Operations
# Status: pending
# Dependencies: task-013, task-014
# Priority: high

# Description:
Build the core Order entity representing fuel delivery orders with relationships to clients, locations, and trucks.

# Details:
- Create Order entity with all order fields
- Add relationships to Tenant, Client, Location, Truck, and User entities
- Implement order status enum (pending, assigned, in_transit, delivered, cancelled)
- Add automatic order number generation
- Create Doctrine migration for orders table
- Build CRUD API endpoints with API Platform
- Add order validation business rules
- Implement proper serialization for nested relationships

## Entity Fields:
- id (UUID, primary key)
- tenant (ManyToOne -> Tenant, not null)
- orderNumber (string, auto-generated, unique within tenant)
- client (ManyToOne -> Client, not null)
- deliveryLocation (ManyToOne -> Location, not null)
- truck (ManyToOne -> Truck, nullable)
- createdByUser (ManyToOne -> User, not null)
- fuelQuantity (decimal, required)
- fuelUnit (enum: gallons, liters)
- requestedDeliveryDate (datetime, required)
- actualDeliveryDate (datetime, nullable)
- status (enum: pending, assigned, in_transit, delivered, cancelled)
- notes (text, optional)
- createdAt, updatedAt (datetime)

## API Endpoints:
- **GET /api/orders** - List orders with filtering and pagination
- **POST /api/orders** - Create new order
- **GET /api/orders/{id}** - Get order details
- **PATCH /api/orders/{id}** - Update order
- **DELETE /api/orders/{id}** - Cancel order

## Business Rules:
- Order number auto-generation (ORD-YYYY-####)
- Delivery location must belong to selected client
- Fuel quantity must be positive
- Only pending orders can be modified
- Created by user must be authenticated user

# Test Strategy:
- Test order creation with valid data
- Verify order number generation works
- Test client-location relationship validation
- Verify fuel quantity validation
- Test order status enum values
- Test order updates and constraints
- Verify tenant isolation works
- Test order filtering and pagination