# Task ID: 014
# Title: Create Truck Entity with CRUD Operations
# Status: pending
# Dependencies: task-006
# Priority: high

# Description:
Build the Truck entity representing delivery vehicles with status management and full CRUD functionality.

# Details:
- Create Truck entity with all vehicle fields
- Add tenant relationship (ManyToOne to Tenant)
- Implement truck status enum (available, in_transit, maintenance, out_of_service)
- Add capacity management in gallons/liters
- Create unique truck number constraint within tenant
- Create Doctrine migration for trucks table
- Build CRUD API endpoints with API Platform
- Add truck availability queries
- Implement truck status update functionality

## Entity Fields:
- id (UUID, primary key)
- tenant (ManyToOne -> Tenant, not null)
- truckNumber (string, unique within tenant)
- licensePlate (string, required)
- capacity (decimal, in gallons/liters)
- status (enum: available, in_transit, maintenance, out_of_service)
- currentLocation (embedded object, optional)
- lastMaintenanceDate (date, optional)
- nextMaintenanceDate (date, optional)
- createdAt, updatedAt (datetime)

## API Endpoints:
- **GET /api/trucks** - List trucks with filtering
- **POST /api/trucks** - Create new truck
- **GET /api/trucks/{id}** - Get truck details
- **PATCH /api/trucks/{id}** - Update truck
- **GET /api/trucks/available** - List available trucks
- **PATCH /api/trucks/{id}/status** - Update truck status

## Validation Rules:
- Truck number unique within tenant
- Capacity must be positive number
- Valid status enum values
- License plate format validation

## Status Management:
- Available: Ready for new orders
- In-Transit: Currently on delivery
- Maintenance: Under repair/service
- Out-of-Service: Temporarily unavailable

# Test Strategy:
- Test truck creation with all required fields
- Verify truck number uniqueness within tenant
- Test truck status transitions
- Verify available trucks query works
- Test capacity validation (positive numbers)
- Test truck update operations
- Verify tenant isolation works correctly
- Test truck filtering and search