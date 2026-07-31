import { useEffect, useState } from 'react';
import { apiClient } from '../api/client';
import { useAuth } from '../hooks/useAuth';

const TONE_CLASS = {
  blue: 'blue',
  green: 'green',
  purple: 'purple',
  teal: 'teal',
  red: 'red',
  gold: 'gold',
};

const ICON_LIBRARY = {
  users: '👥',
  'user-check': '✅',
  'money-bill-wave': '💸',
  'circle-dollar': '🟢',
  'check-circle': '✔️',
  clock: '⏰',
  'piggy-bank': '🐷',
  'triangle-exclamation': '⚠️',
};

function StatCard({ stat }) {
  const tone = TONE_CLASS[stat.tone] || 'blue';
  return (
    <div className="stat-card">
      <div className="stat-header">
        <div>
          <div className="stat-label">{stat.label}</div>
          <div className="stat-value">{stat.formatted}</div>
        </div>
        <div className={`stat-icon ${tone}`}>{ICON_LIBRARY[stat.icon] ?? '•'}</div>
      </div>
      <div className={`stat-trend ${stat.trend}`}>{stat.delta}</div>
      <div className="stat-sub">{stat.sub}</div>
    </div>
  );
}

function OverdueAlert({ count }) {
  if (!count) {
    return (
      <div className="overdue-alert zero">
        <span className="pulse" style={{ background: '#10b981' }} />
        All loans are current. No overdue payments.
      </div>
    );
  }
  return (
    <div className="overdue-alert">
      <span className="pulse" />
      <strong>{count}</strong> loan{count === 1 ? '' : 's'} overdue. Review them now.
    </div>
  );
}

function ChartPanel({ data }) {
  if (!data) return null;
  const max = Math.max(1, ...data.loan_data, ...data.payment_data);
  return (
    <div className="card">
      <div className="card-header">
        <h3>Loans vs Collection</h3>
        <span className="badge-bg">Last 6 Months</span>
      </div>
      <div style={{ display: 'flex', alignItems: 'flex-end', gap: 16, height: 200 }}>
        {data.labels.map((label, idx) => (
          <div key={label} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6 }}>
            <div style={{ display: 'flex', alignItems: 'flex-end', gap: 4, width: '100%', height: '100%' }}>
              <div title={`Loan: ${data.loan_data[idx]}`}
                style={{ flex: 1, height: `${(data.loan_data[idx] / max) * 100}%`, background: 'linear-gradient(180deg, #6366f1, #4f46e5)', borderRadius: '6px 6px 0 0' }} />
              <div title={`Paid: ${data.payment_data[idx]}`}
                style={{ flex: 1, height: `${(data.payment_data[idx] / max) * 100}%`, background: 'linear-gradient(180deg, #14b8a6, #0ea5e9)', borderRadius: '6px 6px 0 0' }} />
            </div>
            <div style={{ fontSize: 11, color: 'var(--muted)' }}>{label}</div>
          </div>
        ))}
      </div>
      <div style={{ marginTop: 12, display: 'flex', gap: 16, fontSize: 12, color: 'var(--muted)' }}>
        <span><span style={{ display: 'inline-block', width: 10, height: 10, borderRadius: 2, background: '#4f46e5', marginRight: 6 }} />Loans disbursed</span>
        <span><span style={{ display: 'inline-block', width: 10, height: 10, borderRadius: 2, background: '#0ea5e9', marginRight: 6 }} />Payments collected</span>
      </div>
    </div>
  );
}

function RecentTransactions({ items }) {
  if (!items?.length) return <div className="empty">No transactions yet.</div>;
  return (
    <ul className="list">
      {items.map((t, idx) => (
        <li key={`${t.date}-${idx}`}>
          <div>
            <div className="who">{t.member || 'Unknown member'}</div>
            <div className="meta">{t.description} · {t.status}</div>
          </div>
          <div style={{ textAlign: 'right' }}>
            <div style={{ fontWeight: 600 }}>${Number(t.amount).toLocaleString()}</div>
            <div className="meta">{t.date}</div>
          </div>
        </li>
      ))}
    </ul>
  );
}

function RecentMembers({ items }) {
  if (!items?.length) return <div className="empty">No members yet.</div>;
  return (
    <ul className="list">
      {items.map((m) => (
        <li key={m.id}>
          <div>
            <div className="who">{m.full_name}</div>
            <div className="meta">{m.code}</div>
          </div>
          <div className="meta">{m.joined_at}</div>
        </li>
      ))}
    </ul>
  );
}

function TopBorrower({ data }) {
  if (!data?.full_name) {
    return <div className="empty">No loan data yet.</div>;
  }
  return (
    <div>
      <div className="who" style={{ fontSize: 16 }}>{data.full_name}</div>
      <div className="meta">Member #{data.member_id}</div>
      <div style={{ marginTop: 8, fontSize: 22, fontWeight: 700 }}>
        ${Number(data.total).toLocaleString()}
      </div>
    </div>
  );
}

export default function DashboardPage() {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => { document.title = 'Dashboard · MicroFinance'; }, []);

  useEffect(() => {
    let active = true;
    setLoading(true);
    apiClient.get('/dashboard-stats')
      .then((response) => {
        if (!active) return;
        setStats(response.data ?? response);
        setLoading(false);
      })
      .catch((err) => {
        if (!active) return;
        setError(err.message || 'Failed to load dashboard');
        setLoading(false);
      });
    return () => { active = false; };
  }, []);

  if (loading) return <p>Loading dashboard…</p>;
  if (error) return <div className="error-banner" style={{ background: 'rgba(239, 68, 68, 0.08)', borderColor: '#fecaca', color: '#991b1b' }}>{error}</div>;
  if (!stats) return null;

  return (
    <>
      <div className="welcome">
        <div>
          <h1>Welcome back, <span style={{ color: 'var(--primary)' }}>{user?.name}</span>!</h1>
          <p>Here&apos;s what&apos;s happening with your microfinance today.</p>
        </div>
        <div className="quick-actions">
          <a className="btn-quick btn-quick-primary" href="http://localhost/MicroFinance/members/add.php">+ Add Member</a>
          <a className="btn-quick btn-quick-success" href="http://localhost/MicroFinance/loans/add.php">+ Add Loan</a>
          <a className="btn-quick btn-quick-info" href="http://localhost/MicroFinance/savings/add.php">+ Add Savings</a>
        </div>
      </div>

      <OverdueAlert count={stats.overdue_alert?.count ?? 0} />

      <div className="stats-grid">
        {stats.stats.map((stat) => <StatCard key={stat.key} stat={stat} />)}
      </div>

      <div className="dashboard-grid">
        <ChartPanel data={stats.chart} />
        <div className="card">
          <div className="card-header">
            <h3>Top Borrower</h3>
            <span className="badge-bg">All time</span>
          </div>
          <TopBorrower data={stats.top_borrower} />
        </div>
      </div>

      <div className="dashboard-grid" style={{ marginTop: 20 }}>
        <div className="card">
          <div className="card-header">
            <h3>Recent Transactions</h3>
            <span className="badge-bg">Last 5</span>
          </div>
          <RecentTransactions items={stats.recent_transactions} />
        </div>
        <div className="card">
          <div className="card-header">
            <h3>Recent Members</h3>
            <span className="badge-bg">Last 5</span>
          </div>
          <RecentMembers items={stats.recent_members} />
        </div>
      </div>
    </>
  );
}
