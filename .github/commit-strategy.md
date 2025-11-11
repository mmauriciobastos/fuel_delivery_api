# Git Commit Strategy - Fuel Delivery Management Platform

## Philosophy
This project follows the **atomic commits** principle: each commit represents one logical, self-contained change that can be independently reviewed, tested, and reverted if needed.

## Why Small, Atomic Commits?

### Benefits
- ✅ **Easier Code Review** - Reviewers can focus on one concept at a time
- ✅ **Clear History** - Git log tells a story of how the system evolved
- ✅ **Safer Rollbacks** - Revert specific changes without losing other work
- ✅ **Better Collaboration** - Reduces merge conflicts and confusion
- ✅ **Effective Debugging** - Use `git bisect` to pinpoint when issues were introduced
- ✅ **Documentation** - Commit messages serve as change documentation

### Anti-Pattern: Large Commits
❌ **Avoid** commits like:
```bash
git commit -m "feat: implement entire user management system"
# 47 files changed, 3,421 insertions(+), 89 deletions(-)
```

✅ **Instead**, break into:
```bash
git commit -m "feat(entity): add User entity with UUID and tenant relationship"
git commit -m "feat(repository): add UserRepository with tenant-aware queries"
git commit -m "feat(service): implement UserService with CRUD operations"
git commit -m "feat(security): add JWT authentication for User"
git commit -m "feat(api): add User API endpoints with role-based access"
git commit -m "test(user): add unit tests for User entity and service"
git commit -m "test(api): add integration tests for User endpoints"
```

## Conventional Commits Format

### Structure
```
<type>(<scope>): <short description>

[optional body - explain WHY, not WHAT]

[optional footer - reference issues, breaking changes]
```

### Types

| Type | Usage | Example |
|------|-------|---------|
| `feat` | New feature or capability | `feat(api): add Client CRUD endpoints` |
| `fix` | Bug fix | `fix(tenant): correct filter not applying to nested relations` |
| `refactor` | Code restructuring (no behavior change) | `refactor(order): extract validation logic to OrderValidator` |
| `test` | Add or update tests | `test(client): add integration tests for Client API` |
| `docs` | Documentation only | `docs(api): document authentication flow` |
| `chore` | Maintenance, dependencies, tooling | `chore(deps): update Symfony to 7.1.5` |
| `style` | Code style, formatting (no logic change) | `style(entity): apply PHP-CS-Fixer formatting` |
| `perf` | Performance improvements | `perf(query): add database index on tenant_id` |
| `ci` | CI/CD pipeline changes | `ci: add PHPStan static analysis to workflow` |
| `build` | Build system changes | `build(docker): optimize Dockerfile for faster builds` |

### Scopes

Scopes identify the area of change:

**Entity-Level:**
- `entity` - Entity classes
- `repository` - Repository classes
- `service` - Service layer
- `api` - API Platform resources
- `security` - Authentication/Authorization
- `validation` - Validators and constraints

**Feature-Level:**
- `tenant` - Tenant management
- `user` - User management
- `client` - Client management
- `location` - Location management
- `truck` - Truck management
- `order` - Order management
- `auth` - Authentication/JWT

**Infrastructure:**
- `config` - Configuration files
- `docker` - Docker/containerization
- `deps` - Dependencies
- `migration` - Database migrations

### Examples by Task Type

#### Creating an Entity
```bash
# Commit 1: Entity class
feat(entity): add Client entity with UUID and tenant relationship

- Add Client entity with id, tenant, companyName, email fields
- Use UUID v4 for primary key
- Implement ManyToOne relationship with Tenant
- Add lifecycle callbacks for timestamps

# Commit 2: Repository
feat(repository): add ClientRepository with tenant-aware queries

- Add findByIdAndTenant method
- Add findActiveClientsForTenant method
- All queries respect tenant filter

# Commit 3: Validation
feat(validation): add Client entity validation constraints

- Email must be valid format
- Company name required, 2-255 chars
- Email unique within tenant scope
```

