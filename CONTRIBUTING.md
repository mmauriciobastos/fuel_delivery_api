# Contributing to Fuel Delivery Management Platform

Thank you for your interest in contributing to the Fuel Delivery Management Platform! This document provides guidelines and instructions for contributing to this project.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Testing Requirements](#testing-requirements)
- [Code Quality Checks](#code-quality-checks)

## 🤝 Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on the code, not the person
- Help others learn and grow

## 🚀 Getting Started

### Prerequisites

- PHP 8.3 or higher
- PostgreSQL 16+
- Composer 2.x
- Docker & Docker Compose (recommended)
- Git

### Local Setup

1. **Fork and clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/fuel_delivery_api.git
   cd fuel_delivery_api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Setup environment**
   ```bash
   cp .env .env.local
   # Edit .env.local with your configuration
   ```

4. **Start Docker containers** (if using Docker)
   ```bash
   docker-compose up -d
   ```

5. **Setup database**
   ```bash
   bin/console doctrine:database:create
   bin/console doctrine:migrations:migrate
   ```

6. **Verify setup**
   ```bash
   composer quality
   ```

## 💻 Development Workflow

### Branch Strategy

We use **Git Flow** branching strategy:

- `main` - Production-ready code
- `develop` - Integration branch for features
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Critical production fixes
- `release/*` - Release preparation

### Creating a Feature Branch

```bash
# Start from develop
git checkout develop
git pull origin develop

# Create feature branch
git checkout -b feature/your-feature-name

# Make your changes...

# Push your branch
git push origin feature/your-feature-name
```

### Branch Naming Convention

- `feature/add-order-notifications`
- `bugfix/fix-tenant-isolation`
- `hotfix/security-jwt-validation`
- `refactor/improve-order-service`
- `docs/update-api-documentation`

## 📐 Coding Standards

### PHP Standards

- **PSR-12** code style
- **Strict types** declaration required: `declare(strict_types=1);`
- **Type hints** on all parameters and returns
- **PHPDoc** for complex logic and public APIs
- **Symfony** best practices

### Example

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;

/**
 * Service for managing order operations.
 */
final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {}

    /**
     * Creates a new order from the provided data.
     *
     * @throws ClientNotFoundException if client doesn't exist
     * @throws InsufficientCapacityException if truck capacity insufficient
     */
    public function createOrder(CreateOrderDTO $dto): Order
    {
        // Implementation...
    }
}
```

### Architecture Guidelines

#### Entities
- Use UUID primary keys
- All entities MUST have `tenant` relationship
- Implement validation with attributes
- Use enums for status fields

#### Services
- Business logic MUST be in services, not controllers
- Services are readonly when possible
- Inject dependencies via constructor
- Use DTOs for complex operations

#### Repositories
- Extend `ServiceEntityRepository`
- All queries MUST respect tenant isolation
- Use QueryBuilder, never raw SQL
- Name methods descriptively

#### Controllers
- Keep controllers thin
- Delegate to services
- Handle HTTP concerns only
- Use API Platform for CRUD operations

### Multi-Tenancy Rules

**CRITICAL**: Every entity that stores business data MUST:
- Have a `tenant` relationship
- Be filtered by tenant automatically via Doctrine filter
- Never expose cross-tenant data

```php
#[ORM\Entity]
class Order
{
    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;
    
    // ... other fields
}
```

## 📝 Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `perf`: Performance improvements
- `ci`: CI/CD changes

### Examples

```
feat(orders): add email notification on order completion

Implement email notification system that sends confirmation
emails to clients when their orders are marked as delivered.

Closes #123
```

```
fix(auth): prevent cross-tenant data access in JWT validation

Add tenant validation to JWT token processing to ensure
users can only access data from their own tenant.

Fixes #456
```

### Commit Best Practices

- Write clear, concise commit messages
- Use imperative mood ("add" not "added")
- Reference issue numbers when applicable
- Keep commits atomic and focused
- Don't commit generated files or cache

## 🔄 Pull Request Process

### Before Submitting

1. **Update from develop**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout your-feature-branch
   git merge develop
   ```

2. **Run quality checks**
   ```bash
   composer quality
   ```

3. **Verify tests pass**
   ```bash
   composer test
   ```

4. **Fix code style issues**
   ```bash
   composer cs-fix
   ```

### PR Requirements

- [ ] All tests pass
- [ ] Code follows style guidelines
- [ ] PHPStan analysis passes (level 8)
- [ ] New features have tests
- [ ] Documentation updated
- [ ] No merge conflicts
- [ ] Descriptive PR title and description
- [ ] Links to related issues

### PR Template

When creating a PR, fill out all sections of the PR template:
- Description of changes
- Type of change
- Testing performed
- Code quality checks
- Database changes (if any)
- API changes (if any)
- Security considerations
- Performance impact

### Review Process

1. At least one approval required
2. All CI checks must pass
3. Resolve all review comments
4. Keep PR updated with develop
5. Squash commits before merging (if requested)

## 🧪 Testing Requirements

### Test Coverage

- **Unit tests** for all business logic
- **Integration tests** for API endpoints
- **Minimum 80% code coverage** for new code
- **Multi-tenant isolation** tests for entities

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderServiceTest extends KernelTestCase
{
    private OrderService $orderService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->orderService = static::getContainer()->get(OrderService::class);
    }

    public function testCreateOrderWithValidData(): void
    {
        // Arrange
        $dto = new CreateOrderDTO(/* ... */);
        
        // Act
        $order = $this->orderService->createOrder($dto);
        
        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(OrderStatus::PENDING, $order->getStatus());
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Service/OrderServiceTest.php

# Run tests with coverage
composer test-coverage
```

## ✅ Code Quality Checks

### Required Tools

All code must pass these checks before merging:

#### PHPStan (Static Analysis)

```bash
# Run analysis
composer phpstan

# Fix issues and re-run
composer phpstan
```

**Level 8** - strictest analysis level

#### PHP CS Fixer (Code Style)

```bash
# Check style issues
composer cs-check

# Auto-fix style issues
composer cs-fix
```

**PSR-12** with Symfony ruleset

### Running All Checks

```bash
# Run all quality checks
composer quality

# Fix issues and run checks
composer quality-fix
```

### Pre-Commit Checklist

Before every commit:
- [ ] Code follows PSR-12 standards
- [ ] All type hints added
- [ ] PHPDoc added for public methods
- [ ] PHPStan passes
- [ ] Tests added/updated
- [ ] Tests pass locally

## 🐛 Reporting Bugs

Use the **Bug Report** issue template and include:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Environment details
- Error messages/logs
- Screenshots if applicable

## 💡 Suggesting Features

Use the **Feature Request** issue template and include:
- Problem statement
- Proposed solution
- Use cases
- Technical considerations
- API changes (if applicable)

## 📚 Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [API Platform Documentation](https://api-platform.com/docs)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [PHPStan Rules](https://phpstan.org/rules)

## ❓ Questions?

- Open a GitHub issue
- Contact the development team
- Check existing documentation

## 📄 License

By contributing, you agree that your contributions will be licensed under the same license as the project.

---

Thank you for contributing! 🎉
