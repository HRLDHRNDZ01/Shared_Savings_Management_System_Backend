# Shared Savings Management System (SSMS) — Backend

Laravel API for a savings / money-pool app (“spaces”): personal or shared pots with balances, deposits/withdrawals, invites, and activity notifications.

## Stack

- PHP 8.4
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

## Getting started (local)

**Needs:** Docker Desktop (WSL2 OK) + Git. PHP on the host is optional if you use Sail for everything.

```bash
git clone <this-repo-url>
cd backend_ssms
cp .env.example .env
```

Edit `.env` for local (do **not** commit `.env`):

```env
APP_ENV=local
APP_URL=http://localhost
FRONTEND_URL=http://localhost:5173
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ssms
DB_USERNAME=sail
DB_PASSWORD=password
```

Then:

```bash
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html composer:2 composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail artisan db:seed --force
```

- API (Sail): http://localhost  
- Health: http://localhost/api/health  
- Reverb (optional realtime): `./vendor/bin/sail artisan reverb:start`

### Local DB in Heidi / DBeaver (from Windows)

| Field | Value |
|--------|--------|
| Type | MySQL |
| Host | `127.0.0.1` (not `mysql`) |
| Port | `3306` |
| Database | `ssms` |
| User | `sail` |
| Password | `password` |

### Seeded logins

- `admin@example.com` / `password`
- `user@example.com` / `password`

API routes: `routes/api.php`. Postman: `postman/`.

## Free live deploy (test)

Recommended free stack:
- **Backend + DB:** [Render](https://render.com) (`render.yaml` + `Dockerfile` — PHP via **Docker**)
- **Frontend:** [Vercel](https://vercel.com) (add `vercel.json` SPA rewrites on the FE repo)
- **Realtime:** off on free (`BROADCAST_CONNECTION=log`) — use `GET /api/notifications`

### 1) Push backend (`main`)

```bash
git push -u origin main
```

### 2) Render (API + Postgres)

1. https://dashboard.render.com → **New** → **Blueprint**
2. Connect this GitHub repo + branch **`main`**
3. Apply `render.yaml` → creates `ssms-api` + `ssms-db` (same region, e.g. Oregon)
4. Set env on **ssms-api**:
   - `APP_KEY` = Laravel key (`php artisan key:generate --show` → must start with `base64:`)
   - `APP_URL` = your Render URL (e.g. `https://ssms-api-xxxxx.onrender.com`)
   - `FRONTEND_URL` = your Vercel origin (e.g. `https://shared-savings-management-system-fr.vercel.app`)
5. Open `/api/health` (not `/` — root may 500; API-only is fine)
6. First boot seeds:
   - `admin@example.com` / `password`
   - `user@example.com` / `password`

**Notes**
- Render `generateValue` for `APP_KEY` is **not** a valid Laravel key — set `base64:…` yourself.
- Free web services **sleep** when idle; first request can be slow.
- Prod DB: Render → `ssms-db` → **External Database URL** + SSL (Postgres, not MySQL).

### 3) Frontend (Vercel)

In `frontend_ssms` env:

```bash
VITE_API_BASE_URL=https://YOUR-RENDER-API.onrender.com
```

Include `vercel.json` rewrites to `index.html` for Vue router paths (`/login`, `/register`, etc.).

### 4) Smoke test

1. Open frontend → register/login  
2. Create space / deposit  
3. Admin → Maintenance  
4. Instant WebSocket push may be off on free Render  

Local `.env` stays on your machine; prod uses Render Environment only — never commit secrets.
