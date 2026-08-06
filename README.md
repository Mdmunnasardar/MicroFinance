# MicroFinance

A PHP + MySQL microfinance management application, split into a **JSON API backend** and a **React (Vite) single-page frontend**.

This step replaces the old server-rendered PHP UI for the API surface area. The legacy procedural PHP pages still work for modules that have not yet been ported to React (Members, Committees, Loans, Installments, Savings, Due System, Profile). They will be migrated module-by-module in subsequent steps.

---

## Top-level folder map

| Folder | Why it exists |
|--------|---------------|
| `backend/` | All server-side PHP — JSON API front controller, class-based controllers, config, views, helpers, middleware, routes, tests, storage. |
| `frontend/` | The new React SPA (Vite + React 18 + react-router-dom + axios). |
| `api/`, `members/`, `Committees/`, `loans/`, `installments/`, `savings/`, `due_system/`, `profile/`, `dashboard.php`, `login.php`, `logout.php`, `index.php` | Legacy URL shims. Still delegate to the existing procedural controllers. **Not deleted** — keep working until each module is replaced by a React equivalent. |
| `assets/` | Windows directory junction → `frontend/src/assets/`. Preserves existing `<link>` / `<script>` URLs that legacy pages reference. |
| `uploads/` | User-uploaded files (avatars). |

---

## Architecture

```
┌──────────────────────────┐         session cookie          ┌────────────────────────────────────┐
│  React SPA (Vite, 5173)  │  ───────────────────────────▶   │  PHP JSON API (Apache, XAMPP)     │
│  axios, withCredentials  │  ◀───────────────────────────   │  /MicroFinance/backend/public/api │
└──────────────────────────┘     {success, data} / errors     └────────────────────────────────────┘
                                                                              │
                                                                              ▼
                                                                       MySQL (MicroFinance)
```

- **Frontend** lives in `frontend/`. It is a Vite dev server on `http://localhost:5173`.
- **Backend** lives in `backend/`. The JSON API is mounted at `http://localhost/MicroFinance/backend/public/api` via the Apache front controller at `backend/public/index.php`.
- Auth is session-cookie based (PHP native sessions). The React app calls the API with `withCredentials: true` so cookies are shared.
- Legacy PHP pages keep working unchanged. The React sidebar links to them until the corresponding React module ships.

---

## `backend/` structure

```
backend/
├── public/
│   ├── index.php          ← JSON API front controller (Apache rewrite target)
│   └── .htaccess          ← routes /api/* to index.php (with ?route= fallback)
├── app/
│   ├── Bootstrap/
│   │   ├── autoload.php   ← spl_autoload_register for App\* namespace
│   │   └── bootstrap.php  ← session start, error/exception handlers, CORS
│   ├── Config/
│   │   ├── app.php        ← paths
│   │   ├── cors.php       ← allowed origins
│   │   ├── database.php   ← mysqli connection (new namespaced API connection)
│   │   └── db.php         ← legacy global $conn used by procedural controllers
│   ├── Controllers/
│   │   ├── JsonApi/       ← new class-based JSON API controllers
│   │   │   ├── AuthController.php            ← login, logout, session
│   │   │   ├── DashboardController.php       ← /api/dashboard-stats
│   │   │   ├── MembersController.php         ← stub (501)
│   │   │   ├── CommitteesController.php      ← stub (501)
│   │   │   ├── LoansController.php           ← stub (501)
│   │   │   ├── InstallmentsController.php    ← stub (501)
│   │   │   ├── SavingsController.php         ← stub (501)
│   │   │   ├── DueSystemController.php       ← stub (501)
│   │   │   ├── ProfileController.php         ← stub (501)
│   │   │   ├── SearchController.php          ← stub (501)
│   │   │   └── NotificationsController.php   ← stub (501)
│   │   ├── Auth/         ← legacy procedural login/logout
│   │   ├── Members/, Committees/, Loans/, Installments/, Savings/, DueSystem/, Profile/
│   │   └── Api/          ← legacy procedural JSON endpoints (search, notifications)
│   ├── Views/            ← legacy HTML layouts (sidebar, topbar, etc.)
│   ├── Models/           ← (empty — future MVP)
│   ├── Services/         ← (empty — future MVP)
│   ├── Repositories/     ← (empty — future MVP)
│   ├── Middleware/
│   │   ├── CorsMiddleware.php
│   │   └── AuthMiddleware.php
│   ├── Helpers/
│   │   ├── JsonResponse.php
│   │   ├── Request.php
│   │   └── Logger.php
│   ├── Routes/
│   │   └── api.php       ← full API route manifest
│   └── Database/
│       ├── migrations/   ← (empty + README)
│       └── seeders/      ← (empty + README)
├── storage/
│   └── logs/             ← API runtime log (api.log)
└── tests/                ← test scaffolding (README inside)
```

---

## `frontend/` structure

```
frontend/
├── index.html
├── package.json
├── vite.config.js
├── .env.example          ← VITE_API_BASE_URL
├── README.md
└── src/
    ├── main.jsx          ← ReactDOM root, BrowserRouter, AuthProvider
    ├── App.jsx           ← Routes
    ├── styles.css        ← React UI styles
    ├── api/
    │   ├── client.js     ← axios instance (withCredentials), 401 handler
    │   ├── authApi.js
    │   ├── membersApi.js
    │   ├── loansApi.js
    │   ├── committeesApi.js
    │   ├── savingsApi.js
    │   ├── installmentsApi.js
    │   └── dueSystemApi.js
    ├── context/AuthContext.jsx
    ├── hooks/useAuth.js
    ├── components/
    │   ├── ProtectedRoute.jsx
    │   ├── LoadingScreen.jsx
    │   └── layout/
    │       ├── AppLayout.jsx
    │       ├── Sidebar.jsx
    │       └── Topbar.jsx
    └── pages/
        ├── LoginPage.jsx
        ├── DashboardPage.jsx
        └── NotFoundPage.jsx
```

