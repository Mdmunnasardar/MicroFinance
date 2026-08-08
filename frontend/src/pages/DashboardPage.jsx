import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { apiClient } from '../api/client';
import '../assets/css/dashboard.css';

// Maps the API's short icon name (e.g. "users") to Font Awesome classes (fa-users).
// Mirrors PHP renderStatCard() at backend/app/Views/components/stat-card.php.
const ICON_CLASS = {
  users: 'fa-users',
  'user-check': 'fa-user-check',
  'money-bill-wave': 'fa-money-bill-wave',
  'circle-dollar': 'fa-circle-dollar',
  'check-circle': 'fa-check-circle',
  clock: 'fa-clock',
  'piggy-bank': 'fa-piggy-bank',
  'triangle-exclamation': 'fa-triangle-exclamation',
};

// PHP renders the trend chip with an arrow/dot icon (fa-arrow-up / fa-arrow-down / fa-circle).
// Mirror this by reusing the delta text as-is; the icon is purely decorative.
const TREND_ICON_CLASS = {
  up: 'fa-arrow-up',
  down: 'fa-arrow-down',
  danger: 'fa-circle',
};

function StatCard({ stat }) {
  const tone = stat.tone || 'blue';
  const trend = stat.trend || 'up';
  return (
    <div className="stat-card">
      <div className="stat-header">
        <div className={`stat-icon ${tone}`}>
          <i className={`fa-solid ${ICON_CLASS[stat.icon] ?? 'fa-circle'}`}></i>
        </div>
        <div className={`stat-trend ${trend}`}>
          <i className={`fa-solid ${TREND_ICON_CLASS[trend] ?? 'fa-circle'}`}></i>
          {stat.delta}
        </div>
      </div>
      <div className="stat-value">{stat.formatted}</div>
      <div className="stat-label">{stat.label}</div>
      <div className="stat-sub">{stat.sub}</div>
    </div>
  );
}

function OverdueAlert({ count }) {
  if (!count) return null;
  return (
    <div className="overdue-alert">
      <i className="fa-solid fa-triangle-exclamation"></i>
      <span><strong>{count}</strong> overdue loans require immediate attention!</span>
      <Link to="/due-system/overdue" className="alert-link">View Details →</Link>
    </div>
  );
}

function HealthCard({ health }) {
  const safe = Math.min(Math.max(Number(health) || 0, 0), 100);
  let badge;
  if (safe >= 70) {
    badge = <span className="badge-success">✅ Your loan portfolio is healthy and performing well.</span>;
  } else if (safe >= 50) {
    badge = <span className="badge-warning">⚠️ Moderate health. Some attention needed.</span>;
  } else {
    badge = <span className="badge-danger">🔴 Critical health. Immediate action required.</span>;
  }
  return (
    <div className="health-card">
      <div className="health-header">
        <i className="fa-solid fa-heart-pulse"></i>
        <span>Loan Health</span>
      </div>
      <div className="health-value">{Math.round(safe * 10) / 10}%</div>
      <div className="health-bar">
        <div className="health-bar-fill" style={{ width: `${safe}%` }}></div>
      </div>
      <div className="health-status">
        {badge}
        <a href="#" className="health-link">View Details →</a>
      </div>
    </div>
  );
}

