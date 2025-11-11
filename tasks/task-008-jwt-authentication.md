# Task ID: 008
# Title: Install and Configure JWT Authentication
# Status: pending
# Dependencies: task-007
# Priority: high

# Description:
Set up JWT-based authentication system using LexikJWTAuthenticationBundle with proper security configuration.

# Details:
- Install LexikJWTAuthenticationBundle
- Generate RSA key pair for JWT signing
- Configure JWT authentication in security.yaml
- Set up JWT token TTL (15 min access, 7 day refresh)
- Configure JWT payload to include tenant information
- Set up refresh token mechanism
- Configure CORS for API access
- Create custom JWT token authenticator if needed
- Set up token blacklist system using Redis

## Security Configuration:
- JWT access token: 15 minutes TTL
- JWT refresh token: 7 days TTL
- RSA256 algorithm for signing
- Custom claims: tenant_id, roles
- Automatic token refresh workflow

## Required Packages:
- lexik/jwt-authentication-bundle
- symfony/security-bundle (already installed)

## Configuration Files:
- config/packages/security.yaml
- config/packages/lexik_jwt_authentication.yaml
- config/jwt/ (key storage)

# Test Strategy:
- Generate and verify JWT keys work
- Test JWT token creation and validation
- Verify token contains correct user/tenant data
- Test token expiration and refresh mechanism
- Verify CORS configuration works
- Test authentication failure scenarios
- Ensure tokens are properly signed and validated
- Test token blacklist functionality