---

## Run it locally

### 1. Prerequisites

- XAMPP (Apache + MySQL/MariaDB), with Apache serving the repo at `http://localhost/MicroFinance/`.
- PHP 8.x.
- Node.js 18+ (`node` and `npm`).
- The legacy `MicroFinance` MySQL database already imported.

### 2. Backend

No build step. Make sure Apache is serving this repo. The API is reachable at:

```
http://localhost/MicroFinance/backend/public/api
```

If `mod_rewrite` is unavailable, the front controller also accepts a `?route=` query string fallback:

```
http://localhost/MicroFinance/backend/public/index.php?route=/api/dashboard-stats
```

### 3. Frontend

```bash
cd frontend
cp .env.example .env       # set VITE_API_BASE_URL if your backend URL differs
npm install
npm run dev
```

Open http://localhost:5173.

Sign in with an existing MicroFinance user (the same credentials you use in the legacy `login.php`).

### 4. Build the frontend for production

```bash
npm run build
```

Static files are emitted into `frontend/dist/`. They can be served by Apache (or any static host) at any URL prefix.

---

## API surface

Full route manifest lives in `backend/app/Routes/api.php`. Summary:

| Status | Resource | Endpoints |
|---|---|---|
| ✅ Implemented | Auth | `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/session` |
| ✅ Implemented | Dashboard | `GET /api/dashboard-stats` |
| 🟡 Stub (`501 NOT_IMPLEMENTED` when authed) | Profile | `GET/PUT /api/profile`, `POST /api/profile/avatar`, `PUT /api/profile/password` |
| 🟡 Stub | Search | `GET /api/search` |
| 🟡 Stub | Notifications | `GET /api/notifications`, `POST /api/notifications/{id}/read` |
| 🟡 Stub | Members | `GET/POST /api/members`, `GET/PUT/DELETE /api/members/{id}`, `GET /api/members/{id}/transactions\|loans\|savings` |
| 🟡 Stub | Committees | `GET/POST /api/committees`, `GET/PUT/DELETE /api/committees/{id}`, member attach/detach |
| 🟡 Stub | Loans | `GET/POST /api/loans`, `GET/PUT/DELETE /api/loans/{id}`, `POST /api/loans/{id}/status` |
| 🟡 Stub | Installments | CRUD at `/api/installments[/{id}]` |
| 🟡 Stub | Savings | CRUD at `/api/savings[/{id}]`, plus deposits/withdrawals/transactions |
| 🟡 Stub | Due System | `GET /api/due-system[/{id}]`, `POST /api/due-system/{id}/collect` |

Stubs sit behind auth, so they return `401 UNAUTHORIZED` until you are signed in, then `501 NOT_IMPLEMENTED` from the controller body once auth passes.

---

## JSON envelope

Success:

```json
{
  "success": true,
  "data": { "...": "..." },
  "meta": {}
}
```

Failure:

```json
{
  "success": false,
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Invalid username or password.",
    "details": {}
  }
}
```

Standard error codes: `UNAUTHORIZED`, `VALIDATION_ERROR`, `NOT_FOUND`, `METHOD_NOT_ALLOWED`, `INVALID_CREDENTIALS`, `NOT_IMPLEMENTED`, `INTERNAL_ERROR`.

---

## CORS

The API is reachable from the React dev server with credentialed cookies. The allowed origin defaults to `http://localhost:5173` and is set in `backend/app/Config/cors.php`. The middleware emits:

```
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: Content-Type, X-Requested-With
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Vary: Origin
```

`OPTIONS` preflight is short-circuited with `204 No Content`.

---

## Migration status

| Module | Legacy PHP | New React page | New JSON endpoint |
|---|---|---|---|
| Login | ✅ | ✅ `/login` | ✅ `POST /api/auth/login`, `GET /api/auth/session`, `POST /api/auth/logout` |
| Dashboard | ✅ | ✅ `/` (Dashboard) | ✅ `GET /api/dashboard-stats` |
| Members | ✅ | ❌ (sidebar links to legacy) | ❌ (stub) |
| Committees | ✅ | ❌ (sidebar links to legacy) | ❌ (stub) |
| Loans | ✅ | ❌ (sidebar links to legacy) | ❌ (stub) |
| Installments | ✅ | ❌ (sidebar links to legacy) | ❌ (stub) |
| Savings | ✅ | ❌ (sidebar links to legacy) | ❌ (stub) |
| Due System | ✅ | ❌ (sidebar links to legacy) | ❌ (stub) |
| Profile | ✅ | ❌ (in topbar placeholder) | ❌ (stub) |
| Search | ✅ | ❌ (in topbar placeholder) | ❌ (stub) |
| Notifications | ✅ | ❌ (in topbar placeholder) | ❌ (stub) |

The next steps (module migrations) will, for each row marked ❌ in the React column, build the React page + replace the stub controller with the real SQL preserved from the procedural legacy controller.

---

## What this step did NOT do

- Did **not** refactor existing legacy controllers. Their SQL and HTML output are untouched.
- Did **not** extract SQL into Services / Repositories. The `Services/` and `Repositories/` directories are scaffolded but empty. SQL duplication between legacy procedural controllers and new `JsonApi/` controllers is intentional for this step.
- Did **not** migrate module pages (Members, Committees, Loans, Installments, Savings, Due System) to React. They keep working via legacy URLs.
- Did **not** introduce tokens, JWT, or any auth change. PHP session cookies remain the auth mechanism.
- Did **not** rename `Committees/` (legacy URL path). Out of scope.
