# GitHub Copilot Instructions - Fuel Delivery Management Platform

## Project Overview
You are working on a **multi-tenant SaaS backend API** for fuel delivery companies built with **Symfony 7.x** and **API Platform 4.x**. This system manages fuel delivery operations including orders, clients, trucks, and users with complete tenant isolation.

## Architecture Principles

### Core Framework Stack
- **Framework**: Symfony 7.x with API Platform 4.x
- **Language**: PHP 8.3+
- **Database**: PostgreSQL 16+ with Doctrine ORM
- **Authentication**: JWT tokens via LexikJWTAuthenticationBundle
- **API Style**: RESTful with JSON:API format

### Multi-Tenancy Requirements
- **ALL entities MUST have a `tenant` relationship** (ManyToOne to Tenant entity)
- **ALWAYS use Doctrine filters** for tenant isolation - never raw queries
- **Every API endpoint MUST respect tenant boundaries**
- **Use UUID v4 for all primary keys**
- **Tenant context MUST be available in all services**

```php
// ✅ Correct: Every entity has tenant relationship
#[ORM\Entity]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;
    
    // ... other fields
}
```

## Code Generation Guidelines

### Entity Development
- **Primary Keys**: Always use UUID with `UuidGenerator::class`
- **Relationships**: All entities MUST relate to Tenant
- **Timestamps**: Use `createdAt` and `updatedAt` with `HasLifecycleCallbacks`
- **Soft Deletes**: Use `isActive` boolean field, not true soft delete
- **Validation**: Use Symfony validation annotations extensively

```php
#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Patch(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['client:read']],
    denormalizationContext: ['groups' => ['client:write']]
)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(['client:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['client:read', 'client:write'])]
    private ?string $companyName = null;
}
```

### Service Layer Pattern
- **Business logic MUST be in services**, not controllers
- **Services MUST inject repositories via constructor**
- **Use DTOs for complex operations**
- **Always validate business rules in services**

```php
#[Service]
readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ClientRepository $clientRepository,
        private TruckRepository $truckRepository,
        private TenantContext $tenantContext,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function createOrder(CreateOrderDTO $dto): Order
    {
        $client = $this->clientRepository->findByIdAndTenant(
            $dto->clientId, 
            $this->tenantContext->getCurrentTenant()
        );
        
        if (!$client) {
            throw new ClientNotFoundException();
        }

        $order = new Order();
        $order->setTenant($this->tenantContext->getCurrentTenant());
        $order->setClient($client);
        // ... set other fields
        
        $this->eventDispatcher->dispatch(new OrderCreatedEvent($order));
        
        return $this->orderRepository->save($order);
    }
}
```

### Repository Pattern
- **Extend `ServiceEntityRepository`**
- **ALL queries MUST be tenant-aware** (filter handles this automatically)
- **Use QueryBuilder for complex queries**
- **Never use native SQL queries**

```php
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findPendingOrdersForClient(Client $client): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.client = :client')
            ->andWhere('o.status = :status')
            ->setParameter('client', $client)
            ->setParameter('status', OrderStatus::PENDING)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

### API Platform Configuration
- **Use attributes for configuration**, not YAML
- **Always define normalization/denormalization groups**
- **Implement custom providers/processors for complex logic**
- **Use security expressions for access control**

```php
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_DISPATCHER')"),
        new Post(
            security: "is_granted('ROLE_DISPATCHER')", 
            processor: CreateOrderProcessor::class
        ),
        new Get(security: "is_granted('ROLE_DISPATCHER')"),
        new Patch(
            security: "is_granted('ROLE_DISPATCHER') and object.getTenant() == user.getTenant()"
        )
    ],
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']]
)]
```

### Security & Authentication
- **All endpoints require authentication** except auth endpoints
- **Use role hierarchy**: `ROLE_ADMIN > ROLE_DISPATCHER > ROLE_USER`
- **Implement security voters for complex permissions**
- **JWT tokens MUST include tenant_id claim**

```php
#[Security("is_granted('ROLE_DISPATCHER')")]
class OrderController extends AbstractController
{
    #[Route('/api/orders/{id}/assign', methods: ['PATCH'])]
    public function assignTruck(
        Order $order, 
        AssignTruckRequest $request,
        OrderService $orderService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('edit', $order);
        
        $updatedOrder = $orderService->assignTruck($order, $request->truckId);
        
        return $this->json($updatedOrder, 200, [], ['groups' => ['order:read']]);
    }
}
```

### Error Handling
- **Use custom exception classes**
- **Implement global exception listener**
- **Return consistent error format**
- **Log errors appropriately**

```php
class ClientNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Client not found or does not belong to your organization');
    }
}

