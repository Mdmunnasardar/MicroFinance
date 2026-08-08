# MicroFinance

A full-stack microfinance management platform built with a **namespaced PHP JSON API** backend and a **React 18 (Vite) single-page application** frontend, backed by a **MySQL / MariaDB** relational schema.

The application supports the day-to-day operations of a microfinance institution: members, committees, loans and loan payments, installments, savings accounts and transactions, branch management, role-based field-officer workflows, an admin dashboard with KPIs, and personal profile / password management.

The React SPA is served from the same Apache document root as the JSON API (`/MicroFinance/`), sharing PHP session cookies for authentication.

---

## Table of contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Architecture](#architecture)
- [Project structure](#project-structure)
- [Getting started](#getting-started)
- [Database setup](#database-setup)
- [JSON API](#json-api)
- [Frontend routes](#frontend-routes)
- [JSON envelope](#json-envelope)
- [CORS](#cors)
- [Deployment](#deployment)
- [Testing](#testing)
- [Contributing](#contributing)
- [Author](#author)
- [License](#license)

---

## Features

### Members
- Create, view, edit, and deactivate members with full demographic data
- Search members by name or member code
- View per-member loans, savings, and recent transactions
- Bulk filter by branch, status, gender, and join date

### Committees
- Create and edit committees scoped to a branch
- Assign / remove members from a committee
- View committee details and member roster

### Loans
- Create loans for members with principal, interest rate, and term
- Record loan payments and update loan status (`active`, `closed`, etc.)
- Track total disbursed, paid, and outstanding
- Loan health / repayment-progress indicators on the dashboard

### Installments & savings
- Schedule and record installments
- Savings accounts with deposit / withdraw flows
- Per-member savings history and global savings transaction log

### Due system
- List of loans with outstanding balances
- Overdue-loan view (loans past maturity)
- Collection reports

### Dashboard
- KPI cards: total members, active members, total loans, total paid, total due, total savings, total collection, overdue loans
- Loans-vs-collection line chart for the last 6 months
- Top borrower card
- Recent transactions and recent members lists

### Profile & account
- View own profile (full name, username, role, branch, avatar)
- Edit profile (name, phone, email, branch)
- Change password (with current-password verification and minimum length)
- Upload / replace avatar (JPEG / PNG / GIF / WebP)
- Role-gated view: admins and branch managers see the field officers list

### Search & notifications (legacy JSON)
- Global search across members, committees, and officers (Topbar)
- Role-specific notification feed in the Topbar

### Authentication & session
- PHP-native session cookies (no JWT, no tokens)
- Server-side session regeneration on login to prevent fixation
- HTTP-only + SameSite=Lax cookies
- Tri-state React auth context (`loading | unauthenticated | authenticated`)
- 401 responses globally drop the client into the unauthenticated state

---

## Tech stack

### Backend
- **PHP 8.x** with `declare(strict_types=1)`
- **`mysqli`** for database access (no PDO, no ORM)
- **`password_hash` / `password_verify`** (bcrypt) for credentials
- Native PHP sessions with custom cookie params
- A **PSR-style `App\` namespace** autoloaded by `spl_autoload_register` (`backend/app/Bootstrap/autoload.php`)
- A small custom JSON front controller (`backend/public/index.php`) that walks the route manifest in `backend/app/Routes/api.php`
- Apache `mod_rewrite` for clean URLs
- **No Composer dependencies** — the backend is built entirely on core PHP

### Frontend
- **React 18.3** + **react-router-dom 6.24** (SPA routing, `BrowserRouter basename="/MicroFinance"`)
- **Vite 5.3** as the dev server and bundler (`@vitejs/plugin-react`)
- **axios 1.7** with `withCredentials: true` for cookie-based auth
- **Context API** (`AuthContext`) + a `ProtectedRoute` wrapper for route gating
- Plain CSS in `frontend/src/styles.css` (no CSS-in-JS, no styled-components)
- CDN-loaded **Font Awesome 6**, **Google Fonts (Inter)**, and **Chart.js** at runtime

### Tooling
- **Tailwind CSS 4.3** at the project root for the legacy static CSS pipeline
- A standalone Vite entry point (`frontend/installments.html`) used as a multi-page-app escape hatch

### Database
- **MySQL / MariaDB** (`MicroFinance` schema)
- Connection via `App\Config\Database` (env-driven) and the legacy `backend/app/Config/db.php`

---

## Architecture

```
┌────────────────────────────┐       session cookie        ┌──────────────────────────────────────┐
│  React SPA (Vite, :5173)   │  ───────────────────────▶   │   PHP JSON API (Apache, /MicroFinance) │
│  axios, withCredentials    │  ◀───────────────────────   │   /MicroFinance/backend/public/api    │
└────────────────────────────┘      {success, data}         └──────────────────────────────────────┘
                                                                                  │
                                                                                  ▼
                                                                          MySQL / MariaDB
                                                                          schema: MicroFinance
```

- **Frontend** lives in `frontend/`. In development it runs on `http://localhost:5173` and proxies legacy `/MicroFinance/api/*.php` and `/MicroFinance/uploads` calls to Apache on `http://localhost`.
- **Backend** lives in `backend/`. The JSON API is mounted at `/MicroFinance/backend/public/api/*` via the project-root `.htaccess` and `backend/public/.htaccess`.
- A single project-root `.htaccess` rewrites `/MicroFinance/` to `frontend/dist/index.html` (the React SPA shell) and forwards `/MicroFinance/backend/public/api/*` to the PHP front controller, so the SPA and API share one URL space.
- Authentication is session-cookie based (PHP native sessions). The React app calls the API with `withCredentials: true` so cookies are shared between the SPA and the JSON API on the same origin.

---

## Project structure

```
MicroFinance/
├── .htaccess                       ← Project-root routing: SPA + API under /MicroFinance/*
├── package.json                    ← Tailwind toolchain (project-root dev/build)
├── frontend/                       ← React 18 + Vite SPA
│   ├── .env.example                ← VITE_API_BASE_URL
│   ├── index.html                  ← SPA entry HTML
│   ├── installments.html           ← Standalone Vite entry (multi-page escape hatch)
│   ├── package.json
│   ├── vite.config.js              ← Dev server :5173, proxy, build inputs
│   └── src/
│       ├── main.jsx                ← ReactDOM root, BrowserRouter basename, AuthProvider
│       ├── App.jsx                 ← Route table
│       ├── styles.css              ← UI styles
│       ├── api/                    ← axios clients (auth, members, loans, committees, savings,
│       │                              installments, due-system, profile, branches, field-officers)
│       ├── context/AuthContext.jsx ← Session state (loading | unauthenticated | authenticated)
│       ├── hooks/useAuth.js
│       ├── components/
│       │   ├── ProtectedRoute.jsx
│       │   ├── LoadingScreen.jsx
│       │   ├── layout/             ← AppLayout, Sidebar, Topbar
│       │   ├── members/            ← MemberForm, MembersTable, MembersFilters
│       │   ├── committees/
│       │   ├── loans/
│       │   └── installments/
│       └── pages/                  ← Page components (see "Frontend routes" below)
│
├── backend/                        ← PHP JSON API
│   ├── public/
│   │   ├── index.php               ← JSON front controller
│   │   └── .htaccess               ← Routes non-files to index.php
│   └── app/
│       ├── Bootstrap/
│       │   ├── autoload.php        ← spl_autoload_register for App\ namespace
│       │   └── bootstrap.php       ← session, error/exception handlers, CORS
│       ├── Config/                 ← app.php, cors.php, Database.php, db.php
│       ├── Controllers/JsonApi/    ← 13 namespaced JSON controllers (see "JSON API")
│       ├── Middleware/             ← AuthMiddleware, CorsMiddleware
│       ├── Helpers/                ← JsonResponse, Request, Logger
│       ├── Routes/api.php          ← Full route manifest (single source of truth)
│       ├── Views/                  ← Legacy HTML layouts (header, sidebar, topbar, footer)
│       └── Database/
│           ├── migrations/         ← (empty, scaffolded)
│           └── seeders/            ← (empty, scaffolded)
│
├── api/                            ← Legacy PHP shims for search/notifications (used by Topbar)
├── assets/                         ← Tailwind-built CSS + legacy JS modules
├── uploads/                        ← User-uploaded files (avatars)
├── backend/tests/                  ← Hand-rolled curl integration tests
│   ├── verify_account_api.php
│   └── verify_profile_fixes.php
└── backend/storage/logs/api.log    ← API runtime log
```

---

## Getting started

### Prerequisites

- **XAMPP** (Apache + MySQL / MariaDB), with Apache serving this repository at `http://localhost/MicroFinance/`
- **PHP 8.x** with the `mysqli` extension
- **Node.js 18+** and **npm**
- A working `MicroFinance` MySQL schema (see [Database setup](#database-setup))

### Backend

No build step. The JSON API is served by Apache directly from `backend/public/`. The URL surface is:

```
http://localhost/MicroFinance/backend/public/api
```

If `mod_rewrite` is unavailable, the front controller also accepts a `?route=` query-string fallback:

```
http://localhost/MicroFinance/backend/public/index.php?route=/api/dashboard-stats
```

### Frontend (development)

```bash
cd frontend
cp .env.example .env       # set VITE_API_BASE_URL if your backend URL differs
npm install
npm run dev
```

The dev server starts on `http://localhost:5173`. It proxies `/MicroFinance/api/*.php` (legacy JSON shims) and `/MicroFinance/uploads` to Apache, so cookies set by the API are visible to the SPA in development.

### Frontend (production build)

```bash
cd frontend
npm run build
```

Static files are emitted to `frontend/dist/`. Once built, they are served by Apache at `http://localhost/MicroFinance/` via the project-root `.htaccess`.

---

## Database setup

The repository does **not** ship SQL migrations or seed files yet — `backend/app/Database/migrations/` and `backend/app/Database/seeders/` are scaffolded but empty.

Connection defaults (env-driven) are defined in `backend/app/Config/Database.php`:

| Variable        | Default            |
|-----------------|--------------------|
| `DB_HOST`       | `127.0.0.1`        |
| `DB_PORT`       | `3306`             |
| `DB_USER`       | `root`             |
| `DB_PASSWORD`   | *(empty)*          |
| `DB_DATABASE`   | `MicroFinance`     |

The legacy procedural controller connection (`backend/app/Config/db.php`) hardcodes the same defaults and sets the timezone to `Asia/Dhaka`.

Tables referenced by the application (inferred from queries in the codebase): `users`, `branches`, `committees`, `members`, `loans`, `loan_payments`, `loan_installments`, `savings`, `savings_transactions`. See `PROJECT_OVERVIEW.md` for the inferred schema.

The `users` table is expected to contain at least the columns used by the auth controller: `user_id`, `username`, `password_hash`, `full_name`, `role`, `is_active`, `avatar`.

---

## JSON API

The full route manifest lives in `backend/app/Routes/api.php`. Controllers are namespaced `App\Controllers\JsonApi\*` and are PSR-style autoloaded from `backend/app/Controllers/JsonApi/`.

### Endpoints

| Method   | Path                                       | Controller                | Action           | Auth |
|----------|--------------------------------------------|---------------------------|------------------|:----:|
| `POST`   | `/api/auth/login`                          | `AuthController`          | login            |      |
| `POST`   | `/api/auth/logout`                         | `AuthController`          | logout           | ✅   |
| `GET`    | `/api/auth/session`                        | `AuthController`          | session          |      |
| `GET`    | `/api/dashboard-stats`                     | `DashboardController`     | stats            | ✅   |
| `GET`    | `/api/profile`                             | `ProfileController`       | show             | ✅   |
| `PUT`    | `/api/profile`                             | `ProfileController`       | update           | ✅   |
| `POST`   | `/api/profile/avatar`                      | `ProfileController`       | uploadAvatar     | ✅   |
| `PUT`    | `/api/profile/password`                    | `ProfileController`       | changePassword   | ✅   |
| `GET`    | `/api/branches`                            | `BranchesController`      | index            | ✅   |
| `GET`    | `/api/field-officers`                      | `FieldOfficersController` | index            | ✅   |
| `GET`    | `/api/field-officers/summary`              | `FieldOfficersController` | summary          | ✅   |
| `GET`    | `/api/search`                              | `SearchController`        | index            | ✅   |
| `GET`    | `/api/notifications`                       | `NotificationsController` | index            | ✅   |
| `POST`   | `/api/notifications/{id}/read`             | `NotificationsController` | markRead         | ✅   |
| `GET`    | `/api/members`                             | `MembersController`       | index            | ✅   |
| `POST`   | `/api/members`                             | `MembersController`       | store            | ✅   |
| `GET`    | `/api/members/search`                      | `MembersController`       | search           | ✅   |
| `GET`    | `/api/members/{id}`                        | `MembersController`       | show             | ✅   |
| `PUT`    | `/api/members/{id}`                        | `MembersController`       | update           | ✅   |
| `DELETE` | `/api/members/{id}`                        | `MembersController`       | destroy          | ✅   |
| `GET`    | `/api/members/{id}/transactions`           | `MembersController`       | transactions     | ✅   |
| `GET`    | `/api/members/{id}/loans`                  | `MembersController`       | loans            | ✅   |
| `GET`    | `/api/members/{id}/savings`                | `MembersController`       | savings          | ✅   |
| `GET`    | `/api/committees`                          | `CommitteesController`    | index            | ✅   |
| `POST`   | `/api/committees`                          | `CommitteesController`    | store            | ✅   |
| `GET`    | `/api/committees/{id}`                     | `CommitteesController`    | show             | ✅   |
| `PUT`    | `/api/committees/{id}`                     | `CommitteesController`    | update           | ✅   |
| `DELETE` | `/api/committees/{id}`                     | `CommitteesController`    | destroy          | ✅   |
| `GET`    | `/api/committees/{id}/members`             | `CommitteesController`    | members          | ✅   |
| `POST`   | `/api/committees/{id}/members`             | `CommitteesController`    | addMember        | ✅   |
| `DELETE` | `/api/committees/{id}/members/{memberId}`  | `CommitteesController`    | removeMember     | ✅   |
| `GET`    | `/api/loans`                               | `LoansController`         | index            | ✅   |
| `POST`   | `/api/loans`                               | `LoansController`         | store            | ✅   |
| `GET`    | `/api/loans/{id}`                          | `LoansController`         | show             | ✅   |
| `PUT`    | `/api/loans/{id}`                          | `LoansController`         | update           | ✅   |
| `DELETE` | `/api/loans/{id}`                          | `LoansController`         | destroy          | ✅   |
| `POST`   | `/api/loans/{id}/status`                   | `LoansController`         | updateStatus     | ✅   |
| `POST`   | `/api/loans/{id}/payments`                 | `LoansController`         | recordPayment    | ✅   |
| `GET`    | `/api/installments`                        | `InstallmentsController`  | index            | ✅   |
| `POST`   | `/api/installments`                        | `InstallmentsController`  | store            | ✅   |
| `GET`    | `/api/installments/{id}`                   | `InstallmentsController`  | show             | ✅   |
| `PUT`    | `/api/installments/{id}`                   | `InstallmentsController`  | update           | ✅   |
| `DELETE` | `/api/installments/{id}`                   | `InstallmentsController`  | destroy          | ✅   |
| `GET`    | `/api/savings`                             | `SavingsController`       | index            | ✅   |
| `POST`   | `/api/savings`                             | `SavingsController`       | store            | ✅   |
| `GET`    | `/api/savings/member/{memberId}`           | `SavingsController`       | byMember         | ✅   |
| `POST`   | `/api/savings/deposits`                    | `SavingsController`       | deposit          | ✅   |
| `POST`   | `/api/savings/withdrawals`                 | `SavingsController`       | withdraw         | ✅   |
| `GET`    | `/api/savings/transactions`                | `SavingsController`       | transactions     | ✅   |
| `GET`    | `/api/savings/{id}`                        | `SavingsController`       | show             | ✅   |
| `PUT`    | `/api/savings/{id}`                        | `SavingsController`       | update           | ✅   |
| `DELETE` | `/api/savings/{id}`                        | `SavingsController`       | destroy          | ✅   |
| `GET`    | `/api/due-system`                          | `DueSystemController`     | index            | ✅   |
| `GET`    | `/api/due-system/overdue`                  | `DueSystemController`     | overdue          | ✅   |
| `GET`    | `/api/due-system/report`                   | `DueSystemController`     | report           | ✅   |
| `GET`    | `/api/due-system/{id}`                     | `DueSystemController`     | show             | ✅   |
| `POST`   | `/api/due-system/{id}/collect`             | `DueSystemController`     | collect          | ✅   |

Static sub-paths (`/api/savings/member/{id}`, `/api/savings/deposits`, `/api/savings/transactions`, `/api/due-system/overdue`, `/api/due-system/report`, `/api/members/search`) are matched **before** the `{id}` placeholders so they are not captured as IDs.

### Authentication

All endpoints marked with ✅ require an authenticated session. Requests without a valid session cookie receive `401 UNAUTHORIZED`. The session cookie is set on `POST /api/auth/login` and cleared on `POST /api/auth/logout`.

### Legacy JSON shims

The React Topbar also calls two legacy `.php` shims that bypass the namespaced API:

- `GET /MicroFinance/api/search.php?q=…`
- `GET /MicroFinance/api/notifications.php`

These are thin wrappers around the controllers in `backend/app/Controllers/Api/` and are proxied to Apache by the Vite dev server.

---

## Frontend routes

The full route table lives in `frontend/src/App.jsx`. All routes except `/login` are gated by `<ProtectedRoute>` (which redirects to `/login` while unauthenticated).

| Path                              | Page                          |
|-----------------------------------|-------------------------------|
| `/login`                          | `LoginPage`                   |
| `/`                               | `DashboardPage`               |
| `/installments`                   | `InstallmentsPage`            |
| `/members`                        | `MembersPage`                 |
| `/members/new`                    | `MemberFormPage` (create)     |
| `/members/:id`                    | `MemberProfilePage`           |
| `/members/:id/edit`               | `MemberFormPage` (edit)       |
| `/committees`                     | `CommitteesPage`              |
| `/committees/new`                 | `CommitteeFormPage` (create)  |
| `/committees/:id`                 | `CommitteeViewPage`           |
| `/committees/:id/edit`            | `CommitteeFormPage` (edit)    |
| `/committees/:id/members`         | `CommitteeMembersPage`        |
| `/loans`                          | `LoansPage`                   |
| `/loans/new`                      | `LoanFormPage` (create)       |
| `/loans/:id`                      | `LoanViewPage`                |
| `/loans/:id/edit`                 | `LoanFormPage` (edit)         |
| `/loans/:id/payment`              | `LoanPaymentPage`             |
| `/savings`                        | `SavingsPage`                 |
| `/savings/new`                    | `SavingsFormPage`             |
| `/savings/deposit`                | `SavingsTransactionPage` (deposit) |
| `/savings/withdraw`               | `SavingsTransactionPage` (withdraw) |
| `/savings/transactions`           | `SavingsTransactionsPage`     |
| `/due-system`                     | `DueSystemPage`               |
| `/due-system/overdue`             | `DueSystemOverduePage`        |
| `/due-system/report`              | `DueSystemReportPage`         |
| `/profile`                        | `ProfilePage`                 |
| `/profile/edit`                   | `EditProfilePage`             |
| `/profile/change-password`        | `ChangePasswordPage`          |
| `/field-officers`                 | `FieldOfficersPage`           |
| `*`                               | `NotFoundPage`                |

Because the SPA is served from `/MicroFinance/` with `BrowserRouter basename="/MicroFinance"`, all of the above are reachable at `http://localhost/MicroFinance/<route>`.

---

## JSON envelope

Every API response uses a uniform envelope.

**Success:**
```json
{
  "success": true,
  "data": { "...": "..." },
  "meta": {}
}
```

**Failure:**
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

**Standard error codes:** `UNAUTHORIZED`, `VALIDATION_ERROR`, `NOT_FOUND`, `METHOD_NOT_ALLOWED`, `INVALID_CREDENTIALS`, `NOT_IMPLEMENTED`, `INTERNAL_SERVER_ERROR`.

The PHP error and exception handlers in `backend/app/Bootstrap/bootstrap.php` convert PHP errors into `ErrorException`s and return a `500 INTERNAL_SERVER_ERROR` JSON response — never raw HTML.

---

## CORS

The API is reachable from the React dev server with credentialed cookies. The allowed-origin list lives in `backend/app/Config/cors.php` and defaults to:

```
http://localhost
http://localhost:80
http://localhost:5173
http://localhost:3000
http://127.0.0.1
http://127.0.0.1:80
http://127.0.0.1:5173
http://127.0.0.1:3000
```

The list can be overridden with the `CORS_ALLOWED_ORIGINS` environment variable.

`CorsMiddleware` emits:

```
Access-Control-Allow-Origin:      <origin>
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers:     Content-Type, X-Requested-With
Access-Control-Allow-Methods:     GET, POST, PUT, PATCH, DELETE, OPTIONS
Vary:                             Origin
```

`OPTIONS` preflight is short-circuited with `204 No Content`.

---

## Deployment

The intended deployment shape is:

1. Place the repository under the Apache document root at `MicroFinance/` so the URLs match the routes (`/MicroFinance/`, `/MicroFinance/backend/public/api`, `/MicroFinance/assets/...`).
2. Ensure `mod_rewrite` is enabled and `AllowOverride All` is set on the document root.
3. Build the SPA: `cd frontend && npm install && npm run build`. The output goes to `frontend/dist/`.
4. Configure the MySQL/MariaDB connection (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_DATABASE`, `DB_PORT`) via environment variables read by `App\Config\Database`.
5. Configure `CORS_ALLOWED_ORIGINS` if the SPA is hosted on a different origin than the API.
6. The project-root `.htaccess` already wires everything together: it serves `frontend/dist/index.html` for SPA routes, forwards `/MicroFinance/backend/public/api/*` to the PHP front controller, and serves `/MicroFinance/assets/*` from `frontend/dist/assets/`.

A single Apache `VirtualHost` is sufficient — there is no separate reverse proxy or build step required once `npm run build` has been run.

---

## Testing

The `backend/tests/` directory contains two hand-rolled curl-based integration scripts:

- `backend/tests/verify_account_api.php` — exercises login → session → profile → profile update → password change.
- `backend/tests/verify_profile_fixes.php` — exercises the profile page, edit profile, avatar upload, field-officers role gating, and branches endpoint.

Both scripts `echo` a `PASS: x, FAIL: y` summary on completion. They are invoked from the command line, not through PHPUnit:

```bash
php backend/tests/verify_account_api.php
php backend/tests/verify_profile_fixes.php
```

PHPUnit test scaffolding is not present.

---

## Contributing

1. Fork the repository.
2. Create a topic branch: `git checkout -b feat/<short-description>`.
3. Make your changes. Keep the JSON envelope and the route-manifest style consistent.
4. Run the integration scripts in `backend/tests/` to verify nothing regressed.
5. `cd frontend && npm run build` to confirm the SPA builds cleanly.
6. Open a pull request describing the change and linking any related issues.

Please do not commit `frontend/dist/`, `frontend/node_modules/`, or `backend/storage/logs/*.log` — these are already in `.gitignore`.

---

## Author

**Md Munna Sardar** — [github.com/Mdmunnasardar](https://github.com/Mdmunnasardar)

---

## License

ISC — see the project `package.json` for the declared license. A standalone `LICENSE` file is not yet bundled with the repository.
