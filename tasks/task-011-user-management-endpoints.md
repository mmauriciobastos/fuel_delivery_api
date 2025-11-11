# Task ID: 011
# Title: Create User Management Endpoints
# Status: pending
# Dependencies: task-010
# Priority: high

# Description:
Build comprehensive user management API endpoints with proper security and validation.

# Details:
- Create user CRUD endpoints with API Platform
- Implement user creation with validation
- Add user update functionality (profile, roles, status)
- Create user listing with filtering and pagination
- Implement user deactivation (soft delete)
- Add password change/reset functionality
- Create user profile endpoint for self-management
- Implement proper serialization groups for different contexts

## Endpoints to Create:
- **GET /api/users** - List users (admin/dispatcher only)
- **POST /api/users** - Create new user (admin only)
- **GET /api/users/{id}** - Get user details
- **PATCH /api/users/{id}** - Update user (admin or self for profile)
- **DELETE /api/users/{id}** - Deactivate user (admin only)
- **GET /api/users/profile** - Current user profile
- **PATCH /api/users/profile** - Update own profile
- **POST /api/users/{id}/change-password** - Change password

## Validation Rules:
- Email uniqueness within tenant
- Strong password requirements
- Valid role assignments
- Required field validation
- Profile update permissions

## Security Features:
- Users can only see users in their tenant
- Role-based endpoint access
- Users can update own profile
- Password fields excluded from responses
- Audit logging for user changes

# Test Strategy:
- Test user creation with all required fields
- Verify email uniqueness within tenant
- Test role assignment and validation
- Verify users only see own tenant's users
- Test user profile updates
- Test password change functionality
- Verify proper error messages
- Test pagination and filtering