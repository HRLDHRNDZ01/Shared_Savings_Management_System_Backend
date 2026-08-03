# Shared Savings Management System (SSMS) — Backend

Laravel API for a savings / money-pool app (“spaces”): personal or shared pots with balances, deposits/withdrawals, invites, and activity notifications.

## Stack

- PHP 8.3
- Laravel 13
- Laravel Sanctum (token auth)
- Docker Compose / Sail
- Postman collection (`postman/`)
- Feature tests under `tests/`

## Domain model

| Concept | Role |
|--------|------|
| **Users** | Register/login, profile, search; roles `admin` / `user` |
| **Spaces** | Savings pots (`personal` or `shared`) with `target_amount`, `balance`, `active`/`archived` |
| **Members** | Owners and members on a space |
| **Invitations** | Invite → accept/decline |
| **Transactions** | Deposits and withdrawals against a space (balance updated in a DB transaction) |
| **Notifications** | Activity feed (deposit, withdrawal, goal, system, etc.) |

Tables use a `tbl_*` naming style (`tbl_spaces`, `tbl_transactions`, etc.).

## Main API surface

- **Auth:** register, login, me/profile, logout
- **Spaces:** list, create, members, invite
- **Invitations:** list, accept, decline
- **Transactions:** recent + totals, deposit, withdraw
- **Notifications:** list
- **Admin:** Sanctum + `admin` middleware (`/admin/ping`)
- **Health:** `GET /api/health`

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

API routes are defined in `routes/api.php`.
