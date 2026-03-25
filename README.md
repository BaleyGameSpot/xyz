# Chinar Signals

A full-stack trading signal application — Laravel REST API backend, Blade admin panel, and native Android client.

---

## Features

### Backend / API
- JWT authentication with token refresh
- Subscription-gated signal access (per package limits)
- Automated signal generation via scheduled Artisan commands
- Crypto payment submission and manual admin verification
- Firebase Cloud Messaging push notifications
- Rate limiting on all endpoints

### Admin Panel
- Dark trading-themed UI (Bootstrap 5 + Chart.js)
- Dashboard with live stats, win/loss charts, and recent activity
- Full user management with block/unblock and manual subscription assignment
- Signal management with bulk win/loss marking
- Package CRUD with timeframe and limit configuration
- Payment verification/rejection workflow with reason tracking
- System settings: wallet addresses, signal config, Firebase, security

### Android App
- Kotlin / Jetpack Compose
- JWT-authenticated signal feed
- Per-pair and per-timeframe filtering
- FCM push notification integration
- Crypto payment submission

---

## Tech Stack

| Layer        | Technology                                      |
|--------------|-------------------------------------------------|
| Backend      | PHP 8.2, Laravel 11, MySQL 8                    |
| Auth         | JWT (tymon/jwt-auth)                            |
| Admin UI     | Blade templates, Bootstrap 5.3, Chart.js 4.4   |
| Notifications| Firebase Cloud Messaging (FCM)                  |
| Queue        | Redis + Laravel Horizon (optional)              |
| Android      | Kotlin, Jetpack Compose, Retrofit 2, OkHttp 4  |
| Web Server   | Nginx + PHP-FPM                                 |

---

## Project Structure

```
chinar-signals/
├── backend/                    # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Admin/          # Admin panel controllers
│   │   │   └── Api/            # REST API controllers
│   │   ├── Models/
│   │   ├── Services/
│   │   │   ├── SignalService.php
│   │   │   └── NotificationService.php
│   │   └── Console/Commands/
│   │       └── GenerateSignals.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── resources/views/admin/  # Blade admin panel
│   │   ├── layouts/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── users/
│   │   ├── signals/
│   │   ├── packages/
│   │   ├── payments/
│   │   └── settings/
│   └── routes/
│       ├── api.php             # REST API routes
│       └── web.php             # Admin panel routes
│
├── android/                    # Android application
│   └── app/src/main/java/
│       └── com/chinarsignals/app/
│
└── docs/
    ├── API.md                  # Full API documentation
    ├── SETUP.md                # Deployment guide
    └── schema.sql              # MySQL schema dump
```

---

## Quick Start

### 1. Clone and install

```bash
git clone https://github.com/your-org/chinar-signals.git
cd chinar-signals/backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 2. Configure database

```bash
# Edit .env with your DB credentials, then:
php artisan migrate
php artisan db:seed
```

### 3. Start development server

```bash
php artisan serve
```

Admin panel: [http://localhost:8000/admin](http://localhost:8000/admin)
API base: [http://localhost:8000/api/v1](http://localhost:8000/api/v1)

Default admin credentials: `admin@chinarsignals.com` / `Admin@12345`

### 4. Run queue worker (for push notifications)

```bash
php artisan queue:work
```

---

## Admin Panel Routes

| Route                         | Description               |
|-------------------------------|---------------------------|
| `/admin/login`                | Admin authentication      |
| `/admin/dashboard`            | Stats, charts, activity   |
| `/admin/users`                | User list and management  |
| `/admin/users/{id}`           | User detail and history   |
| `/admin/signals`              | Signal list with filters  |
| `/admin/signals/{id}`         | Signal detail, mark W/L   |
| `/admin/packages`             | Package list              |
| `/admin/packages/create`      | Create new package        |
| `/admin/packages/{id}/edit`   | Edit package              |
| `/admin/payments`             | Payment verification      |
| `/admin/settings`             | System configuration      |

---

## API Overview

Base URL: `https://api.chinarsignals.com/api/v1`

| Method | Endpoint              | Auth | Description             |
|--------|-----------------------|------|-------------------------|
| POST   | /auth/register        | No   | Register new user       |
| POST   | /auth/login           | No   | Login, receive JWT      |
| POST   | /auth/logout          | Yes  | Invalidate token        |
| GET    | /auth/me              | Yes  | Get current user        |
| GET    | /packages             | No   | List packages           |
| POST   | /payments             | Yes  | Submit payment          |
| GET    | /payments/wallets     | No   | Get wallet addresses    |
| GET    | /signals              | Yes  | List signals (filtered) |
| GET    | /signals/{id}         | Yes  | Get signal details      |
| GET    | /subscription         | Yes  | Subscription status     |

See [docs/API.md](docs/API.md) for complete documentation.

---

## Documentation

- [API Reference](docs/API.md) — All endpoints, request/response examples, error codes
- [Setup Guide](docs/SETUP.md) — Server requirements, deployment, Firebase, cron, security
- [Database Schema](docs/schema.sql) — MySQL schema with indexes, FK constraints, seed data

---

## License

Proprietary — All rights reserved. © 2025 Chinar Signals.