class InsufficientTruckCapacityException extends \Exception
{
    public function __construct(float $required, float $available)
    {
        parent::__construct(
            sprintf('Truck capacity insufficient. Required: %s, Available: %s', $required, $available)
        );
    }
}
```

## Business Logic Rules

### Order Management
- **Orders can only be created for clients within same tenant**
- **Order status transitions**: `pending → assigned → in_transit → delivered`
- **Only pending orders can be assigned to trucks**
- **Truck capacity must be >= order quantity**
- **Auto-generate order numbers**: `ORD-YYYY-####`

### Multi-Tenancy Rules
- **Users belong to exactly one tenant**
- **All data queries are automatically filtered by tenant**
- **No cross-tenant data access allowed**
- **Tenant context set during JWT authentication**

### Validation Rules
- **Email must be unique within tenant** (not globally)
- **Truck numbers must be unique within tenant**
- **Order numbers must be unique within tenant**
- **Fuel quantity must be positive decimal**
- **Delivery dates must be in future (configurable business hours)**

## Domain-Specific Patterns

### Status Enums
```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}

enum TruckStatus: string
{
    case AVAILABLE = 'available';
    case IN_TRANSIT = 'in_transit';
    case MAINTENANCE = 'maintenance';
    case OUT_OF_SERVICE = 'out_of_service';
}
```

### Event-Driven Architecture
```php
// Dispatch events for important business actions
$this->eventDispatcher->dispatch(new OrderCreatedEvent($order));
$this->eventDispatcher->dispatch(new OrderAssignedEvent($order, $truck));
$this->eventDispatcher->dispatch(new OrderDeliveredEvent($order));

// Create event listeners for side effects
class OrderAssignedListener
{
    public function __invoke(OrderAssignedEvent $event): void
    {
        $order = $event->getOrder();
        $truck = $event->getTruck();
        
        // Update truck status
        $truck->setStatus(TruckStatus::IN_TRANSIT);
        
        // Log status change
        $this->statusHistoryService->logStatusChange($order, $event->getUser());
    }
}
```

### DTOs for Complex Operations
```php
readonly class CreateOrderDTO
{
    public function __construct(
        public Uuid $clientId,
        public Uuid $locationId,
        public float $fuelQuantity,
        public FuelUnit $fuelUnit,
        public \DateTimeInterface $requestedDeliveryDate,
        public ?string $notes = null
    ) {}
}
```

## Testing Guidelines
- **Unit tests for all services and business logic**
- **Integration tests for API endpoints**
- **Multi-tenant isolation tests**
- **Use fixtures for consistent test data**

```php
class OrderServiceTest extends KernelTestCase
{
    private OrderService $orderService;
    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->orderService = static::getContainer()->get(OrderService::class);
        $this->tenantContext = static::getContainer()->get(TenantContext::class);
    }

    public function testCreateOrderWithValidData(): void
    {
        $tenant = $this->createTenant();
        $this->tenantContext->setCurrentTenant($tenant);
        
        $client = $this->createClient($tenant);
        $dto = new CreateOrderDTO(/* ... */);
        
        $order = $this->orderService->createOrder($dto);
        
        $this->assertEquals($tenant, $order->getTenant());
        $this->assertEquals(OrderStatus::PENDING, $order->getStatus());
    }
}
```

## Performance Considerations
- **Use lazy loading for entity relationships**
- **Implement database indexes on tenant_id + frequently queried fields**
- **Use Redis for JWT token blacklist and caching**
- **Optimize queries with proper joins**

## File Naming Conventions
- **Entities**: `src/Entity/Order.php`
- **Repositories**: `src/Repository/OrderRepository.php`
- **Services**: `src/Service/OrderService.php`
- **DTOs**: `src/Dto/CreateOrderDTO.php`
- **Events**: `src/Event/OrderCreatedEvent.php`
- **Listeners**: `src/EventListener/OrderCreatedListener.php`
- **Tests**: `tests/Unit/Service/OrderServiceTest.php`

