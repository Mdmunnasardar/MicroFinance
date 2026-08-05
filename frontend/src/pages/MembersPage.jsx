// MembersPage — list page mirroring members/index.php + MembersListController.
// Stats grid, filter row, action header (Add Member), table,
// pagination. Keeps the same Bootstrap-style markup as the PHP view so the
// CSS already in members.css maps 1:1; the small CSS block under `.members-page`
// adds the few extras (filter row layout, empty state, stat spacing).

import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { membersApi } from '../api/membersApi';
import MembersFilters from '../components/members/MembersFilters';
import MembersTable from '../components/members/MembersTable';

const DEFAULT_PAGE_SIZE = 25;
function formatMoney(value) {
  const n = Number(value) || 0;
  return `$${n.toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
}

export default function MembersPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({
    page: 1,
    per_page: DEFAULT_PAGE_SIZE,
    total: 0,
    last_page: 1,
    stats: { total_members: 0, active_members: 0, inactive_members: 0, total_loans: 0 },
    filters: { branches: [], committees: [] },
  });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PAGE_SIZE);
  const [filters, setFilters] = useState({ search: '', branch: '', status: '' });
  const [pendingFilters, setPendingFilters] = useState({ search: '', branch: '', status: '' });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [confirmDelete, setConfirmDelete] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => { document.title = 'Members · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  const loadMembers = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page, per_page: perPage };
      if (filters.search.trim()) params.search = filters.search.trim();
      if (filters.branch) params.branch = filters.branch;
      if (filters.status) params.status = filters.status;
      const response = await membersApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      const incomingMeta = response.meta || {};
      setMeta((current) => ({
        ...current,
        ...incomingMeta,
        stats: incomingMeta.stats || current.stats,
        filters: incomingMeta.filters || current.filters,
      }));
    } catch (err) {
      setError(err.message || 'Failed to load members.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, filters]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      loadMembers();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadMembers]);

  const handleFilterSubmit = () => {
    setPage(1);
    setFilters({ ...pendingFilters });
  };

  const handleFilterReset = () => {
    const empty = { search: '', branch: '', status: '' };
    setPendingFilters(empty);
    setPage(1);
    setFilters(empty);
  };

  const handleDelete = async () => {
    if (!confirmDelete) return;
    setBusy(true);
    try {
      await membersApi.remove(confirmDelete.member_id);
      setFeedback({ kind: 'success', message: `Member "${confirmDelete.full_name}" deleted.` });
      setConfirmDelete(null);
      await loadMembers();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to delete member.' });
      setConfirmDelete(null);
    } finally {
      setBusy(false);
    }
  };

  const stats = meta.stats || {};
  const total = Number(meta.total) || items.length;
  const lastPage = Math.max(1, Number(meta.last_page) || 1);
  const branches = (meta.filters && meta.filters.branches) || [];

  const startIndex = items.length === 0 ? 0 : (meta.page - 1) * meta.per_page + 1;
  const endIndex = Math.min(meta.page * meta.per_page, total);

  return (
    <div className="members-page">
      {feedback ? (
        <div className={`members-toast ${feedback.kind}`} role="status">
          <span className="members-toast-icon" aria-hidden>{feedback.kind === 'success' ? '✓' : '!'}</span>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      <Link to="/" className="back-btn">
        <i className="fa-solid fa-arrow-left"></i> Back to Dashboard
      </Link>

      <h1 className="page-title">
        <i className="fa-solid fa-users"></i> Members Management
      </h1>

      {/* Stats Grid — mirrors members/index.php lines 85-133 */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-header">
            <div>
              <div className="stat-label">Total Members</div>
              <div className="stat-value">{Number(stats.total_members || 0).toLocaleString()}</div>
            </div>
            <div className="stat-icon blue"><i className="fa-solid fa-users"></i></div>
          </div>
          <div className="stat-trend up"><i className="fa-solid fa-arrow-up"></i> 12%</div>
          <div className="stat-sub">+18 this month</div>
        </div>

        <div className="stat-card">
          <div className="stat-header">
            <div>
              <div className="stat-label">Active Members</div>
              <div className="stat-value">{Number(stats.active_members || 0).toLocaleString()}</div>
            </div>
            <div className="stat-icon green"><i className="fa-solid fa-user-check"></i></div>
          </div>
          <div className="stat-trend up"><i className="fa-solid fa-arrow-up"></i> 8%</div>
          <div className="stat-sub">
            {Number(stats.total_members || 0) > 0
              ? `${Math.round((Number(stats.active_members || 0) / Number(stats.total_members || 0)) * 100)}% of total`
              : '0% of total'}
          </div>
        </div>

        <div className="stat-card">
          <div className="stat-header">
            <div>
              <div className="stat-label">Total Loans</div>
              <div className="stat-value">{formatMoney(stats.total_loans)}</div>
            </div>
            <div className="stat-icon purple"><i className="fa-solid fa-money-bill-wave"></i></div>
          </div>
          <div className="stat-trend up"><i className="fa-solid fa-arrow-up"></i> 12.5%</div>
          <div className="stat-sub">+12.5% this month</div>
        </div>

        <div className="stat-card">
          <div className="stat-header">
            <div>
              <div className="stat-label">Inactive Members</div>
              <div className="stat-value">{Number(stats.inactive_members || 0).toLocaleString()}</div>
            </div>
            <div className="stat-icon red"><i className="fa-solid fa-user-slash"></i></div>
          </div>
          <div className="stat-trend down"><i className="fa-solid fa-arrow-down"></i> Needs attention</div>
          <div className="stat-sub">Requires review</div>
        </div>
      </div>

      {/* Filter Section */}
      <div className="filter-section">
        <MembersFilters
          value={pendingFilters}
          branches={branches}
          onChange={setPendingFilters}
          onSubmit={handleFilterSubmit}
          onReset={handleFilterReset}
        />
      </div>

      {/* Actions */}
      <div className="members-actions">
        <span className="text-muted">Total: {total} members</span>
        <div>
          <Link to="/members/new" className="btn btn-primary">
            <i className="fa-solid fa-plus"></i> Add Member
          </Link>
        </div>
      </div>

      {/* Table */}
      {error ? (
        <div className="alert alert-danger" role="alert">{error}</div>
      ) : null}

      {loading ? (
        <div className="members-loading">
          <i className="fa-solid fa-spinner fa-spin"></i> Loading members…
        </div>
      ) : (
        <MembersTable items={items} onDelete={(row) => setConfirmDelete(row)} />
      )}

      {/* Pagination */}
      <div className="members-pagination">
        <div className="members-pagination-info">
          Showing <strong>{startIndex}</strong> – <strong>{endIndex}</strong> of <strong>{total}</strong>
        </div>
        <div className="members-pagination-controls">
          <label className="members-pagination-size">
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
          <span className="members-pagination-current">
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

      {/* Delete confirm */}
      {confirmDelete ? (
        <div className="members-backdrop" role="dialog" aria-modal="true">
          <div className="members-modal danger">
            <div className="members-modal-head">
              <div className="members-modal-icon danger" aria-hidden><i className="fa-solid fa-trash"></i></div>
              <div>
                <h3>Delete member?</h3>
                <p>"{confirmDelete.full_name}" ({confirmDelete.member_code})</p>
              </div>
            </div>
            <div className="members-modal-body">
              <p style={{ margin: 0, color: 'var(--muted)', fontSize: 13, lineHeight: 1.5 }}>
                This action cannot be undone. Members with existing loans cannot be deleted.
              </p>
              <div className="members-modal-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setConfirmDelete(null)} disabled={busy}>Cancel</button>
                <button type="button" className="btn btn-danger" onClick={handleDelete} disabled={busy}>
                  <i className="fa-solid fa-trash"></i> {busy ? 'Deleting…' : 'Delete member'}
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}