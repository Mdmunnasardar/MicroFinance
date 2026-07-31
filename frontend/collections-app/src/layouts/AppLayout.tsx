import { Outlet, NavLink, Link } from 'react-router-dom';
import { Coins } from 'lucide-react';

const navItems = [
  { to: '.', label: 'Installments' },
  { to: 'payment', label: 'Record Payment' },
  { to: 'payment-list', label: 'Payment List' },
  { to: 'overdue', label: 'Overdue' },
  { to: 'due-list', label: 'Due List' },
  { to: 'report', label: 'Report' },
];

export default function AppLayout() {
  return (
    <div className="min-h-screen bg-slate-50">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
          <Link to="." className="flex items-center gap-2 font-semibold text-brand-700">
            <Coins className="h-5 w-5" /> MicroFinance · Collections
          </Link>
        </div>
        <nav className="mx-auto flex max-w-7xl gap-4 px-6 pb-3 text-sm">
          {navItems.map((n) => (
            <NavLink key={n.to} to={n.to} end={n.to === '.'}
              className={({ isActive }) => `rounded-md px-3 py-1.5 ${isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'}`}>
              {n.label}
            </NavLink>
          ))}
        </nav>
      </header>
      <main className="mx-auto max-w-7xl px-6 py-8">
        <Outlet />
      </main>
    </div>
  );
}