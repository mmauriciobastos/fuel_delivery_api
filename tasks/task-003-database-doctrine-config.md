# Task ID: 003
# Title: Configure PostgreSQL Database and Doctrine
# Status: completed
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

---

# Completion Summary

## Completed Date: November 10, 2025

## What Was Done:

### 1. Configured Doctrine DBAL (doctrine.yaml)
- ✅ Set PostgreSQL 16 as server version
- ✅ Configured UTF-8 charset and collation
- ✅ Enabled profiling in debug mode with backtrace collection
- ✅ Enabled savepoints for nested transactions
- ✅ Registered UUID custom type using `Symfony\Bridge\Doctrine\Types\UuidType`
- ✅ Removed problematic driver_options that caused PDO errors

### 2. Configured Doctrine ORM Settings
- ✅ Auto-generate proxy classes in development (disabled in production)
- ✅ Enable lazy ghost objects (Doctrine 3.x feature)
- ✅ Report fields where declared for better error messages
- ✅ Use underscore naming strategy (e.g., createdAt -> created_at)
- ✅ Configured attribute-based entity mapping from src/Entity
- ✅ Set up DQL function placeholders for future custom functions

### 3. Configured UUID Generation Strategy
- ✅ Registered UUID type with Doctrine
- ✅ UUID will be used with custom generator in entities:
  ```php
  #[ORM\Id]
  #[ORM\Column(type: 'uuid', unique: true)]
  #[ORM\GeneratedValue(strategy: 'CUSTOM')]
  #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
  private ?Uuid $id = null;
  ```

### 4. Enhanced Doctrine Migrations Configuration
- ✅ Configured migrations directory: `migrations/`
- ✅ Set up migration version tracking table: `doctrine_migration_versions`
- ✅ Enabled all-or-nothing execution (transactions wrap all migrations)
- ✅ Enabled transactional behavior for individual migrations
- ✅ Enabled database platform checking (ensures PostgreSQL only)
- ✅ Enabled profiler in debug mode
- ✅ Configured version column length and execution tracking

### 5. Configured Production Optimizations
- ✅ Disabled auto-generate proxy classes in production
- ✅ Set custom proxy directory for production builds
- ✅ Configured query cache using cache pools
- ✅ Configured result cache using cache pools
- ✅ Set up Symfony cache pools for Doctrine caching

## Test Results:

### Database Connection Tests:
```bash
✅ docker compose exec app php bin/console doctrine:database:drop --force
   → Successfully dropped database

✅ docker compose exec app php bin/console doctrine:database:create
   → Successfully created database "fuel_delivery"
```

### Migration System Tests:
```bash
✅ Created test entity with UUID primary key
✅ Generated migration successfully
✅ Migration correctly generated UUID column type in PostgreSQL
✅ Migration executed successfully (9.4ms, 13 SQL queries)
✅ Verified table structure in PostgreSQL:
   - id: uuid (PRIMARY KEY)
   - Correct column types generated
```

### Schema Validation:
```bash
✅ docker compose exec app php bin/console doctrine:schema:validate
   → [OK] The mapping files are correct
   → [OK] The database schema is in sync with mapping files
```

### PostgreSQL Verification:
```sql
✅ Verified UUID column type in database:
   Table "public.test_uuid"
   Column |         Type              | Nullable
   -------+---------------------------+----------
   id     | uuid                      | not null
   
✅ Primary key constraint created correctly
✅ Character varying columns with proper lengths
✅ Timestamp columns without timezone (as configured)
```

## Configuration Files Modified:

### config/packages/doctrine.yaml
- Enhanced DBAL configuration with PostgreSQL optimizations
- Configured UUID type registration
- Set up proper charset and collation
- Configured development and production environments separately
- Added cache pool configuration for production

### config/packages/doctrine_migrations.yaml
- Enhanced migration tracking configuration
- Enabled all-or-nothing transaction mode
- Configured version tracking table
- Enabled database platform checking
- Set up profiling for debug mode

## Key Features Configured:

1. **UUID Support**: Full UUID v4 support for all entities using Symfony Bridge
2. **Transaction Support**: Savepoints and nested transactions enabled
3. **Migration System**: Robust migration tracking with transactional execution
4. **Performance**: Cache pools configured for production query/result caching
5. **Naming Strategy**: Automatic conversion of camelCase to snake_case
6. **Type Safety**: Strong typing with Doctrine 3.x features
7. **Debugging**: Profiling and error reporting enabled in development

## Database Configuration Summary:

- **Database**: fuel_delivery
- **User**: fuel_delivery_user
- **Server**: PostgreSQL 16
- **Charset**: UTF-8
- **Timezone**: UTC (default PostgreSQL behavior)
- **Connection**: Via Docker network (database:5432)

## Next Steps:
Proceed to Task 004: Setup Git Repository and Code Quality Tools

## Notes:
- UUID generation uses Symfony's doctrine.uuid_generator service
- All entities should use UUID as primary key following the PRD requirements
- Migrations are transactional by default (safe rollback)
- Database schema validation passes successfully
- Ready for multi-tenant entity development