## Common Anti-Patterns to Avoid
- ❌ Business logic in controllers
- ❌ Direct entity manager usage in controllers
- ❌ Queries without tenant filtering
- ❌ Global unique constraints (should be tenant-scoped)
- ❌ Hardcoded values (use configuration/enums)
- ❌ Anemic domain models (entities with only getters/setters)

## Git Workflow & Commit Strategy

### Atomic Commits Principle
**ALWAYS break down tasks into small, atomic commits**. Each commit should represent ONE logical change that can be reviewed and reverted independently.

### When to Create Commits
During task execution, **pause and create commits** at these milestones:

1. **After Entity Creation**
   - Entity class + repository created
   - Example: `feat(entity): add Client entity with UUID and tenant relationship`

2. **After Repository Methods**
   - Custom repository methods added
   - Example: `feat(repository): add tenant-aware queries for Client`

3. **After Service Implementation**
   - Service class with business logic complete
   - Example: `feat(service): implement ClientService with CRUD operations`

4. **After API Resource Configuration**
   - API Platform resource configured
   - Example: `feat(api): add Client API resource with normalization groups`

5. **After Security/Validation**
   - Security voters or validators added
   - Example: `feat(security): add ClientVoter for tenant-scoped access control`

6. **After Tests Pass**
   - Unit or integration tests complete and passing
   - Example: `test(client): add unit tests for Client entity validation`

7. **After Configuration Changes**
   - Config files updated (routes, services, etc.)
   - Example: `chore(config): configure Client API routes and serialization`

8. **After Documentation**
   - Significant documentation added
   - Example: `docs(client): add API documentation for Client endpoints`

### Conventional Commit Format
Use this format for ALL commits:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature or functionality
- `fix`: Bug fix
- `refactor`: Code restructuring without behavior change
- `test`: Adding or updating tests
- `docs`: Documentation changes
- `chore`: Maintenance tasks (dependencies, config)
- `style`: Code style/formatting changes
- `perf`: Performance improvements

**Scopes:** Entity name, feature area, or component (e.g., `entity`, `service`, `api`, `auth`, `tenant`, `order`, `client`)

**Examples:**
```bash
feat(entity): add Tenant entity with UUID primary key
feat(repository): add TenantRepository with findBySlug method
feat(service): implement TenantContext service for isolation
test(tenant): add unit tests for tenant isolation logic
fix(api): correct tenant filter application in Client queries
refactor(order): extract status transition logic to OrderStateMachine
docs(api): document authentication endpoints
chore(deps): update API Platform to 4.1.2
```

### Workflow During Task Execution
When implementing a task:

1. **Plan the commits** - Break task into logical units
2. **Implement one unit** - Focus on single concept
3. **Verify it works** - Run code, execute tests
4. **Create commit** - Use conventional format
5. **Repeat** - Move to next unit

**Example: Task "Create Client Entity and CRUD"**
```bash
# Commit 1: Entity
git add src/Entity/Client.php
git commit -m "feat(entity): add Client entity with tenant relationship"

# Commit 2: Repository
git add src/Repository/ClientRepository.php
git commit -m "feat(repository): add ClientRepository with tenant-aware queries"

# Commit 3: API Resource
git add src/Entity/Client.php  # API Platform attributes
git commit -m "feat(api): configure Client API resource with CRUD operations"

# Commit 4: Service Layer
git add src/Service/ClientService.php
git commit -m "feat(service): implement ClientService for business logic"

# Commit 5: Tests
git add tests/Unit/Entity/ClientTest.php tests/Integration/Api/ClientApiTest.php
git commit -m "test(client): add unit and integration tests"
```

### Pre-Commit Checklist
Before each commit, verify:
- ✅ Code has no syntax errors
- ✅ Tests pass (if applicable)
- ✅ Follows project conventions
- ✅ Commit message is clear and descriptive
- ✅ Only related changes included

### Branch Strategy
- **Feature branches**: `feature/task-###-description`
- **Fix branches**: `fix/issue-description`
- **Main branch**: Always deployable, protected

**See `.github/commit-strategy.md` for detailed guidelines and examples.**

Remember: This is a **multi-tenant SaaS platform** where **data isolation is critical**. Every piece of code must respect tenant boundaries and follow the established patterns for security and scalability.