# MicroFinance — React Frontend

The new React UI for MicroFinance. This Vite app talks to the JSON API at
`http://localhost/MicroFinance/backend/public/api` over session cookies.

## Requirements

- Node.js 18+ (`node` and `npm`).
- XAMPP/MySQL running with the legacy MicroFinance database.
- The backend front controller at `backend/public/index.php` reachable through
  Apache (no rewrite configuration required — the controller also accepts
  `index.php?route=/api/...` as a fallback).

## Configure

```bash
cp .env.example .env
```

`.env` defines `VITE_API_BASE_URL`. The default matches XAMPP at
`http://localhost/MicroFinance/backend/public/api`.

## Run

```bash
npm install
npm run dev
```

Open http://localhost:5173. Sign in with an existing MicroFinance user.

## Build

```bash
npm run build
```

Outputs static files to `dist/`.

## Layout

```text
frontend/
├── index.html
├── package.json
├── vite.config.js
├── .env.example
└── src/
    ├── main.jsx
    ├── App.jsx
    ├── styles.css
    ├── api/
    │   ├── client.js
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
    │   └── layout/{AppLayout,Sidebar,Topbar}.jsx
    └── pages/{LoginPage,DashboardPage,NotFoundPage}.jsx
```

## Notes

- The sidebar mirrors the legacy PHP navigation. Only the Dashboard is wired to
  a React page in this step; the other links open their legacy PHP URLs in a
  full page reload until those modules are migrated.
- Logout calls `/api/auth/logout` and redirects to `/login`. The legacy
  `/MicroFinance/logout.php` URL is also kept as a fallback in case the React
  app is bypassed.
