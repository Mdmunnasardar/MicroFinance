# MicroFinance

A server-rendered PHP microfinance management application (XAMPP / MySQL / Bootstrap).

This is **mvp1**, a folder-structure pass. Code has been **moved** but not refactored: every page mixes view + DB query + business logic and that is by design for this step. The split into proper Controllers / Models / Services / Repositories will happen in a later MVP.

---

## Top-level folder map

| Folder | Why it exists |
|--------|---------------|
| `backend/` | All server-side PHP — controllers, config, views, models, services, tests, storage. |
| `frontend/` | Browser-side assets — CSS, JS, future React/TS sources, images. |
| `api/` | URL shim — delegates to `backend/app/Controllers/Api/*`. Keeps existing AJAX URLs working. |
| `members/`, `Committees/`, `loans/`, `installments/`, `savings/`, `due_system/`, `profile/` | URL shims — one tiny `require_once` per file that delegates to the matching controller. Keeps existing page URLs working. |
| `dashboard.php`, `login.php`, `logout.php`, `index.php`, `profile.php` | Root URL shims — delegate to the matching controller in `backend/app/Controllers/`. |
| `assets/` | Windows directory junction → `frontend/src/assets/`. Allows existing `assets/css/...` and `assets/js/...` URL paths to resolve to the new asset location without rewriting every `<link>`/`<script>` in the codebase. |
| `uploads/` | User-uploaded files (avatars). Kept at repo root because Apache needs to serve them at the same URL. |
| `node_modules/`, `package.json`, `package-lock.json` | Tailwind CSS build toolchain. Will eventually move into `frontend/` once a proper frontend build pipeline is wired up. |
| `CLEANUP_REPORT.md`, `PROJECT_OVERVIEW.md` | Project-level documentation (mvp1 cleanup pass + architecture overview). |

---

## `backend/` structure

```
backend/
├── app/
│   ├── Controllers/      ← one Controller per page (still mixed: view + query + logic)
│   │   ├── Auth/         ← LoginController, LogoutController
│   │   ├── Api/          ← AJAX endpoints (Notifications, Search)
│   │   ├── Members/      ← list, create, update, delete, view, quick_view, loan_chart, export
│   │   ├── Committees/   ← list, create, update, delete, view, members, assign, toggle-status
│   │   ├── Loans/        ← list, create, update, delete, view, payment
│   │   ├── Installments/ ← list, payment, payment_list, edit, delete
│   │   ├── Savings/      ← list, add, edit, delete, deposit, withdraw, member, report, transactions
│   │   ├── DueSystem/    ← list, overdue, report
│   │   ├── Profile/      ← edit, change-password, upload-avatar
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php
│   │   └── HomeController.php
│   ├── Views/
│   │   ├── layouts/      ← header.php, footer.php, sidebar.php, topbar.php
│   │   └── components/   ← reusable dashboard/member renderers
│   │       └── member/   ← page-header, stats, filters, table, modal
│   ├── Config/           ← db.php + future env/config files (README.md inside)
│   ├── Models/           ← (empty — future MVP)
│   ├── Services/         ← (empty — future MVP)
│   ├── Repositories/     ← (empty — future MVP)
│   ├── Middleware/       ← (empty — future MVP)
│   ├── Helpers/          ← (empty — future MVP)
│   ├── Routes/           ← (empty — future MVP)
│   └── Database/
│       ├── migrations/   ← (empty + README)
│       └── seeders/      ← (empty + README)
├── storage/
│   ├── logs/             ← (empty — runtime logs)
│   └── uploads/          ← (empty — moved avatars back to repo-root `uploads/`)
└── tests/                ← (empty + README)
```

---

## `frontend/` structure

```
frontend/
└── src/
    ├── assets/
    │   ├── css/          ← dashboard.css, members.css, committees.css, loans.css, profile.css, app.css (Tailwind), input.css (Tailwind source), icons/, images/
    │   └── js/           ← dashboard.js, members.js, committees.js, profile.js
    ├── components/       ← (empty — future MVP for reusable JS components)
    ├── pages/            ← (empty — future MVP for page-level bundles)
    ├── layouts/          ← (empty — future MVP for layout templates)
    └── api/              ← (empty — future MVP for typed API client)
```

---

## Why this folder structure?

- **`backend/` vs `frontend/`** makes the deployment unit boundary explicit. In a future step, the frontend can move behind a CDN while the backend stays on PHP.
- **`Controllers/`** follows a domain-bounded convention (one folder per business domain — Members, Loans, etc.) so related code stays grouped.
- **`Views/`** holds the shared layout chrome (header/footer/sidebar/topbar) and reusable dashboard renderers. A future MVP will separate HTML from logic.
- **`Config/`, `Models/`, `Services/`, `Repositories/`, `Middleware/`, `Helpers/`** are scaffolded empty so the next MVP can split the currently-mixed controllers into proper layers without moving files again.
- **`Database/migrations/` + `seeders/`** provide a place to version the schema once it stabilizes.
- **`storage/logs/`** is where future runtime logs (errors, audits) should land instead of `error_log()`.
- **`tests/`** is the home for PHPUnit / integration tests once they're introduced.

---

## URL routing

This pass **preserves all existing URLs** for backward compatibility via thin PHP shim files:

- `http://localhost/MicroFinance/dashboard.php` → shim → `backend/app/Controllers/DashboardController.php`
- `http://localhost/MicroFinance/members/add.php` → shim → `backend/app/Controllers/Members/MemberCreateController.php`
- `http://localhost/MicroFinance/api/search.php` → shim → `backend/app/Controllers/Api/SearchController.php`

Each shim is exactly three lines: a comment, a `require_once`, and an exit. The next MVP can introduce a real router and remove these shims.

---

## Asset serving

`assets/` is a Windows directory junction pointing to `frontend/src/assets/`. This means:
- Browser requests `http://localhost/MicroFinance/assets/css/dashboard.css` resolve to `frontend/src/assets/css/dashboard.css` on disk.
- All existing `<link>` and `<script>` tags in the codebase work unchanged.
- In production on Linux, replace the junction with a symlink or configure Apache to serve `frontend/src/assets/` at the `/MicroFinance/assets/` URL prefix.

---

## What this pass did NOT do

- Did **not** refactor any controller. Every page still mixes HTML + DB queries + business logic.
- Did **not** change SQL, calculations, or business rules.
- Did **not** rename `Committees/` folder (PascalCase) — left for a future dedicated pass.
- Did **not** extract shared code (status-config array, loan-status badges, member-fetch SQL) — these remain duplicated as in the previous MVP.
- Did **not** introduce a router — kept the URL shim approach to preserve every URL path.

See `CLEANUP_REPORT.md` for the dead-code pass that preceded this restructure.