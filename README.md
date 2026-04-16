## About Bumpa Loyalty Backend

This is the backend API for the Bumpa Loyalty Program built with Laravel 12. The application provides an event-driven loyalty system where users earn achievements based on purchase count, unlock badges at achievement thresholds, and receive cashback via a mock Paystack payment gateway.


## Features

- Event-driven achievement and badge system
- Mock Paystack payment gateway for cashback disbursement
- RESTful API with Sanctum token authentication
- Admin endpoints with role-based access control
- Comprehensive test coverage (unit + feature)


## Tech Stack

- PHP 8.2, Laravel 12, Sanctum
- MySQL 8.4, Redis 7
- Docker (MySQL, Redis, PHP webserver, queue worker, frontend)
- PHPUnit


## Installation

The application is wrapped with a docker container that also includes the frontend service. Ensure docker-compose/docker is installed and running on system before running the command. To install docker visit `https://docs.docker.com/desktop/`.

**Important:** Both `bumpa-backend` and `bumpa-frontend` repos must be cloned as sibling directories (same parent folder) for the unified Docker setup to work:

```
parent-folder/
  bumpa-backend/
  bumpa-frontend/
```

Clone both repos

    git clone https://github.com/GeneraalAladeen/bumpa-backend.git
    git clone <bumpa-frontend-repo-url>

Switch to the backend folder

    cd bumpa-backend

Install PHP dependencies

    docker-compose run --rm base_php composer install

Copy the environment file

    cp .env.example .env

Generate encryption key

    docker-compose run --rm base_php php artisan key:generate

Start the database

    docker-compose up -d database_server

Run migrations and seed the database

    docker-compose run --rm base_php php artisan migrate --seed

Start all services (backend API, queue worker, database, Redis, frontend)

    docker-compose up -d

This starts five services:
- **database_server** — MySQL 8.4 on port 3309
- **redis** — Redis 7
- **webserver** — Laravel API on `http://localhost:8002`
- **queue** — Queue worker for processing achievements and cashback
- **frontend** — React app on `http://localhost:5173`


## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/register | No | Register a new user |
| POST | /api/login | No | Login and get token |
| GET | /api/user | Sanctum | Get authenticated user |
| GET | /api/users/{user}/achievements | Sanctum | Get user's loyalty progress |
| POST | /api/purchases | Sanctum | Simulate a purchase |
| GET | /api/admin/users/achievements | Sanctum + Admin | List all users' loyalty data |


## Architecture

### Event Flow

```
PurchaseCompleted event
  → CheckAchievementsListener (queued)
      → AchievementUnlocked event
          → CheckBadgesListener (queued)
              → BadgeUnlocked event
  → ProcessCashbackListener (queued)
      → MockPaystackGateway → CashbackTransaction
```

### Database Models

- **User** — has achievements (many-to-many), badges (many-to-many), orders, cashback transactions
- **Order** — belongs to user, has order reference, total amount, status
- **Achievement** — purchase count thresholds with cashback percentages
- **Badge** — achievement count thresholds
- **CashbackTransaction** — payment records with provider references and status


## Seeded Data

### Achievements
| Name | Purchases Required | Cashback % |
|------|--------------------|------------|
| First Purchase | 1 | 1% |
| 5 Purchases | 5 | 2% |
| 10 Purchases | 10 | 3% |
| 25 Purchases | 25 | 5% |
| 50 Purchases | 50 | 7% |

### Badges
| Name | Achievements Required |
|------|-----------------------|
| Beginner | 1 |
| Intermediate | 2 |
| Advanced | 3 |
| Master | 5 |

### Seeded Users
| Email | Password | Role |
|-------|----------|------|
| admin@bumpa.com | password | Admin |
| john@example.com | password | User |
| jane@example.com | password | User |


## Design Choices

### Simple Pagination
The admin users endpoint uses `simplePaginate` instead of `paginate`. Standard pagination runs a `COUNT(*)` query on every request to calculate the total number of records and last page — this gets expensive as the users table grows. Since the admin panel only needs Previous/Next navigation, `simplePaginate` skips the count query entirely and just checks if there's a next page, making it significantly faster at scale.

### Queued Event Listeners
All listeners (`CheckAchievementsListener`, `CheckBadgesListener`, `ProcessCashbackListener`) implement `ShouldQueue` so the purchase API response returns immediately without waiting for achievement checks or cashback processing. This keeps the API fast, but it means the frontend won't see achievement unlocks instantly — they appear once the queue worker processes the jobs (typically a few seconds).

### Cashback Simulation
The `MockPaystackGateway` simulates a real payment provider with an 80% success rate (configurable via `PAYMENT_GATEWAY_SUCCESS_RATE` in `.env`). Failed cashback disbursements are recorded with a `failure_reason` so they can be retried or investigated. In production, this would be swapped for a real Paystack integration by implementing the `PaymentGatewayInterface` contract — no controller or listener code would need to change since the gateway is resolved from the service container.


## Testing

Run all tests

    docker-compose run --rm base_php php artisan test

Or locally with SQLite

    php artisan test
