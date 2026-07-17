---
description: "Shining English BE"
mode: primary
---

# Shining English — Backend Architecture

## Tech Stack

- **Laravel** 12 (PHP 8.5)
- **Filament** v5 (admin panel / CMS)
- **Sanctum** (API token auth)
- **MySQL** 8.4 (database)
- **Redis** (cache / queue)
- **Pest** v4 + **PHPUnit** v12 (testing)
- **Scramble** (API docs)
- **Socialite** (third-party auth)
- **Laravel Pint** (code style)
- **Docker** via Laravel Sail (PHP 8.5, Nginx, MySQL, Redis)

## Directory Structure

```
app/
├── Models/                   # 28 Eloquent models
│   ├── User.php              # Authenticatable + Notifiable + HasApiTokens
│   ├── Course.php / Lesson.php / Enrollment.php
│   ├── Order.php / OrderItem.php
│   ├── Star.php / StarTransaction.php
│   ├── Blog.php / Category.php / Level.php
│   └── Quiz.php / QuizQuestion.php / QuizAnswer.php / UserQuizAttempt.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/ApiController.php    # Base controller (uses Jsonable trait)
│   │   └── Api/V1/                  # Versioned controllers
│   │       ├── AuthController.php
│   │       ├── CourseController.php / LessonController.php
│   │       ├── OrderController.php / CartController.php
│   │       ├── StarController.php
│   │       ├── Notification/NotificationController.php
│   │       └── ...
│   ├── Middleware/
│   │   ├── VerifyDeveloperToken.php
│   │   └── VerifyUserToken.php
│   └── Requests/              # Form Request validation classes
├── Services/                  # Business logic (21 services)
│   ├── Service.php            # Base service class
│   ├── Star/                  # StarService, IStarService
│   ├── Enrollment/            # EnrollmentService
│   ├── Order/                 # OrderService
│   ├── Course/ / Lesson/ / Cart/ / Quiz/
│   └── Notification/          # NotificationService
├── Repositories/              # Data access (21 repositories)
│   ├── Repository.php         # Base repository class
│   ├── Star/                  # StarRepository, IStarRepository
│   └── ...                    # Matches Services structure
├── Integrations/
│   ├── Payments/              # Payment strategies (PayOS, COD)
│   └── Auth/                  # Socialite strategies (Google)
├── Notifications/
│   ├── PaymentSuccessNotification.php
│   ├── StarWalletNotification.php
│   ├── EnrollmentNotification.php
│   ├── LessonCompletedNotification.php
│   └── Auth/ResetPasswordNotification.php
├── Enums/                     # PHP 8 backed enums
├── DTO/                       # Data Transfer Objects
├── Jobs/                      # Queueable jobs
├── Traits/
│   └── Jsonable.php           # success/error/notfound response helpers
└── Providers/
    └── AppServiceProvider.php # All repository & service bindings

routes/
├── api.php                    # All API routes under /api/v1/
├── web.php                    # Web routes
└── console.php                # Artisan commands

config/
├── app.php / auth.php / database.php
├── const.php                  # App constants (star amounts, pagination)
├── queue.php                  # Database-backed queue
├── sanctum.php / services.php / payos.php
└── ...

tests/
├── Unit/                      # Pure unit tests (Mockery-based)
│   ├── Services/
│   ├── Repositories/
│   ├── Enums/
│   ├── Notifications/
│   └── Models/
├── Feature/                   # Integration tests (RefreshDatabase)
│   └── Api/V1/
├── Support/helpers.php        # assertServiceContract, assertRepositoryContract
├── Pest.php                   # Test case configuration
└── TestCase.php
```

## Data Flow

```
HTTP Request
  → api.php route
    → VerifyDeveloperToken middleware (all routes)
      → VerifyUserToken middleware (user-specific routes)
        → Controller (extends ApiController)
          → Service Interface
            → Service Implementation (business logic)
              → Repository Interface
                → Repository Implementation (extends Repository)
                  → Eloquent Model
```

## Response Format

All API responses follow the `Jsonable` trait format:

```json
// Success
{ "status": true, "status_code": 200, "data": { ... }, "message": "OK" }

// Error
{ "status": false, "status_code": 422, "message": "Error msg", "errors": {} }

// Paginated
{ "status": true, "status_code": 200, "data": [...], "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 35 } }
```

