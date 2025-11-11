# Task ID: 002
# Title: Configure Docker Development Environment
# Status: pending
# Dependencies: task-001
# Priority: high

# Description:
Set up Docker Compose environment with PHP 8.3, PostgreSQL 16, Redis, and Nginx for local development.

# Details:
- Create Dockerfile for PHP 8.3-FPM with required extensions
- Configure docker-compose.yml with services: app, database, cache, webserver
- Set up PostgreSQL 16 container with persistent volume
- Configure Redis container for caching and JWT blacklist
- Set up Nginx container with proper PHP-FPM configuration
- Create environment-specific configuration files
- Set up volume mounts for development

## Services Configuration:
- **app**: PHP 8.3-FPM with Symfony requirements
- **database**: PostgreSQL 16 with fuel_delivery database
- **cache**: Redis latest stable
- **webserver**: Nginx with API Platform configuration

## Required Extensions:
- pdo_pgsql, intl, opcache, redis, zip, curl, mbstring

# Test Strategy:
- Verify all containers start successfully
- Test database connection from app container
- Verify Redis connection
- Access application through Nginx (localhost:8080)
- Run Symfony console commands inside container
- Test hot-reload during development