# Task ID: 003
# Title: Configure PostgreSQL Database and Doctrine
# Status: pending
# Dependencies: task-002
# Priority: high

# Description:
Set up PostgreSQL database connection, configure Doctrine ORM, and establish database migration system.

# Details:
- Configure DATABASE_URL in .env files
- Set up Doctrine configuration for PostgreSQL
- Configure doctrine.yaml with proper settings
- Set up UUID strategy for primary keys
- Configure timezone handling
- Set up migrations directory and configuration
- Create initial database schema
- Configure connection pooling and optimization settings

## Configuration Requirements:
- PostgreSQL 16 connection
- UUID v4 for all primary keys
- UTC timezone handling
- Migration versioning system
- Proper indexing strategy preparation

## Files to Configure:
- config/packages/doctrine.yaml
- .env and .env.local
- migrations/ directory structure

# Test Strategy:
- Verify database connection with `doctrine:database:create`
- Test migration system with dummy migration
- Verify UUID generation works
- Test database queries through Symfony console
- Confirm proper timezone handling
- Run `doctrine:schema:validate` successfully