## Key API Routes (routes/api.php)

All routes under `/api/v1/`:

```
POST   /access-token                     # Developer access token
POST   /auth/register                    # Register
POST   /auth/login                       # Login
POST   /auth/third-party-login           # Google login
POST   /auth/forgot-password             # Password reset request
POST   /auth/reset-password              # Reset password

# Authenticated (VerifyUserToken):
GET    /auth/me                          # Current user
POST   /auth/logout
POST   /user/update

GET    /courses|/courses/filter|/courses/free
GET    /courses/{id}/access|/learning-progress
POST   /courses/{id}/lessons/{lessonId}/complete

GET    /lessons/{id}/video|/quiz|/comments
POST   /lessons/{id}/comments

GET    /orders|/orders/{id}
POST   /orders|/orders/{id}/cancel

GET    /cart/items|/cart/count
POST   /cart/items
DELETE /cart/clear

GET    /stars/balance
POST   /stars/check-in
POST   /stars/courses/{courseId}/pay

GET    /notifications                   # List (paginated)
GET    /notifications/unread-count
PATCH  /notifications/{id}/read
PATCH  /notifications/read-all
```

## Key Conventions

- **Layers**: Controller → Service (interface + impl) → Repository (interface + impl) → Model
- **Service** extends `Service` base class, takes `IRepository` in constructor
- **Repository** extends `Repository` base class, takes Eloquent `Model` in constructor
- **All bindings** registered in `AppServiceProvider::register()` via `$this->app->bind()`
- **Controller** constructor: type-hint service interfaces → auto-resolved by container
- **Validation**: always use Form Request classes (`php artisan make:request`)
- **Queues**: database-backed; jobs implement `ShouldQueue` + use `Queueable`
- **PHP 8**: constructor property promotion, named arguments, match expressions
- **Enums**: TitleCase keys, string-backed
- **Testing**: Pest, `uses(TestCase::class)` for unit, `uses(RefreshDatabase::class)` for feature
- **Code style**: `vendor/bin/pint --format agent` before finalizing

## IoC Bindings (AppServiceProvider)

Every repository and service interface is explicitly bound:

```php
$this->app->bind(IStarRepository::class, StarRepository::class);
$this->app->bind(IStarService::class, StarService::class);
// ... 40+ bindings total
```

## Notification Module

- 4 notification classes using `database` channel (implement `ShouldQueue`)
- `NotificationController` at `/api/v1/notifications`
- `NotificationService` → `INotificationRepository` → `NotificationRepository`
- Dispatched from: `StarService`, `EnrollmentService`, `OrderService`, `PayosPaymentStrategy`
- Tests: `tests/Unit/Notifications/*`, `tests/Feature/Api/V1/Notification/*`

## Testing Approach

```bash
# Run specific test file
docker compose exec -u sail shining_english_app php artisan test --filter=NotificationController

# Run all tests
docker compose exec -u sail shining_english_app php artisan test

# Code style
docker compose exec -u sail shining_english_app vendor/bin/pint --format agent
```

- **Unit tests**: Mockery-based, mock all dependencies, mock `DB::transaction`, no database
- **Feature tests**: `RefreshDatabase`, real HTTP requests, `Notification::fake()`, `Bus::fake()`
- Test helpers: `assertServiceContract()`, `assertRepositoryContract()`, `assertJsonResponsePayload()`, `createDeveloperAccessToken()`

## Code Generation Guide

When adding a new domain (e.g., "Coupon"):

1. `php artisan make:model Coupon -mf` → Model + migration + factory
2. Create `IRepository<Domain>` + `<Domain>Repository` in `app/Repositories/<Domain>/`
3. Create `IService<Domain>` + `<Domain>Service` in `app/Services/<Domain>/`
4. Create `<Domain>Controller` in `app/Http/Controllers/Api/V1/<Domain>/`
5. Create Form Request for validation if needed
6. Add routes in `routes/api.php`
7. Bind in `app/Providers/AppServiceProvider.php`
8. Write unit tests (service + repository + controller)
9. Run `vendor/bin/pint --format agent`

Always check sibling files for the correct pattern before writing new code.