function TopBorrowerCard({ top }) {
  if (!top || !top.full_name) {
    return (
      <div className="top-borrower">
        <div className="top-header">
          <i className="fa-solid fa-trophy"></i>
          <span>Top Borrower</span>
        </div>
        <p className="text-muted">No borrowers found</p>
      </div>
    );
  }
  const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(top.full_name)}&background=4f46e5&color=fff&size=52`;
  return (
    <div className="top-borrower">
      <div className="top-header">
        <i className="fa-solid fa-trophy"></i>
        <span>Top Borrower</span>
      </div>
      <div className="top-member">
        <img src={avatarUrl} alt="Avatar" />
        <div>
          <h4>{top.full_name}</h4>
          <span className="member-id">ID: {top.member_id ?? 'N/A'}</span>
          <div className="top-amount">
            <span className="label">Total Borrowed</span>
            <span className="value">${Number(top.total || 0).toLocaleString()}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

function TrendChart({ data }) {
  const canvasRef = useRef(null);
  const chartRef = useRef(null);

  useEffect(() => {
    if (!canvasRef.current || !data || typeof window === 'undefined' || !window.Chart) return undefined;
    const Chart = window.Chart;

    if (chartRef.current) {
      chartRef.current.destroy();
      chartRef.current = null;
    }

    chartRef.current = new Chart(canvasRef.current.getContext('2d'), {
      type: 'line',
      data: {
        labels: data.labels || [],
        datasets: [
          {
            label: 'Loans Disbursed',
            data: data.loan_data || [],
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#4f46e5',
            fill: true,
          },
          {
            label: 'Collection',
            data: data.payment_data || [],
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#22c55e',
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 20,
              font: { family: 'Inter', size: 12, weight: '500' },
            },
            position: 'top',
          },
          tooltip: {
            callbacks: {
              label: (context) => {
                const label = context.dataset.label || '';
                if (context.parsed.y !== null) {
                  return `${label}: $${context.parsed.y.toLocaleString()}`;
                }
                return label;
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value) => `$${Number(value).toLocaleString()}`,
              font: { family: 'Inter', size: 11 },
            },
            grid: { color: 'rgba(0,0,0,0.05)' },
          },
          x: {
            grid: { display: false },
            ticks: { font: { family: 'Inter', size: 11 } },
          },
        },
        interaction: { intersect: false, mode: 'index' },
      },
    });

    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
  }, [data]);

  return <canvas id="trendChart" ref={canvasRef}></canvas>;
}

function formatDate(value) {
  if (!value) return '';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

export default function DashboardPage() {
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
  if (error) {
    return (
      <div className="error-banner" style={{ background: 'rgba(239, 68, 68, 0.08)', borderColor: '#fecaca', color: '#991b1b' }}>
        {error}
      </div>
    );
  }
  if (!stats) return null;

  const userName = stats?.user?.name || 'User';
  const overdueCount = stats?.overdue_alert?.count ?? 0;
  const statList = stats?.stats ?? [];
  const chart = stats?.chart ?? null;
  const top = stats?.top_borrower ?? null;
  const health = stats?.health ?? 0;
  const transactions = stats?.recent_transactions ?? [];
  const members = stats?.recent_members ?? [];

  return (
    <>
      <div className="dashboard-top">
        <div className="welcome-section">
          <h1>Welcome back, <span className="highlight">{userName}</span>!</h1>
          <p>Here's what's happening with your microfinance today.</p>
        </div>
        <div className="quick-actions">
          <a href="#" className="btn-quick btn-quick-primary">
            <i className="fa-solid fa-user-plus"></i> Add Member
          </a>
          <a href="#" className="btn-quick btn-quick-success">
            <i className="fa-solid fa-hand-holding-dollar"></i> Add Loan
          </a>
          <a href="#" className="btn-quick btn-quick-info">
            <i className="fa-solid fa-piggy-bank"></i> Add Savings
          </a>
        </div>
      </div>

      <OverdueAlert count={overdueCount} />

      <div className="stats-grid">
        {statList.map((stat) => <StatCard key={stat.key} stat={stat} />)}
      </div>

      <div className="dashboard-grid">
        <div className="chart-container">
          <div className="card-header-section">
            <h3><i className="fa-solid fa-chart-line"></i> Loans vs Collection</h3>
            <span className="badge-bg">Last 6 Months</span>
          </div>
          <div className="chart-wrapper">
            <TrendChart data={chart} />
          </div>
        </div>

        <div className="right-panel">
          <HealthCard health={health} />
          <TopBorrowerCard top={top} />
        </div>
      </div>

      <div className="bottom-grid">
        <div className="transaction-table">
          <div className="table-header">
            <h3><i className="fa-solid fa-clock-rotate-left"></i> Recent Transactions</h3>
            <a href="#" className="view-all">View All →</a>
          </div>
          <div className="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Member</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {transactions.length === 0 ? (
                  <tr><td colSpan={6} className="text-center text-muted">No transactions found</td></tr>
                ) : (
                  transactions.map((row, idx) => (
                    <tr key={`${row.date}-${idx}`}>
                      <td><span className="badge-type payment">{row.type}</span></td>
                      <td>{row.description}</td>
                      <td><strong>{row.member}</strong></td>
                      <td>${Number(row.amount).toLocaleString()}</td>
                      <td>{formatDate(row.date)}</td>
                      <td><span className="badge-status completed"><i className="fa-solid fa-check-circle"></i> {row.status}</span></td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        <div className="recent-members">
          <div className="table-header">
            <h3><i className="fa-solid fa-user-plus"></i> Recent Members</h3>
            <a href="#" className="view-all">View All →</a>
          </div>
          <div className="members-list">
            {members.length === 0 ? (
              <p className="text-muted">No members found</p>
            ) : (
              members.map((m) => {
                const avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(m.full_name)}&background=random&color=fff&size=36`;
                return (
                  <div className="member-item" key={m.id}>
                    <img src={avatar} alt="Avatar" />
                    <div>
                      <div className="member-name">{m.full_name}</div>
                      <div className="member-code">{m.code || 'N/A'}</div>
                    </div>
                    <div className="member-join">
                      <small>{formatDate(m.joined_at)}</small>
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </div>
      </div>
    </>
  );
}