#### Creating a Service
```bash
feat(service): implement ClientService with CRUD operations

- Add createClient with DTO
- Add updateClient with validation
- Add deleteClient (soft delete via isActive)
- Inject ClientRepository and TenantContext
- Dispatch ClientCreatedEvent on creation
```

#### Adding API Endpoints
```bash
feat(api): add Client API resource with CRUD operations

- Configure GetCollection, Post, Get, Patch, Delete operations
- Define normalization groups (client:read)
- Define denormalization groups (client:write)
- Add security expressions for role-based access
```

#### Adding Tests
```bash
# Unit tests
test(client): add unit tests for Client entity validation

- Test required field validation
- Test email format validation
- Test tenant relationship requirement
- Test timestamps auto-population

# Integration tests
test(api): add integration tests for Client endpoints

- Test GET /api/clients returns tenant-filtered results
- Test POST /api/clients creates client with current tenant
- Test PATCH /api/clients updates only own tenant data
- Test DELETE /api/clients soft deletes client
- Test cross-tenant access is denied
```

#### Fixing Bugs
```bash
fix(tenant): correct Doctrine filter not applying to Order.client relation

- Add filter annotation to Client relationship
- Update TenantFilter to handle nested associations
- Add test to verify filter applies to eager-loaded relations

Fixes #42
```

#### Refactoring
```bash
refactor(order): extract status transition logic to OrderStateMachine

- Create OrderStateMachine service
- Move transition validation from OrderService
- Improve testability and reusability
- No behavior changes
```

## When to Commit - Detailed Guide

### 1. Entity + Repository (2 commits)
After creating entity class:
```bash
git add src/Entity/Client.php
git commit -m "feat(entity): add Client entity with UUID and tenant relationship"
```

After creating repository:
```bash
git add src/Repository/ClientRepository.php
git commit -m "feat(repository): add ClientRepository with tenant-aware queries"
```

### 2. Service Layer (1 commit)
After implementing service with business logic:
```bash
git add src/Service/ClientService.php
git commit -m "feat(service): implement ClientService with CRUD operations"
```

### 3. API Configuration (1 commit)
After adding API Platform attributes and resources:
```bash
git add src/Entity/Client.php src/ApiResource/
git commit -m "feat(api): configure Client API resource with CRUD endpoints"
```

### 4. Custom Processors/Providers (1 commit each)
```bash
git add src/State/CreateClientProcessor.php
git commit -m "feat(api): add CreateClientProcessor for custom creation logic"
```

### 5. Security/Voters (1 commit)
```bash
git add src/Security/Voter/ClientVoter.php
git commit -m "feat(security): add ClientVoter for tenant-scoped access control"
```

### 6. DTOs (1 commit)
```bash
git add src/Dto/CreateClientDTO.php src/Dto/UpdateClientDTO.php
git commit -m "feat(dto): add Client DTOs for request/response handling"
```

### 7. Events + Listeners (2 commits)
```bash
# Event
git add src/Event/ClientCreatedEvent.php
git commit -m "feat(event): add ClientCreatedEvent for client lifecycle"

# Listener
git add src/EventListener/ClientCreatedListener.php
git commit -m "feat(listener): add ClientCreatedListener for notification handling"
```

### 8. Validation (1 commit)
```bash
git add src/Validator/ src/Entity/Client.php
git commit -m "feat(validation): add custom validators for Client entity"
```

### 9. Tests (1-2 commits)
```bash
# Unit tests
git add tests/Unit/Entity/ClientTest.php tests/Unit/Service/ClientServiceTest.php
git commit -m "test(client): add unit tests for Client entity and service"

# Integration tests
git add tests/Integration/Api/ClientApiTest.php
git commit -m "test(api): add integration tests for Client endpoints"
```

### 10. Migrations (1 commit)
```bash
git add migrations/Version20251110123456.php
git commit -m "feat(migration): add Client table migration"
```

### 11. Configuration (1 commit)
```bash
git add config/packages/api_platform.yaml config/services.yaml
git commit -m "chore(config): configure Client API serialization and services"
```

