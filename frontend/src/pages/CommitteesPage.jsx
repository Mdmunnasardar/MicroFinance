// CommitteesPage — list page mirroring Committees/index.php +
// CommitteesListController. Stats grid, filter row, page-header with
// back-to-dashboard and add-committee actions, committee card grid.
// Keeps the same markup/classes as the PHP view so the CSS in
// .committees-page maps 1:1.

import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { committeesApi } from '../api/committeesApi';
import CommitteesFilters from '../components/committees/CommitteesFilters';
import CommitteeCard from '../components/committees/CommitteeCard';

const EMPTY_FILTERS = { search: '', branch_id: '', status: '', meeting_day: '' };

export default function CommitteesPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({
    stats: { total_committees: 0, active_committees: 0, inactive_committees: 0, total_members: 0 },
    filters: { branches: [], officers: [] },
  });
  const [filters, setFilters] = useState({ ...EMPTY_FILTERS });
  const [pendingFilters, setPendingFilters] = useState({ ...EMPTY_FILTERS });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [confirmDelete, setConfirmDelete] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => { document.title = 'Committees · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  // Hydrate from sessionStorage feedback (set by form pages after redirect).
  useEffect(() => {
    const sessionFeedback = window.sessionStorage?.getItem('committees_feedback');
    if (sessionFeedback) {
      try {
        const parsed = JSON.parse(sessionFeedback);
        if (parsed?.message) {
          setFeedback(parsed);
        }
      } catch (_e) {
        // ignore
      }
      window.sessionStorage.removeItem('committees_feedback');
    }
  }, []);

  const loadCommittees = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = {};
      if (filters.search.trim()) params.search = filters.search.trim();
      if (filters.branch_id) params.branch_id = filters.branch_id;
      if (filters.status !== '') params.status = filters.status;
      if (filters.meeting_day) params.meeting_day = filters.meeting_day;

      const response = await committeesApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      const incomingMeta = response.meta || {};
      setMeta((current) => ({
        stats: incomingMeta.stats || current.stats,
        filters: incomingMeta.filters || current.filters,
      }));
    } catch (err) {
      setError(err.message || 'Failed to load committees.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      loadCommittees();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadCommittees]);

  const handleFilterSubmit = () => { setFilters({ ...pendingFilters }); };
  const handleFilterReset = () => {
    setPendingFilters({ ...EMPTY_FILTERS });
    setFilters({ ...EMPTY_FILTERS });
  };

  const handleToggleStatus = async (committee) => {
    setBusy(true);
    try {
      await committeesApi.update(committee.committee_id, {
        committee_name: committee.committee_name,
        branch_id: committee.branch_id,
        field_officer_id: committee.field_officer_id,
        meeting_day: committee.meeting_day,
        meeting_time: committee.meeting_time,
        formed_date: committee.formed_date,
        is_active: Number(committee.is_active) === 1 ? 0 : 1,
      });
      setFeedback({
        kind: 'success',
        message: `Committee "${committee.committee_name}" ${Number(committee.is_active) === 1 ? 'deactivated' : 'activated'}.`,
      });
      await loadCommittees();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to update status.' });
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async () => {
    if (!confirmDelete) return;
    setBusy(true);
    try {
      await committeesApi.remove(confirmDelete.committee_id);
      setFeedback({ kind: 'success', message: `Committee "${confirmDelete.committee_name}" deleted.` });
      setConfirmDelete(null);
      await loadCommittees();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to delete committee.' });
      setConfirmDelete(null);
    } finally {
      setBusy(false);
    }
  };

  const stats = meta.stats || {};
  const branches = (meta.filters && meta.filters.branches) || [];

  return (
    <div className="committees-page">
      {feedback ? (
        <div className={`toast ${feedback.kind}`} role="status">
          <i className={`fa-solid ${feedback.kind === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`}></i>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      {/* Page header — mirrors Committees/index.php lines 92-112 */}
      <div className="page-header animate-slide-up">
        <div className="header-left">
          <div className="header-icon primary">
            <i className="fa-solid fa-users-cog"></i>
          </div>
          <div>
            <h1 className="header-title">Committees</h1>
            <p className="header-subtitle">Manage all committees and their members</p>
          </div>
        </div>
        <div className="header-actions">
          <Link to="/" className="btn btn-secondary">
            <i className="fa-solid fa-arrow-left"></i> Dashboard
          </Link>
          <Link to="/committees/new" className="btn btn-primary">
            <i className="fa-solid fa-plus-circle"></i> Add Committee
          </Link>
        </div>
      </div>

      {/* Stat cards — mirrors Committees/index.php lines 114-160 */}
      <div className="stat-grid">
        <div className="stat-card primary animate-slide-up" style={{ animationDelay: '0.05s' }}>
          <div className="stat-top">
            <div>
              <p className="stat-label">Total Committees</p>
              <p className="stat-number">{Number(stats.total_committees || 0).toLocaleString()}</p>
            </div>
            <div className="stat-icon primary-icon">
              <i className="fa-solid fa-building"></i>
            </div>
          </div>
        </div>
        <div className="stat-card success animate-slide-up" style={{ animationDelay: '0.1s' }}>
          <div className="stat-top">
            <div>
              <p className="stat-label">Active</p>
              <p className="stat-number success-text">{Number(stats.active_committees || 0).toLocaleString()}</p>
            </div>
            <div className="stat-icon success-icon">
              <i className="fa-solid fa-check-circle"></i>
            </div>
          </div>
        </div>
        <div className="stat-card danger animate-slide-up" style={{ animationDelay: '0.15s' }}>
          <div className="stat-top">
            <div>
              <p className="stat-label">Inactive</p>
              <p className="stat-number danger-text">{Number(stats.inactive_committees || 0).toLocaleString()}</p>
            </div>
            <div className="stat-icon danger-icon">
              <i className="fa-solid fa-times-circle"></i>
            </div>
          </div>
        </div>
        <div className="stat-card purple animate-slide-up" style={{ animationDelay: '0.2s' }}>
          <div className="stat-top">
            <div>
              <p className="stat-label">Total Members</p>
              <p className="stat-number purple-text">{Number(stats.total_members || 0).toLocaleString()}</p>
            </div>
            <div className="stat-icon purple-icon">
              <i className="fa-solid fa-users"></i>
            </div>
          </div>
        </div>
      </div>

      {/* Filter section */}
      <div className="filter-section animate-slide-up" style={{ animationDelay: '0.25s' }}>
        <CommitteesFilters
          value={pendingFilters}
          branches={branches}
          onChange={setPendingFilters}
          onSubmit={handleFilterSubmit}
          onReset={handleFilterReset}
        />
      </div>

      {/* Error */}
      {error ? (
        <div className="empty-state" role="alert">
          <div className="empty-icon"><i className="fa-solid fa-triangle-exclamation"></i></div>
          <h3 className="empty-title">Error</h3>
          <p className="empty-description">{error}</p>
          <button type="button" className="btn btn-primary" onClick={loadCommittees}>
            <i className="fa-solid fa-rotate"></i> Retry
          </button>
        </div>
      ) : null}

      {/* Loading */}
      {loading && !error ? (
        <div className="loading-state">
          <i className="fa-solid fa-spinner fa-spin"></i> Loading committees…
        </div>
      ) : null}

      {/* Cards or empty */}
      {!loading && !error && items.length > 0 ? (
        <div className="committee-grid animate-slide-up" style={{ animationDelay: '0.3s' }}>
          {items.map((committee) => (
            <CommitteeCard
              key={committee.committee_id}
              committee={committee}
              onToggleStatus={handleToggleStatus}
              onDelete={setConfirmDelete}
            />
          ))}
        </div>
      ) : null}

      {!loading && !error && items.length === 0 ? (
        <div className="empty-state animate-slide-up" style={{ animationDelay: '0.3s' }}>
          <div className="empty-icon"><i className="fa-solid fa-inbox"></i></div>
          <h3 className="empty-title">No Committees Found</h3>
          <p className="empty-description">Get started by creating your first committee.</p>
          <Link to="/committees/new" className="btn btn-primary">
            <i className="fa-solid fa-plus-circle"></i> Add Committee
          </Link>
        </div>
      ) : null}

      {/* Delete confirmation modal */}
      {confirmDelete ? (
        <div className="modal-backdrop" role="dialog" aria-modal="true">
          <div className="modal-card">
            <div className="modal-head">
              <div className="modal-icon danger" aria-hidden><i className="fa-solid fa-trash"></i></div>
              <div>
                <h3>Delete committee?</h3>
                <p className="modal-sub">"{confirmDelete.committee_name}" (#{confirmDelete.committee_id})</p>
              </div>
            </div>
            <div className="modal-body">
              <p style={{ margin: 0, color: '#64748b', fontSize: 13, lineHeight: 1.5 }}>
                This action cannot be undone. Committees with active members cannot be deleted.
              </p>
              <div className="modal-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setConfirmDelete(null)} disabled={busy}>
                  Cancel
                </button>
                <button type="button" className="btn btn-danger" onClick={handleDelete} disabled={busy}>
                  <i className="fa-solid fa-trash"></i> {busy ? 'Deleting…' : 'Delete committee'}
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}