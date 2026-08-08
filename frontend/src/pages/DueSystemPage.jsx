// DueSystemPage — mirrors due_system/index.php. Six-column table (Loan
// Code, Member, Loan, Paid, Remaining, Status) with the same Paid /
// Near Close / Due bucketing. Adds stats cards and pagination.

import '../assets/css/due-system.css';
import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { dueSystemApi } from '../api/dueSystemApi';

const DEFAULT_PER_PAGE = 25;

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

const STATUS_META = {
  paid:      { className: 'due-badge paid',      label: 'Paid' },
  near_close:{ className: 'due-badge near_close', label: 'Near Close' },
  due:       { className: 'due-badge due',       label: 'Due' },
};

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export default function DueSystemPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    last_page: 1,
    stats: { total_loans: 0, paid_count: 0, near_close_count: 0, due_count: 0, total_remaining: 0 },
  });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);
  const [search, setSearch] = useState('');
  const [pendingSearch, setPendingSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);

  useEffect(() => { document.title = 'Due List · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  useEffect(() => {
    const sf = window.sessionStorage?.getItem('due_feedback');
    if (sf) {
      try {
        const parsed = JSON.parse(sf);
        if (parsed?.message) setFeedback(parsed);
      } catch (_e) { /* ignore */ }
      window.sessionStorage.removeItem('due_feedback');
    }
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page, per_page: perPage };
      if (search.trim()) params.search = search.trim();
      const response = await dueSystemApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      const incoming = response.meta || {};
      setMeta((current) => ({ ...current, ...incoming, stats: incoming.stats || current.stats }));
    } catch (err) {
      setError(err.message || 'Failed to load due list.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, search]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => { if (active) load(); }, 0);
    return () => { active = false; clearTimeout(timer); };
  }, [load]);

  const handleApply = (e) => {
    if (e) e.preventDefault();
    setPage(1);
    setSearch(pendingSearch);
  };

  const handleReset = () => {
    setPendingSearch('');
    setPage(1);
    setSearch('');
  };

  const stats = meta.stats || {};
  const total = Number(meta.total) || 0;
  const lastPage = Math.max(1, Number(meta.last_page) || 1);
  const startIndex = total === 0 ? 0 : (meta.page - 1) * meta.per_page + 1;
  const endIndex = Math.min(meta.page * meta.per_page, total);

  return (
    <div className="due-page">
      {feedback ? (
        <div className={`due-toast ${feedback.kind}`} role="status">
          <span className="due-toast-icon" aria-hidden>{feedback.kind === 'success' ? '✓' : '!'}</span>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      <div className="container">
        <div className="page-header">
          <div className="header-left">
            <h3 className="page-title"><i className="fa-solid fa-chart-column"></i> Due List</h3>
            <p className="page-subtitle">Loans grouped by Paid, Near Close, and Due</p>
          </div>
          <div className="header-right">
            <Link to="/due-system/overdue" className="due-btn danger">
              <i className="fa-solid fa-triangle-exclamation"></i> Overdue
            </Link>
            <Link to="/due-system/report" className="due-btn info">
              <i className="fa-solid fa-chart-pie"></i> Report
            </Link>
          </div>
        </div>

        <div className="due-stats-grid">
          <div className="due-stat-card">
            <div className="due-stat-icon"><i className="fa-solid fa-coins"></i></div>
            <div className="due-stat-label">Total Remaining</div>
            <div className="due-stat-value">{formatMoney(stats.total_remaining)}</div>
            <div className="due-stat-meta">
              <i className="fa-solid fa-wallet"></i> {stats.total_loans || 0} loans
            </div>
          </div>
          <div className="due-stat-card">
            <div className="due-stat-icon green"><i className="fa-solid fa-check-circle"></i></div>
            <div className="due-stat-label">Paid</div>
            <div className="due-stat-value">{stats.paid_count || 0}</div>
            <div className="due-stat-meta"><i className="fa-solid fa-check"></i> Fully paid</div>
          </div>
          <div className="due-stat-card">
            <div className="due-stat-icon amber"><i className="fa-solid fa-hourglass-half"></i></div>
            <div className="due-stat-label">Near Close</div>
            <div className="due-stat-value">{stats.near_close_count || 0}</div>
            <div className="due-stat-meta"><i className="fa-solid fa-circle-info"></i> Remaining &lt; 5,000</div>
          </div>
          <div className="due-stat-card">
            <div className="due-stat-icon red"><i className="fa-solid fa-circle-exclamation"></i></div>
            <div className="due-stat-label">Due</div>
            <div className="due-stat-value">{stats.due_count || 0}</div>
            <div className="due-stat-meta"><i className="fa-solid fa-triangle-exclamation"></i> Remaining ≥ 5,000</div>
          </div>
        </div>

        <div className="due-filter-card">
          <form onSubmit={handleApply} className="due-filter-row">
            <div className="due-filter-group">
              <label className="due-filter-label">
                <i className="fa-solid fa-search"></i> Search
              </label>
              <input
                type="text"
                className="due-filter-input"
                placeholder="Loan code or member name..."
                value={pendingSearch}
                onChange={(e) => setPendingSearch(e.target.value)}
                aria-label="Search due list"
              />
            </div>
            <div className="due-filter-group">
              <button type="submit" className="due-btn primary block">
                <i className="fa-solid fa-filter"></i> Apply
              </button>
            </div>
            {search ? (
              <div className="due-filter-group">
                <button type="button" className="due-btn secondary block" onClick={handleReset}>
                  <i className="fa-solid fa-rotate"></i> Reset
                </button>
              </div>
            ) : null}
          </form>
        </div>

        {error ? (
          <div className="due-alert" role="alert">
            <i className="fa-solid fa-circle-exclamation"></i> {error}
          </div>
        ) : null}

        <div className="due-card">
          <div className="due-card-header">
            <h5><i className="fa-solid fa-list"></i> Due List</h5>
            <span className="due-card-count">{total} total</span>
          </div>
          <div className="due-table-responsive">
            {loading ? (
              <div className="due-loading">
                <i className="fa-solid fa-spinner fa-spin"></i> Loading…
              </div>
            ) : items.length === 0 ? (
              <div className="due-empty">
                <i className="fa-solid fa-inbox"></i>
                <p>No due records found</p>
              </div>
            ) : (
              <table className="due-table">
                <thead>
                  <tr>
                    <th>Loan Code</th>
                    <th>Member</th>
                    <th>Loan</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((row) => {
                    const statusKey = row.due_status || 'due';
                    const statusMeta = STATUS_META[statusKey] || STATUS_META.due;
                    return (
                      <tr key={row.loan_id}>
                        <td><strong>{escapeHtml(row.loan_code)}</strong></td>
                        <td>{escapeHtml(row.full_name || 'N/A')}</td>
                        <td>{formatMoney(row.principal_amount)}</td>
                        <td>{formatMoney(row.total_paid)}</td>
                        <td className="due-remaining">
                          <strong>{formatMoney(row.remaining)}</strong>
                        </td>
                        <td>
                          <span className={statusMeta.className}>
                            {statusMeta.label}
                          </span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            )}
          </div>
        </div>

        {total > 0 ? (
          <div className="due-pagination">
            <div className="due-pagination-info">
              Showing <strong>{startIndex}</strong> – <strong>{endIndex}</strong> of <strong>{total}</strong>
            </div>
            <div className="due-pagination-controls">
              <label className="due-pagination-size">
                <span>Rows</span>
                <select
                  value={perPage}
                  onChange={(e) => { setPage(1); setPerPage(Number(e.target.value)); }}
                  disabled={loading}
                >
                  <option value={10}>10</option>
                  <option value={25}>25</option>
                  <option value={50}>50</option>
                  <option value={100}>100</option>
                </select>
              </label>
              <button
                type="button"
                className="due-btn secondary"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={loading || page <= 1}
              >
                ‹ Prev
              </button>
              <span className="due-pagination-current">
                Page <strong>{meta.page}</strong> of <strong>{lastPage}</strong>
              </span>
              <button
                type="button"
                className="due-btn secondary"
                onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                disabled={loading || page >= lastPage}
              >
                Next ›
              </button>
            </div>
          </div>
        ) : null}

        <div className="due-back-wrap">
          <Link to="/" className="due-btn secondary">
            <i className="fa-solid fa-arrow-left"></i> Back to Dashboard
          </Link>
        </div>
      </div>
    </div>
  );
}
