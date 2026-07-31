import { Users, Wallet, Banknote, AlertTriangle } from 'lucide-react';
import type { DashboardSummary } from '../../types';
import { formatINR, formatNumber } from '../../lib/format';

interface Props {
  summary: DashboardSummary;
}

const tiles = (s: DashboardSummary) => [
  {
    icon: Users,
    label: 'Total Members',
    value: formatNumber(s.totalMembers),
    tone: 'text-brand-600',
  },
  {
    icon: Banknote,
    label: 'Active Loans',
    value: formatNumber(s.activeLoans),
    tone: 'text-emerald-600',
  },
  {
    icon: Wallet,
    label: 'Total Savings',
    value: formatINR(s.totalSavings),
    tone: 'text-sky-600',
  },
  {
    icon: AlertTriangle,
    label: 'Overdue',
    value: formatINR(s.overdueAmount),
    tone: s.overdueAmount > 0 ? 'text-rose-600' : 'text-slate-500',
  },
];

export default function StatGrid({ summary }: Props) {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {tiles(summary).map(({ icon: Icon, label, value, tone }) => (
        <div key={label} className="stat-card">
          <Icon className={`h-8 w-8 ${tone}`} />
          <div>
            <p className="text-xs uppercase tracking-wide text-slate-500">{label}</p>
            <p className="text-xl font-semibold">{value}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
