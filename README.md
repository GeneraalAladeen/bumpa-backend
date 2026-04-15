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
- Docker (MySQL, Redis, PHP webserver, queue worker)
- PHPUnit


## Installation

The application is wrapped with a docker container. Ensure docker-compose/docker is installed and running on system before running the command. To install docker visit `https://docs.docker.com/desktop/`.

Clone the repo

    git clone https://github.com/GeneraalAladeen/bumpa-backend.git

Switch to repo folder

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

Start the application

    docker-compose up -d

The API will be served on `http://localhost:8002`.


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


## Testing

Run all tests

    docker-compose run --rm base_php php artisan test

Or locally with SQLite

    php artisan test
