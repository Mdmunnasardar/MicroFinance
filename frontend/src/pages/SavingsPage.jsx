// SavingsPage — list of savings accounts. Mirrors savings/index.php (PHP
// Bootstrap-themed list with 5 columns and 4 action buttons). Adds stat
// cards, search/type filters, and pagination to the same table layout.

import '../assets/css/savings.css';
import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { savingsApi } from '../api/savingsApi';

const DEFAULT_PER_PAGE = 25;

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDate(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export default function SavingsPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    last_page: 1,
    stats: { total_accounts: 0, total_balance: 0, individual_accounts: 0, group_accounts: 0, recent_accounts: 0 },
  });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);
  const [search, setSearch] = useState('');
  const [type, setType] = useState('');
  const [pendingSearch, setPendingSearch] = useState('');
  const [pendingType, setPendingType] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);

  useEffect(() => { document.title = 'Savings · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  // Hydrate from sessionStorage feedback set by form pages.
  useEffect(() => {
    const sf = window.sessionStorage?.getItem('savings_feedback');
    if (sf) {
      try {
        const parsed = JSON.parse(sf);
        if (parsed?.message) setFeedback(parsed);
      } catch (_e) { /* ignore */ }
      window.sessionStorage.removeItem('savings_feedback');
    }
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page, per_page: perPage };
      if (search.trim()) params.search = search.trim();
      if (type) params.type = type;
      const response = await savingsApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      const incoming = response.meta || {};
      setMeta((current) => ({
        ...current,
        ...incoming,
        stats: incoming.stats || current.stats,
      }));
    } catch (err) {
      setError(err.message || 'Failed to load savings accounts.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, search, type]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => { if (active) load(); }, 0);
    return () => { active = false; clearTimeout(timer); };
  }, [load]);

  const handleApplyFilters = (e) => {
    if (e) e.preventDefault();
    setPage(1);
    setSearch(pendingSearch);
    setType(pendingType);
  };

  const handleResetFilters = () => {
    setPendingSearch('');
    setPendingType('');
    setPage(1);
    setSearch('');
    setType('');
  };

  const stats = meta.stats || {};
  const total = Number(meta.total) || 0;
  const lastPage = Math.max(1, Number(meta.last_page) || 1);
  const startIndex = total === 0 ? 0 : (meta.page - 1) * meta.per_page + 1;
  const endIndex = Math.min(meta.page * meta.per_page, total);

  return (
    <div className="savings-page">
      {feedback ? (
        <div className={`savings-toast ${feedback.kind}`} role="status">
          <span className="savings-toast-icon" aria-hidden>{feedback.kind === 'success' ? '✓' : '!'}</span>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      <div className="container">
        {/* Page header — mirrors savings/index.php lines 56-66 */}
        <div className="page-header">
          <div className="header-left">
            <h3 className="page-title"><i className="fa-solid fa-piggy-bank"></i> Savings Accounts</h3>
            <p className="page-subtitle">Manage member savings accounts, deposits, and withdrawals</p>
          </div>
        </div>

        {/* Statistics cards — extension of the PHP list with summary tiles */}
        <div className="savings-stats-grid">
          <div className="savings-stat-card">
            <div className="savings-stat-icon"><i className="fa-solid fa-coins"></i></div>
            <div className="savings-stat-label">Total Balance</div>
            <div className="savings-stat-value">{formatMoney(stats.total_balance)}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-wallet"></i> {stats.total_accounts || 0} accounts
            </div>
          </div>
          <div className="savings-stat-card">
            <div className="savings-stat-icon green"><i className="fa-solid fa-user"></i></div>
            <div className="savings-stat-label">Individual Accounts</div>
            <div className="savings-stat-value">{stats.individual_accounts || 0}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-circle-check"></i> Active
            </div>
          </div>
          <div className="savings-stat-card">
            <div className="savings-stat-icon amber"><i className="fa-solid fa-users"></i></div>
            <div className="savings-stat-label">Group Accounts</div>
            <div className="savings-stat-value">{stats.group_accounts || 0}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-circle-info"></i> Committee-linked
            </div>
          </div>
          <div className="savings-stat-card">
            <div className="savings-stat-icon purple"><i className="fa-solid fa-clock"></i></div>
            <div className="savings-stat-label">Recent Activity</div>
            <div className="savings-stat-value">{stats.recent_accounts || 0}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-calendar"></i> Last 7 days
            </div>
          </div>
        </div>

        {/* Action buttons — mirrors savings/index.php lines 58-66 */}
        <div className="savings-action-row">
          <Link to="/savings/new" className="savings-btn primary">
            <i className="fa-solid fa-plus"></i> Add Account
          </Link>
          <Link to="/savings/deposit" className="savings-btn success">
            <i className="fa-solid fa-circle-arrow-down"></i> Deposit
          </Link>
          <Link to="/savings/withdraw" className="savings-btn warning">
            <i className="fa-solid fa-circle-arrow-up"></i> Withdraw
          </Link>
          <Link to="/savings/transactions" className="savings-btn info">
            <i className="fa-solid fa-list"></i> Transactions
          </Link>
        </div>

        {/* Filters */}
        <div className="savings-filter-card">
          <form onSubmit={handleApplyFilters} className="savings-filter-row">
            <div className="savings-filter-group">
              <label className="savings-filter-label">
                <i className="fa-solid fa-search"></i> Search
              </label>
              <input
                type="text"
                className="savings-filter-input"
                placeholder="Member name or code..."
                value={pendingSearch}
                onChange={(e) => setPendingSearch(e.target.value)}
                aria-label="Search savings"
              />
            </div>
            <div className="savings-filter-group">
              <label className="savings-filter-label">
                <i className="fa-solid fa-circle-info"></i> Type
              </label>
              <select
                className="savings-filter-input"
                value={pendingType}
                onChange={(e) => setPendingType(e.target.value)}
                aria-label="Filter by saving type"
              >
                <option value="">All Types</option>
                <option value="individual">Individual</option>
                <option value="group">Group</option>
              </select>
            </div>
            <div className="savings-filter-group">
              <button type="submit" className="savings-btn primary block">
                <i className="fa-solid fa-filter"></i> Apply
              </button>
            </div>
            {(search || type) ? (
              <div className="savings-filter-group">
                <button type="button" className="savings-btn secondary block" onClick={handleResetFilters}>
                  <i className="fa-solid fa-rotate"></i> Reset
                </button>
              </div>
            ) : null}
          </form>
        </div>

        {error ? (
          <div className="savings-alert" role="alert">
            <i className="fa-solid fa-circle-exclamation"></i> {error}
          </div>
        ) : null}

        {/* Accounts table — mirrors savings/index.php lines 71-115 */}
        <div className="savings-card">
          <div className="savings-card-header">
            <h5>Savings Accounts</h5>
            <span className="savings-card-count">{total} total</span>
          </div>
          <div className="savings-table-responsive">
            {loading ? (
              <div className="savings-loading">
                <i className="fa-solid fa-spinner fa-spin"></i> Loading…
              </div>
            ) : items.length === 0 ? (
              <div className="savings-empty">
                <i className="fa-solid fa-inbox"></i>
                <p>No savings accounts found</p>
                <Link to="/savings/new" className="savings-btn primary">
                  <i className="fa-solid fa-plus"></i> Create first account
                </Link>
              </div>
            ) : (
              <table className="savings-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Type</th>
                    <th>Balance</th>
                    <th>Last Transaction</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((row) => (
                    <tr key={row.saving_id}>
                      <td>
                        <span className="savings-id-badge">
                          <i className="fa-solid fa-hashtag" style={{ fontSize: 8, marginRight: 2, opacity: 0.6 }}></i>
                          {row.saving_id}
                        </span>
                      </td>
                      <td>
                        <div className="savings-member">
                          <strong>{escapeHtml(row.full_name || 'N/A')}</strong>
                          <small><i className="fa-solid fa-id-card"></i> {escapeHtml(row.member_code || '')}</small>
                        </div>
                      </td>
                      <td>
                        <span className={`savings-type-badge ${row.saving_type || 'individual'}`}>
                          {row.saving_type ? row.saving_type.charAt(0).toUpperCase() + row.saving_type.slice(1) : 'Individual'}
                        </span>
                      </td>
                      <td className="savings-balance">{formatMoney(row.balance)}</td>
                      <td>{formatDate(row.last_transaction_date)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>

        {/* Pagination */}
        {total > 0 ? (
          <div className="savings-pagination">
            <div className="savings-pagination-info">
              Showing <strong>{startIndex}</strong> – <strong>{endIndex}</strong> of <strong>{total}</strong>
            </div>
            <div className="savings-pagination-controls">
              <label className="savings-pagination-size">
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
                className="savings-btn secondary"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={loading || page <= 1}
              >
                ‹ Prev
              </button>
              <span className="savings-pagination-current">
                Page <strong>{meta.page}</strong> of <strong>{lastPage}</strong>
              </span>
              <button
                type="button"
                className="savings-btn secondary"
                onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                disabled={loading || page >= lastPage}
              >
                Next ›
              </button>
            </div>
          </div>
        ) : null}

        <div className="savings-back-wrap">
          <Link to="/" className="savings-btn secondary">
            <i className="fa-solid fa-arrow-left"></i> Back to Dashboard
          </Link>
        </div>
      </div>
    </div>
  );
}