### 12. Documentation (1 commit)
```bash
git add docs/api/client-endpoints.md
git commit -m "docs(api): document Client API endpoints and usage"
```

## Task Breakdown Example

### Task: "Implement Order Entity with CRUD Operations"

**Planned Commits:**
1. `feat(entity): add Order entity with UUID, tenant, and status fields`
2. `feat(enum): add OrderStatus enum with state transitions`
3. `feat(repository): add OrderRepository with status filtering methods`
4. `feat(service): implement OrderService with creation workflow`
5. `feat(service): add order assignment and status transition logic`
6. `feat(api): configure Order API resource with CRUD operations`
7. `feat(security): add OrderVoter for role-based access control`
8. `feat(event): add OrderCreatedEvent and OrderAssignedEvent`
9. `feat(listener): add OrderEventListener for status change notifications`
10. `test(order): add unit tests for Order entity and OrderService`
11. `test(api): add integration tests for Order API endpoints`
12. `feat(migration): add Order table migration`

**Workflow:**
```bash
# Step 1: Create entity
# ... work on Order.php ...
git add src/Entity/Order.php
git commit -m "feat(entity): add Order entity with UUID, tenant, and status fields"

# Step 2: Create enum
# ... work on OrderStatus.php ...
git add src/Enum/OrderStatus.php
git commit -m "feat(enum): add OrderStatus enum with state transitions"

# Step 3: Create repository
# ... work on OrderRepository.php ...
git add src/Repository/OrderRepository.php
git commit -m "feat(repository): add OrderRepository with status filtering methods"

# ... continue for each step ...
```

## Pre-Commit Checklist

Before running `git commit`, verify:

- [ ] **Code Quality**
  - [ ] No syntax errors
  - [ ] Follows PSR-12 coding standards
  - [ ] PHPStan passes (if configured)
  - [ ] PHP-CS-Fixer applied (if configured)

- [ ] **Functionality**
  - [ ] Code runs without errors
  - [ ] New tests pass
  - [ ] Existing tests still pass
  - [ ] Manual testing completed (if applicable)

- [ ] **Scope**
  - [ ] Only related files included
  - [ ] No debug code left behind
  - [ ] No commented-out code
  - [ ] No unintended changes

- [ ] **Commit Message**
  - [ ] Follows conventional format
  - [ ] Type and scope are accurate
  - [ ] Description is clear and concise
  - [ ] Body explains WHY (if needed)

## Branch Strategy

### Branch Naming
```
feature/task-###-short-description
fix/issue-description
refactor/component-name
chore/maintenance-task
```

**Examples:**
- `feature/task-005-tenant-entity`
- `feature/task-012-client-crud`
- `fix/order-status-validation`
- `refactor/order-service-extraction`
- `chore/update-dependencies`

### Workflow
1. **Create feature branch from `main`**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feature/task-012-client-crud
   ```

2. **Make atomic commits as you work**
   ```bash
   # ... work on entity ...
   git add src/Entity/Client.php
   git commit -m "feat(entity): add Client entity with UUID and tenant relationship"
   
   # ... work on repository ...
   git add src/Repository/ClientRepository.php
   git commit -m "feat(repository): add ClientRepository with tenant-aware queries"
   ```

3. **Push to remote regularly**
   ```bash
   git push -u origin feature/task-012-client-crud
   ```

4. **Create Pull Request**
   - PR title: "Task 012: Implement Client Entity and CRUD Operations"
   - Description: Reference task file, list completed features
   - Link related issues

5. **After PR approval, merge to `main`**
   ```bash
   # Use squash merge if commits are too granular
   # OR keep atomic commits if they're well-structured
   ```

## Git Commands Quick Reference

### Making Commits
```bash
# Stage specific files
git add src/Entity/Client.php src/Repository/ClientRepository.php

# Commit with message
git commit -m "feat(entity): add Client entity with UUID and tenant relationship"

