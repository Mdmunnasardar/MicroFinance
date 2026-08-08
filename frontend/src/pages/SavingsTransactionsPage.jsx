// SavingsTransactionsPage — savings transaction history. Mirrors
// savings/transactions.php (ID, Member, Type, Amount, Balance After, Date,
// Notes) plus stats grid, type filter, and pagination.

import '../assets/css/savings.css';
import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { savingsApi } from '../api/savingsApi';

const DEFAULT_PER_PAGE = 25;

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatDateTime(value) {
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

export default function SavingsTransactionsPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    last_page: 1,
    stats: { total_txns: 0, deposit_count: 0, withdrawal_count: 0, total_deposits: 0, total_withdrawals: 0 },
  });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);
  const [type, setType] = useState('');
  const [pendingType, setPendingType] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);

  useEffect(() => { document.title = 'Savings Transactions · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

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
      if (type) params.type = type;
      const response = await savingsApi.transactions(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      const incoming = response.meta || {};
      setMeta((current) => ({
        ...current,
        ...incoming,
        stats: incoming.stats || current.stats,
      }));
    } catch (err) {
      setError(err.message || 'Failed to load transactions.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, type]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => { if (active) load(); }, 0);
    return () => { active = false; clearTimeout(timer); };
  }, [load]);

  const handleApply = (e) => {
    if (e) e.preventDefault();
    setPage(1);
    setType(pendingType);
  };

  const handleReset = () => {
    setPendingType('');
    setPage(1);
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
        <div className="page-header">
          <div className="header-left">
            <h3 className="page-title"><i className="fa-solid fa-clock-rotate-left"></i> Savings Transaction History</h3>
            <p className="page-subtitle">All deposits and withdrawals across member savings accounts</p>
          </div>
          <div className="header-right">
            <Link to="/savings/deposit" className="savings-btn success">
              <i className="fa-solid fa-plus"></i> Deposit
            </Link>
            <Link to="/savings/withdraw" className="savings-btn warning">
              <i className="fa-solid fa-circle-arrow-up"></i> Withdraw
            </Link>
            <Link to="/savings" className="savings-btn secondary">
              <i className="fa-solid fa-arrow-left"></i> Back
            </Link>
          </div>
        </div>

        <div className="savings-stats-grid">
          <div className="savings-stat-card">
            <div className="savings-stat-icon"><i className="fa-solid fa-list"></i></div>
            <div className="savings-stat-label">Total Transactions</div>
            <div className="savings-stat-value">{stats.total_txns || 0}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-clock"></i> All time
            </div>
          </div>
          <div className="savings-stat-card">
            <div className="savings-stat-icon green"><i className="fa-solid fa-circle-arrow-down"></i></div>
            <div className="savings-stat-label">Deposits</div>
            <div className="savings-stat-value">{stats.deposit_count || 0}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-coins"></i> {formatMoney(stats.total_deposits)}
            </div>
          </div>
          <div className="savings-stat-card">
            <div className="savings-stat-icon amber"><i className="fa-solid fa-circle-arrow-up"></i></div>
            <div className="savings-stat-label">Withdrawals</div>
            <div className="savings-stat-value">{stats.withdrawal_count || 0}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-coins"></i> {formatMoney(stats.total_withdrawals)}
            </div>
          </div>
          <div className="savings-stat-card">
            <div className="savings-stat-icon purple"><i className="fa-solid fa-scale-balanced"></i></div>
            <div className="savings-stat-label">Net Flow</div>
            <div className="savings-stat-value">{formatMoney((Number(stats.total_deposits) || 0) - (Number(stats.total_withdrawals) || 0))}</div>
            <div className="savings-stat-meta">
              <i className="fa-solid fa-circle-info"></i> Deposits − Withdrawals
            </div>
          </div>
        </div>

        <div className="savings-filter-card">
          <form onSubmit={handleApply} className="savings-filter-row">
            <div className="savings-filter-group">
              <label className="savings-filter-label">
                <i className="fa-solid fa-circle-info"></i> Type
              </label>
              <select
                className="savings-filter-input"
                value={pendingType}
                onChange={(e) => setPendingType(e.target.value)}
                aria-label="Filter by transaction type"
              >
                <option value="">All Types</option>
                <option value="deposit">Deposit</option>
                <option value="withdrawal">Withdrawal</option>
              </select>
            </div>
            <div className="savings-filter-group">
              <button type="submit" className="savings-btn primary block">
                <i className="fa-solid fa-filter"></i> Apply
              </button>
            </div>
            {type ? (
              <div className="savings-filter-group">
                <button type="button" className="savings-btn secondary block" onClick={handleReset}>
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

        <div className="savings-card">
          <div className="savings-card-header">
            <h5>Transactions</h5>
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
                <p>No transactions yet</p>
                <Link to="/savings/deposit" className="savings-btn success">
                  <i className="fa-solid fa-plus"></i> Record first deposit
                </Link>
              </div>
            ) : (
              <table className="savings-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance After</th>
                    <th>Date</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((row) => (
                    <tr key={row.txn_id}>
                      <td>
                        <span className="savings-id-badge">
                          <i className="fa-solid fa-hashtag" style={{ fontSize: 8, marginRight: 2, opacity: 0.6 }}></i>
                          {row.txn_id}
                        </span>
                      </td>
                      <td>
                        <div className="savings-member">
                          <strong>{escapeHtml(row.full_name || 'N/A')}</strong>
                          <small><i className="fa-solid fa-id-card"></i> {escapeHtml(row.member_code || '')}</small>
                        </div>
                      </td>
                      <td>
                        <span className={`savings-txn-badge ${row.type}`}>
                          <i className={`fa-solid ${row.type === 'deposit' ? 'fa-circle-arrow-down' : 'fa-circle-arrow-up'}`}></i>
                          {' '}
                          {row.type === 'deposit' ? 'Deposit' : 'Withdrawal'}
                        </span>
                      </td>
                      <td className={row.type === 'deposit' ? 'savings-balance positive' : 'savings-balance negative'}>
                        {row.type === 'deposit' ? '+' : '−'}{formatMoney(row.amount)}
                      </td>
                      <td className="savings-balance">{formatMoney(row.balance_after)}</td>
                      <td>{formatDateTime(row.txn_date)}</td>
                      <td>{row.notes ? escapeHtml(row.notes) : <span className="savings-muted">—</span>}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>

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
      </div>
    </div>
  );
}
