// LoansPage — list page mirroring loans/index.php. Deep-blue header with
// "Loan Management", four stat cards (Total Portfolio / Active Loans /
// Overdue Loans / Collection Rate), filter row, full loans table, delete
// confirmation modal, toast feedback, and Back to Dashboard button.

import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { loansApi } from '../api/loansApi';
import LoansTable from '../components/loans/LoansTable';
import LoansFilters from '../components/loans/LoansFilters';

const DEFAULT_PER_PAGE = 25;
const EMPTY_FILTERS = { search: '', status: '' };
const LEGACY_BASE = '/MicroFinance';

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatPercent(value) {
  const n = Number(value) || 0;
  return `${n.toFixed(1)}%`;
}

export default function LoansPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    last_page: 1,
    stats: {
      total_loans: 0,
      active_loans: 0,
      overdue_loans: 0,
      closed_loans: 0,
      total_portfolio: 0,
      total_paid: 0,
      collection_rate: 0,
      recent_loans: 0,
    },
  });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);
  const [filters, setFilters] = useState({ ...EMPTY_FILTERS });
  const [pendingFilters, setPendingFilters] = useState({ ...EMPTY_FILTERS });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [confirmDelete, setConfirmDelete] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => { document.title = 'Loans · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  // Hydrate from sessionStorage feedback (set by form page after redirect).
  useEffect(() => {
    const sessionFeedback = window.sessionStorage?.getItem('loans_feedback');
    if (sessionFeedback) {
      try {
        const parsed = JSON.parse(sessionFeedback);
        if (parsed?.message) setFeedback(parsed);
      } catch (_e) {
        // ignore
      }
      window.sessionStorage.removeItem('loans_feedback');
    }
  }, []);

  const loadLoans = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page, per_page: perPage };
      if (filters.search.trim()) params.search = filters.search.trim();
      if (filters.status) params.status = filters.status;
      const response = await loansApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      const incoming = response.meta || {};
      setMeta((current) => ({
        ...current,
        ...incoming,
        stats: incoming.stats || current.stats,
      }));
    } catch (err) {
      setError(err.message || 'Failed to load loans.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, filters]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      loadLoans();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadLoans]);

  const handleFilterSubmit = () => {
    setPage(1);
    setFilters({ ...pendingFilters });
  };

  const handleFilterReset = () => {
    setPendingFilters({ ...EMPTY_FILTERS });
    setPage(1);
    setFilters({ ...EMPTY_FILTERS });
  };

  const handleDelete = async () => {
    if (!confirmDelete) return;
    setBusy(true);
    try {
      await loansApi.remove(confirmDelete.loan_id);
      setFeedback({ kind: 'success', message: `Loan "${confirmDelete.loan_code}" deleted.` });
      setConfirmDelete(null);
      await loadLoans();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to delete loan.' });
      setConfirmDelete(null);
    } finally {
      setBusy(false);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const stats = meta.stats || {};
  const total = Number(meta.total) || items.length;
  const lastPage = Math.max(1, Number(meta.last_page) || 1);
  const startIndex = items.length === 0 ? 0 : (meta.page - 1) * meta.per_page + 1;
  const endIndex = Math.min(meta.page * meta.per_page, total);

  return (
    <div className="loans-page">
      {feedback ? (
        <div className={`loans-toast ${feedback.kind}`} role="status">
          <span className="loans-toast-icon" aria-hidden>{feedback.kind === 'success' ? '✓' : '!'}</span>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      {/* Floating orbs — mirrors loans/index.php lines 750-752 */}
      <div className="orb orb-1"></div>
      <div className="orb orb-2"></div>
      <div className="orb orb-3"></div>

      <div className="container">
        {/* Page header — mirrors loans/index.php lines 757-780 */}
        <div className="page-header">
          <div className="header-left">
            <div className="header-icon">
              <i className="fa-solid fa-hand-holding-usd"></i>
            </div>
            <div>
              <div className="header-title">
                <span>Loan</span> Management
                <span className="header-subtitle">
                  <i className="fa-solid fa-users"></i> {stats.total_loans || 0} Active Loans
                </span>
              </div>
            </div>
          </div>
          <div className="header-right">
            <Link to="/loans/new" className="btn btn-primary">
              <i className="fa-solid fa-plus-circle"></i> New Loan
            </Link>
            <button type="button" className="btn btn-secondary" onClick={handlePrint} aria-label="Print">
              <i className="fa-solid fa-print"></i>
            </button>
          </div>
        </div>

        {/* Statistics cards — mirrors loans/index.php lines 783-820 */}
        <div className="stats-grid">
          <div className="stat-card">
            <div className="stat-icon"><i className="fa-solid fa-coins"></i></div>
            <div className="stat-label">Total Portfolio</div>
            <div className="stat-value">{formatMoney(stats.total_portfolio)}</div>
            <div className="stat-change positive">
              <i className="fa-solid fa-arrow-up"></i> {stats.total_loans || 0} Total Loans
            </div>
          </div>

          <div className="stat-card">
            <div className="stat-icon"><i className="fa-solid fa-check-circle"></i></div>
            <div className="stat-label">Active Loans</div>
            <div className="stat-value">{stats.active_loans || 0}</div>
            <div className="stat-change positive">
              <i className="fa-solid fa-check"></i> {stats.total_loans > 0
                ? `${((Number(stats.active_loans || 0) / Number(stats.total_loans || 1)) * 100).toFixed(1)}% Active`
                : '0% Active'}
            </div>
          </div>

          <div className="stat-card">
            <div className="stat-icon"><i className="fa-solid fa-triangle-exclamation"></i></div>
            <div className="stat-label">Overdue Loans</div>
            <div className="stat-value">{stats.overdue_loans || 0}</div>
            <div className={`stat-change ${stats.overdue_loans > 0 ? 'negative' : 'positive'}`}>
              <i className={`fa-solid ${stats.overdue_loans > 0 ? 'fa-arrow-up' : 'fa-check'}`}></i>
              {stats.overdue_loans > 0 ? 'Needs Attention' : 'All Good'}
            </div>
          </div>

          <div className="stat-card">
            <div className="stat-icon"><i className="fa-solid fa-chart-line"></i></div>
            <div className="stat-label">Collection Rate</div>
            <div className="stat-value">{formatPercent(stats.collection_rate)}</div>
            <div className="stat-change positive">
              <i className="fa-solid fa-clock"></i> {stats.recent_loans || 0} New (7 days)
            </div>
          </div>
        </div>

        {/* Filter row */}
        <div className="loans-filter-section">
          <LoansFilters
            value={pendingFilters}
            onChange={setPendingFilters}
            onSubmit={handleFilterSubmit}
            onReset={handleFilterReset}
          />
        </div>

        {/* Error */}
        {error ? (
          <div className="loans-alert" role="alert">{error}</div>
        ) : null}

        {/* Loading */}
        {loading ? (
          <div className="loans-loading">
            <i className="fa-solid fa-spinner fa-spin"></i> Loading loans…
          </div>
        ) : (
          <LoansTable items={items} onDelete={setConfirmDelete} />
        )}

        {/* Pagination */}
        <div className="loans-pagination">
          <div className="loans-pagination-info">
            Showing <strong>{startIndex}</strong> – <strong>{endIndex}</strong> of <strong>{total}</strong>
          </div>
          <div className="loans-pagination-controls">
            <label className="loans-pagination-size">
              <span>Rows</span>
              <select
                value={perPage}
                onChange={(event) => {
                  setPage(1);
                  setPerPage(Number(event.target.value));
                }}
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
              className="btn btn-secondary"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={loading || page <= 1}
            >
              ‹ Prev
            </button>
            <span className="loans-pagination-current">
              Page <strong>{meta.page}</strong> of <strong>{lastPage}</strong>
            </span>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
              disabled={loading || page >= lastPage}
            >
              Next ›
            </button>
          </div>
        </div>

        {/* Back to Dashboard — mirrors loans/index.php lines 944-949 */}
        <div className="loans-back-wrap">
          <Link to="/" className="back-button">
            <i className="fa-solid fa-arrow-left"></i>
            <span className="text">Back to Dashboard</span>
          </Link>
        </div>
      </div>

      {/* Delete confirmation modal */}
      {confirmDelete ? (
        <div className="loans-backdrop" role="dialog" aria-modal="true">
          <div className="loans-modal danger">
            <div className="loans-modal-head">
              <div className="loans-modal-icon danger" aria-hidden><i className="fa-solid fa-trash"></i></div>
              <div>
                <h3>Delete loan?</h3>
                <p>"{confirmDelete.loan_code}" ({confirmDelete.full_name || 'N/A'})</p>
              </div>
            </div>
            <div className="loans-modal-body">
              <p style={{ margin: 0, color: '#7A9BCB', fontSize: 13, lineHeight: 1.5 }}>
                This action cannot be undone. Loans with recorded payments cannot be deleted.
              </p>
              <div className="loans-modal-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setConfirmDelete(null)} disabled={busy}>Cancel</button>
                <button type="button" className="btn btn-danger" onClick={handleDelete} disabled={busy}>
                  <i className="fa-solid fa-trash"></i> {busy ? 'Deleting…' : 'Delete loan'}
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {/* Reference to the legacy export-style PHP print helper is intentionally
          omitted — the React print() path above preserves the same behavior. */}
      <span style={{ display: 'none' }} aria-hidden data-legacy-base={LEGACY_BASE}></span>
    </div>
  );
}