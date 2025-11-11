# Fuel Delivery Management Platform

A **multi-tenant SaaS backend API** for fuel delivery companies built with **Symfony 7.x** and **API Platform 4.x**. This system manages fuel delivery operations including orders, clients, trucks, and users with complete tenant isolation.

## 🚀 Features

- **Multi-Tenant Architecture**: Complete data isolation for each organization
- **RESTful API**: JSON:API format with API Platform 4.x
- **Authentication**: JWT-based authentication with role-based access control (RBAC)
- **Order Management**: Complete workflow from creation to delivery
- **Fleet Management**: Truck tracking and assignment
- **Client Management**: Customer and location management
- **Status Tracking**: Real-time order status updates with history

## 📋 Requirements

- **PHP**: 8.3 or higher
- **PostgreSQL**: 16.x or higher
- **Composer**: 2.x
- **Docker & Docker Compose**: For local development (optional)

## 🛠️ Tech Stack

- **Framework**: Symfony 7.3
- **API**: API Platform 4.x
- **ORM**: Doctrine ORM 3.x
- **Database**: PostgreSQL 16+
- **Authentication**: JWT (LexikJWTAuthenticationBundle)
- **Code Quality**: PHPStan (Level 8), PHP CS Fixer

## 📦 Installation

### Using Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd fuel_delivery_api
   ```

2. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   docker-compose exec php composer install
   ```

4. **Setup database**
   ```bash
   docker-compose exec php bin/console doctrine:database:create
   docker-compose exec php bin/console doctrine:migrations:migrate
   ```

5. **Generate JWT keys**
   ```bash
   docker-compose exec php bin/console lexik:jwt:generate-keypair
   ```

### Manual Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd fuel_delivery_api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env .env.local
   # Edit .env.local with your database credentials and settings
   ```

4. **Setup database**
   ```bash
   bin/console doctrine:database:create
   bin/console doctrine:migrations:migrate
   ```

5. **Generate JWT keys**
   ```bash
   bin/console lexik:jwt:generate-keypair
   ```

6. **Start development server**
   ```bash
   symfony server:start
   ```

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env.local`:

```env
# Database
DATABASE_URL="postgresql://user:password@localhost:5432/fuel_delivery?serverVersion=16&charset=utf8"

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase

# App Environment
APP_ENV=dev
APP_SECRET=your_app_secret
```

## 🎯 Usage

### API Documentation

Once the application is running, access the API documentation at:
- **Swagger UI**: `http://localhost:8000/api/docs`
- **OpenAPI Spec**: `http://localhost:8000/api/docs.json`

### Authentication

1. **Login** to get JWT token:
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"user@example.com","password":"password"}'
   ```

2. **Use the token** in subsequent requests:
   ```bash
   curl -X GET http://localhost:8000/api/orders \
     -H "Authorization: Bearer YOUR_JWT_TOKEN"
   ```

## 🧪 Testing

### Run all tests
```bash
composer test
```

### Run tests with coverage
```bash
composer test-coverage
```

Coverage report will be generated in `var/coverage/index.html`

## 📐 Code Quality

### PHPStan (Static Analysis)
```bash
# Run analysis
composer phpstan

# Generate baseline for existing issues
composer phpstan-baseline
```

### PHP CS Fixer (Code Style)
```bash
# Check code style
composer cs-check

# Fix code style automatically
composer cs-fix
```

### Run all quality checks
```bash
# Check without fixing
composer quality

# Fix issues automatically
composer quality-fix
```

## 🏗️ Architecture

### Multi-Tenancy
- All entities have a `tenant` relationship
- Doctrine filters enforce tenant isolation automatically
- JWT tokens include `tenant_id` claim
- No cross-tenant data access allowed

### Entity Structure
```
Tenant
├── User (ROLE_ADMIN, ROLE_DISPATCHER, ROLE_USER)
├── Client
│   └── Location
├── Truck
└── Order
    └── OrderStatusHistory
```

### API Endpoints

#### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register new tenant
- `POST /api/auth/refresh` - Refresh token

#### Resources
- `/api/tenants` - Tenant management (admin only)
- `/api/users` - User management
- `/api/clients` - Client management
- `/api/locations` - Location management
- `/api/trucks` - Truck management
- `/api/orders` - Order management

## 🔐 Security

### Roles & Permissions
- **ROLE_ADMIN**: Full system access, tenant management
- **ROLE_DISPATCHER**: Order and fleet management
- **ROLE_USER**: Read-only access to assigned orders

### Best Practices
- All entities use UUID primary keys
- Passwords hashed with bcrypt
- JWT tokens expire after configurable period
- Input validation on all endpoints
- SQL injection prevention via Doctrine ORM

## 📚 Development

### Project Structure
```
fuel_delivery_api/
├── config/           # Configuration files
├── migrations/       # Database migrations
├── public/          # Web root
├── src/
│   ├── Entity/      # Doctrine entities
│   ├── Repository/  # Data repositories
│   ├── Service/     # Business logic
│   ├── Controller/  # API controllers
│   ├── Dto/         # Data transfer objects
│   ├── Event/       # Domain events
│   └── EventListener/ # Event listeners
├── tests/           # PHPUnit tests
└── var/             # Cache, logs, temp files
```

### Coding Standards
- **PSR-12** code style
- **Symfony** best practices
- **Strict types** declaration required
- **Type hints** on all parameters and returns
- **PHPDoc** for complex logic

### Git Workflow
1. Create feature branch from `develop`
2. Make changes with meaningful commits
3. Run quality checks: `composer quality`
4. Push and create Pull Request
5. PR must pass CI checks
6. Merge to `develop` after approval

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=prod` in environment
- [ ] Configure production database
- [ ] Generate new JWT keys
- [ ] Set strong `APP_SECRET`
- [ ] Clear cache: `bin/console cache:clear --env=prod`
- [ ] Warm cache: `bin/console cache:warmup --env=prod`
- [ ] Run migrations: `bin/console doctrine:migrations:migrate --no-interaction`
- [ ] Setup HTTPS/SSL certificates
- [ ] Configure CORS properly
- [ ] Setup monitoring and logging

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📝 License

This project is proprietary and confidential.

## 📞 Support

For questions and support, please contact the development team.

## 🔗 Useful Links

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [API Platform Documentation](https://api-platform.com/docs)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP CS Fixer Documentation](https://cs.symfony.com/)
