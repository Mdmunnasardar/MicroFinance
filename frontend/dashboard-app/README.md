# `dashboard-app/`

React UI for the **Dashboard** module of MicroFinance.

## Stack

- Vite + React 18 + TypeScript
- Tailwind CSS v3
- TanStack Query v5 (data fetching)
- React Router v6
- React Hook Form + Zod
- Lucide React (icons)
- Sonner (toasts)
- date-fns

## Dev

```bash
npm install
npm run dev
```

Runs on Vite dev server. Proxies `/api/*` to `http://localhost/MicroFinance`.

## Build

```bash
npm run build
```

Outputs static assets to `dist/`.

## Wiring to PHP

The PHP side is unchanged: `dashboard/index.php` still serves the same pages. This React app is a **parallel** UI mounted at a future URL (likely `/MicroFinance/dashboard-app/`) that consumes JSON endpoints from `backend/api/`.

See `../backend/app/Controllers/DashboardController.php` for the existing PHP backend.