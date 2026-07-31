import { Outlet, Link, useLocation } from 'react-router-dom';
import { LayoutDashboard } from 'lucide-react';

// Placeholder layout. The real layout will mirror the PHP sidebar/topbar.

export default function AppLayout() {
  const location = useLocation();
  return (
    <div className="min-h-screen bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
          <Link to="/" className="flex items-center gap-2 font-semibold text-brand-700">
            <LayoutDashboard className="h-5 w-5" />
            MicroFinance · Dashboard
          </Link>
          <span className="text-xs text-slate-500">{location.pathname}</span>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-6 py-8">
        <Outlet />
      </main>
    </div>
  );
}