# Amend last commit (if not pushed yet)
git commit --amend -m "feat(entity): add Client entity with tenant and validation"

# Stage all changes (use carefully)
git add .
```

### Reviewing Before Commit
```bash
# See what's changed
git status

# See diff of unstaged changes
git diff

# See diff of staged changes
git diff --cached

# See file changes only
git status --short
```

### Splitting Changes
```bash
# Interactive staging (choose specific hunks)
git add -p src/Entity/Client.php

# Stage specific lines interactively
git add -i
```

### Commit History
```bash
# View commit history
git log --oneline

# View with graph
git log --oneline --graph --all

# View commits for specific file
git log --oneline -- src/Entity/Client.php
```

## Common Scenarios

### Scenario 1: Forgot to Commit Separately
**Problem:** Made changes to entity AND repository, want separate commits.

**Solution:**
```bash
# Stage entity only
git add src/Entity/Client.php
git commit -m "feat(entity): add Client entity with UUID and tenant relationship"

# Stage repository
git add src/Repository/ClientRepository.php
git commit -m "feat(repository): add ClientRepository with tenant-aware queries"
```

### Scenario 2: Need to Split One File's Changes
**Problem:** Made multiple unrelated changes in one file.

**Solution:**
```bash
# Use interactive staging
git add -p src/Entity/Client.php

# Choose 'y' for hunks belonging to first commit
# Choose 'n' for hunks belonging to second commit

git commit -m "feat(entity): add Client entity base fields"

# Add remaining changes
git add src/Entity/Client.php
git commit -m "feat(validation): add validation constraints to Client"
```

### Scenario 3: Committed Too Early
**Problem:** Committed but forgot to add a file.

**Solution:**
```bash
# Add forgotten file
git add src/Repository/ClientRepository.php

# Amend previous commit (ONLY if not pushed!)
git commit --amend --no-edit
```

### Scenario 4: Wrong Commit Message
**Problem:** Made typo in commit message.

**Solution:**
```bash
# Fix last commit message (ONLY if not pushed!)
git commit --amend -m "feat(entity): add Client entity with UUID and tenant relationship"
```

## Best Practices Summary

1. ✅ **Commit early, commit often** - Small commits are better than large ones
2. ✅ **One concept per commit** - Each commit should do one thing well
3. ✅ **Write clear messages** - Future you will thank present you
4. ✅ **Test before committing** - Ensure code works
5. ✅ **Review before pushing** - Use `git diff --cached` to review staged changes
6. ✅ **Use conventional format** - Makes history scannable and automatable
7. ✅ **Keep commits focused** - Avoid mixing refactoring with new features
8. ✅ **Document the WHY** - Code shows WHAT, commit message explains WHY

## Anti-Patterns to Avoid

- ❌ **Giant commits** - "feat: implement entire order management system"
- ❌ **Vague messages** - "update stuff", "fix bug", "changes"
- ❌ **Mixed concerns** - Entity + service + tests + migration in one commit
- ❌ **WIP commits** - Don't commit broken/incomplete code
- ❌ **Debugging artifacts** - Remove console.logs, var_dumps before commit
- ❌ **Formatting-only changes** - Mixed with logic changes

## Tools & Automation

### Git Hooks (Optional)
Consider adding pre-commit hooks:
```bash
# .git/hooks/pre-commit
#!/bin/bash

# Run PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff

# Run PHPStan
vendor/bin/phpstan analyze

# Run tests
vendor/bin/phpunit
```

### Commit Message Validation (Optional)
Use tools like `commitlint` to enforce conventional commits format.

## References
- [Conventional Commits Specification](https://www.conventionalcommits.org/)
- [How to Write a Git Commit Message](https://chris.beams.io/posts/git-commit/)
- [Atomic Commits](https://www.freshconsulting.com/insights/blog/atomic-commits/)

---

**Remember:** Good commit practices are an investment in the project's future. They make code review faster, debugging easier, and collaboration smoother. Take the extra minute to craft good commits - it pays dividends later.
