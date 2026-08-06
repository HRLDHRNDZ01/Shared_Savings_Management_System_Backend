# Shared Savings Management System (SSMS) — Backend

Laravel API for a savings / money-pool app (“spaces”): personal or shared pots with balances, deposits/withdrawals, invites, and activity notifications.

## Stack

- PHP 8.3
- Laravel 13
- Laravel Sanctum (token auth)
- Laravel Reverb (WebSocket push — no polling)
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
| **Notifications** | Activity feed (deposit, withdrawal, goal, system, etc.) + **instant WebSocket push** |

Tables use a `tbl_*` naming style (`tbl_spaces`, `tbl_transactions`, etc.).

## Main API surface

- **Auth:** register, login, me/profile, logout
- **Spaces:** list, create, members, invite
- **Invitations:** list, accept, decline
- **Transactions:** recent + totals, deposit, withdraw
- **Notifications:** list (`GET /api/notifications`) — for history/hydration
- **Broadcast auth:** `POST /api/broadcasting/auth` (Sanctum)
- **Admin:** Sanctum + `admin` middleware (`/admin/ping`)
- **Health:** `GET /api/health`

## Real-time notifications (Reverb)

When a row is created in `tbl_notifications`, the backend broadcasts instantly on a **private** user channel (no polling).

| Item | Value |
|------|--------|
| Channel | `private-users.{user_id}` |
| Events | `notification.created`, `invitation.created`, `invitation.updated` |
| Auth | `POST /api/broadcasting/auth` with `Authorization: Bearer {token}` |
| WS host | `localhost:8080` (Sail maps `REVERB_PORT`) |

### Start Reverb (with Sail)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan reverb:start
```

### Frontend (Laravel Echo + pusher-js)

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY, // same as REVERB_APP_KEY
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: 'http://localhost/api/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${accessToken}`,
      Accept: 'application/json',
    },
  },
})

echo.private(`users.${userId}`).listen('.notification.created', (payload) => {
  // payload.notification — same shape as REST notifications
  console.log(payload.notification)
})
```

Use `GET /api/notifications` once on app load for recent history; use Echo for live updates after that.

## Access control (User groups + sidebar)

Admins manage **user groups** and tick which sidebar pages each group can see.

| Endpoint | Purpose |
|----------|---------|
| `GET /api/me/sidebar` | Menus allowed for the logged-in user |
| `GET /api/admin/user-groups` | List groups |
| `POST /api/admin/user-groups` | Create group |
| `PUT /api/admin/user-groups/{id}/menus` | Tick/save menus for a group |
| `PUT /api/admin/users/{id}/group` | Assign a user to a group |
| `GET /api/admin/sidebar-menus` | Menus available for ticking |

- **Admin** always gets all menus (including Maintenance).
- Regular users get menus from their **one** assigned group.
- Seed: `SidebarAccessSeeder` creates menus + `Standard User` group.

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
# set REVERB_APP_KEY / REVERB_APP_SECRET (or copy from a working .env)
php artisan migrate
./vendor/bin/sail up -d
./vendor/bin/sail artisan reverb:start
```

API routes are defined in `routes/api.php`.
