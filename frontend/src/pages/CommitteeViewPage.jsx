// CommitteeViewPage — committee detail view mirroring Committees/view.php.
// Two-column layout: main info + sidebar (officer + quick actions),
// then a members table below. Mirrors PHP view.php lines 64-295.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { committeesApi } from '../api/committeesApi';

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function initialsFromName(name) {
  if (!name) return '?';
  const trimmed = String(name).trim();
  if (trimmed.includes(' ')) {
    const parts = trimmed.split(/\s+/);
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return trimmed.slice(0, 2).toUpperCase();
}

function formatDate(value, opts = {}) {
  if (!value) return 'N/A';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    ...opts,
  });
}

function formatTime(value) {
  if (!value) return '';
  const [hStr, mStr] = String(value).split(':');
  const h = Number(hStr);
  const m = Number(mStr);
  if (Number.isNaN(h) || Number.isNaN(m)) return value;
  const am = h < 12 || h === 24;
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  const minute = m.toString().padStart(2, '0');
  const period = am ? 'AM' : 'PM';
  return `${hour12}:${minute} ${period}`;
}

function formatMoney(value) {
  const n = Number(value) || 0;
  return `$${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function CommitteeViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [busy, setBusy] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => { document.title = 'Committee Details · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  // Hydrate from sessionStorage feedback (set by form page after redirect).
  useEffect(() => {
    const sessionFeedback = window.sessionStorage?.getItem('committees_feedback');
    if (sessionFeedback) {
      try {
        const parsed = JSON.parse(sessionFeedback);
        if (parsed?.message) setFeedback(parsed);
      } catch (_e) {
        // ignore
      }
      window.sessionStorage.removeItem('committees_feedback');
    }
  }, []);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await committeesApi.get(id);
      const payload = response?.data ?? response;
      setData(payload);
    } catch (err) {
      setError(err.message || 'Failed to load committee.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      loadData();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadData]);

  const handleToggleStatus = async () => {
    if (!data?.committee) return;
    const c = data.committee;
    setBusy(true);
    try {
      await committeesApi.update(c.committee_id, {
        committee_name: c.committee_name,
        branch_id: c.branch_id,
        field_officer_id: c.field_officer_id,
        meeting_day: c.meeting_day,
        meeting_time: c.meeting_time,
        formed_date: c.formed_date,
        is_active: Number(c.is_active) === 1 ? 0 : 1,
      });
      setFeedback({
        kind: 'success',
        message: `Committee "${c.committee_name}" ${Number(c.is_active) === 1 ? 'deactivated' : 'activated'}.`,
      });
      await loadData();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to update status.' });
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async () => {
    if (!data?.committee) return;
    setBusy(true);
    try {
      await committeesApi.remove(data.committee.committee_id);
      window.sessionStorage.setItem(
        'committees_feedback',
        JSON.stringify({
          kind: 'success',
          message: `Committee "${data.committee.committee_name}" deleted.`,
        }),
      );
      navigate('/committees');
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to delete committee.' });
      setConfirmDelete(false);
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return (
      <div className="committees-page">
        <div className="page-header">
          <div className="header-left">
            <div className="header-icon primary">
              <i className="fa-solid fa-users-cog"></i>
            </div>
            <div>
              <h1 className="header-title">Committee Details</h1>
              <p className="header-subtitle">Loading…</p>
            </div>
          </div>
        </div>
        <div className="loading-state">
          <i className="fa-solid fa-spinner fa-spin"></i> Loading…
        </div>
      </div>
    );
  }

  if (error || !data?.committee) {
    return (
      <div className="committees-page">
        <div className="page-header">
          <div className="header-left">
            <div className="header-icon primary">
              <i className="fa-solid fa-users-cog"></i>
            </div>
            <div>
              <h1 className="header-title">Committee Details</h1>
              <p className="header-subtitle">Committee not found</p>
            </div>
          </div>
          <div className="header-actions">
            <Link to="/committees" className="btn btn-secondary">
              <i className="fa-solid fa-arrow-left"></i> Back to Committees
            </Link>
          </div>
        </div>
        <div className="empty-state">
          <div className="empty-icon"><i className="fa-solid fa-triangle-exclamation"></i></div>
          <h3 className="empty-title">Unable to load committee</h3>
          <p className="empty-description">{error || 'The committee could not be found.'}</p>
          <Link to="/committees" className="btn btn-primary">
            <i className="fa-solid fa-arrow-left"></i> Back to list
          </Link>
        </div>
      </div>
    );
  }

  const committee = data.committee;
  const members = data.members || [];
  const stats = data.stats || { total_savings: 0, total_loans: 0 };
  const isActive = Number(committee.is_active) === 1;
  const memberCount = Number(committee.member_count || members.length || 0);

  return (
    <div className="committees-page">
      {feedback ? (
        <div className={`toast ${feedback.kind}`} role="status">
          <i className={`fa-solid ${feedback.kind === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`}></i>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      {/* Page header — mirrors view.php lines 67-84 */}
      <div className="page-header animate-slide-up">
        <div className="header-left">
          <div className="header-icon primary">
            <i className="fa-solid fa-users-cog"></i>
          </div>
          <div>
            <h1 className="header-title">Committee Details</h1>
            <p className="header-subtitle">Complete committee information</p>
          </div>
        </div>
        <div className="header-actions">
          <Link to={`/committees/${committee.committee_id}/edit`} className="btn btn-primary">
            <i className="fa-solid fa-edit"></i> Edit
          </Link>
          <Link to="/committees" className="btn btn-secondary">
            <i className="fa-solid fa-arrow-left"></i> Back
          </Link>
        </div>
      </div>

      {/* Main grid — mirrors view.php lines 88-188 */}
      <div className="grid grid-cols-3 gap-6 animate-slide-up" style={{ animationDelay: '0.05s' }}>
        <div style={{ gridColumn: 'span 2' }}>
          <div className="detail-section">
            <div className="flex items-start justify-between mb-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-900">{escapeHtml(committee.committee_name)}</h2>
                <p className="text-sm text-gray-500 mt-2">
                  <i className="fa-solid fa-tag mr-1"></i> #{committee.committee_id}
                </p>
              </div>
              <span className={`badge ${isActive ? 'badge-success' : 'badge-danger'} text-sm`} style={{ padding: '6px 16px' }}>
                {isActive ? 'Active' : 'Inactive'}
              </span>
            </div>

            <div className="detail-grid">
              <div>
                <p className="detail-label"><i className="fa-solid fa-building text-primary mr-1"></i> Branch</p>
                <p className="detail-value">{escapeHtml(committee.branch_name || 'N/A')}</p>
              </div>
              <div>
                <p className="detail-label"><i className="fa-solid fa-calendar-alt text-warning mr-1"></i> Formed Date</p>
                <p className="detail-value">{formatDate(committee.formed_date, { day: '2-digit', month: 'long', year: 'numeric' })}</p>
              </div>
              <div>
                <p className="detail-label"><i className="fa-solid fa-calendar-day text-info mr-1"></i> Meeting Schedule</p>
                <p className="detail-value">
                  <span className="badge badge-info">{escapeHtml(committee.meeting_day || '')}</span>
                  <span className="badge badge-gray" style={{ marginLeft: 8 }}>{formatTime(committee.meeting_time)}</span>
                </p>
              </div>
              <div>
                <p className="detail-label"><i className="fa-solid fa-users text-purple mr-1"></i> Total Members</p>
                <p className="detail-value text-2xl font-bold text-purple">{memberCount}</p>
              </div>
              <div>
                <p className="detail-label"><i className="fa-solid fa-piggy-bank text-success mr-1"></i> Total Savings</p>
                <p className="detail-value text-xl font-bold text-success">{formatMoney(stats.total_savings)}</p>
              </div>
              <div>
                <p className="detail-label"><i className="fa-solid fa-hand-holding-usd text-warning mr-1"></i> Total Loans</p>
                <p className="detail-value text-xl font-bold text-warning">{stats.total_loans || 0}</p>
              </div>
            </div>

            <p className="text-xs text-gray-400 mt-6 pt-4 border-t border-gray-100">
              <i className="fa-solid fa-clock mr-1"></i>
              Created: {formatDate(committee.created_at, { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}
            </p>
          </div>
        </div>

        {/* Sidebar */}
        <div style={{ gridColumn: 'span 1' }}>
          <div className="officer-card mb-4">
            <h4 className="font-semibold text-gray-800 mb-3 flex items-center">
              <i className="fa-solid fa-user-tie text-primary mr-2"></i> Field Officer
            </h4>
            <div className="flex items-center gap-4">
              <div className="officer-avatar">
                {initialsFromName(committee.officer_name)}
              </div>
              <div>
                <p className="officer-name">{escapeHtml(committee.officer_name || 'Not Assigned')}</p>
              </div>
            </div>
          </div>

          <div className="detail-section">
            <h4 className="font-semibold text-gray-800 text-sm mb-3">Quick Actions</h4>
            <div className="space-y-2">
              <Link
                to={`/committees/${committee.committee_id}/members`}
                className="btn btn-outline-primary btn-block"
              >
                <i className="fa-solid fa-user-plus"></i> Manage Members
              </Link>
              <button
                type="button"
                className={`btn ${isActive ? 'btn-warning' : 'btn-success'} btn-block`}
                onClick={handleToggleStatus}
                disabled={busy}
              >
                <i className={`fa-solid ${isActive ? 'fa-pause' : 'fa-play'}`}></i>
                {isActive ? 'Deactivate' : 'Activate'}
              </button>
              <button
                type="button"
                className="btn btn-danger btn-block"
                onClick={() => setConfirmDelete(true)}
                disabled={busy}
              >
                <i className="fa-solid fa-trash"></i> Delete Committee
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Members list — mirrors view.php lines 190-295 */}
      <div className="detail-section mt-6 animate-slide-up" style={{ animationDelay: '0.1s' }}>
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-bold text-gray-800 flex items-center">
            <i className="fa-solid fa-users text-primary mr-2"></i>
            Committee Members
            <span className="feature-pill">{members.length}</span>
          </h3>
          <Link to={`/committees/${committee.committee_id}/members`} className="btn btn-primary btn-sm">
            <i className="fa-solid fa-plus"></i> Add Member
          </Link>
        </div>

        {members.length > 0 ? (
          <div className="table-wrapper">
            <table className="table-premium">
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Code</th>
                  <th>Contact</th>
                  <th>Gender</th>
                  <th>Join Date</th>
                  <th style={{ textAlign: 'right' }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {members.map((member) => (
                  <tr key={member.member_id}>
                    <td>
                      <div className="flex items-center gap-3">
                        <div className="avatar">{initialsFromName(member.full_name)}</div>
                        <div>
                          <p className="font-medium text-gray-800">{escapeHtml(member.full_name)}</p>
                          {member.guarantor_name ? (
                            <p className="text-xs text-gray-500">
                              <i className="fa-solid fa-user-check mr-1"></i>
                              Guarantor: {escapeHtml(member.guarantor_name)}
                            </p>
                          ) : null}
                        </div>
                      </div>
                    </td>
                    <td><span className="badge badge-gray badge-sm">{escapeHtml(member.member_code || '')}</span></td>
                    <td>
                      {member.phone ? (
                        <p className="text-sm text-gray-600">
                          <i className="fa-solid fa-phone text-success mr-1"></i> {escapeHtml(member.phone)}
                        </p>
                      ) : null}
                      {member.national_id ? (
                        <p className="text-xs text-gray-400">
                          <i className="fa-solid fa-id-card mr-1"></i> {escapeHtml(member.national_id)}
                        </p>
                      ) : null}
                    </td>
                    <td>
                      {member.gender === 'M' ? (
                        <span className="badge badge-info badge-sm">Male</span>
                      ) : member.gender === 'F' ? (
                        <span className="badge badge-purple badge-sm">Female</span>
                      ) : (
                        <span className="badge badge-gray badge-sm">Other</span>
                      )}
                    </td>
                    <td className="text-sm text-gray-600">{formatDate(member.join_date)}</td>
                    <td style={{ textAlign: 'right' }}>
                      <div className="flex items-center justify-end gap-1">
                        <Link
                          to={`/members/${member.member_id}`}
                          className="action-btn primary"
                          title="View"
                        >
                          <i className="fa-solid fa-eye"></i>
                        </Link>
                        <Link
                          to={`/committees/${committee.committee_id}/members`}
                          className="action-btn danger"
                          title="Remove"
                        >
                          <i className="fa-solid fa-user-minus"></i>
                        </Link>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="empty-state" style={{ padding: '40px 20px' }}>
            <div className="empty-icon" style={{ fontSize: 48 }}>
              <i className="fa-solid fa-users"></i>
            </div>
            <h3 className="empty-title" style={{ fontSize: 18 }}>No Members Assigned</h3>
            <p className="empty-description" style={{ fontSize: 13 }}>This committee doesn't have any members yet.</p>
            <Link to={`/committees/${committee.committee_id}/members`} className="btn btn-primary btn-sm">
              <i className="fa-solid fa-plus"></i> Assign First Member
            </Link>
          </div>
        )}
      </div>

      {/* Delete confirmation modal */}
      {confirmDelete ? (
        <div className="modal-backdrop" role="dialog" aria-modal="true">
          <div className="modal-card">
            <div className="modal-head">
              <div className="modal-icon danger" aria-hidden><i className="fa-solid fa-trash"></i></div>
              <div>
                <h3>Delete committee?</h3>
                <p className="modal-sub">"{committee.committee_name}" (#{committee.committee_id})</p>
              </div>
            </div>
            <div className="modal-body">
              <p style={{ margin: 0, color: '#64748b', fontSize: 13, lineHeight: 1.5 }}>
                This action cannot be undone. Committees with active members cannot be deleted.
              </p>
              <div className="modal-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setConfirmDelete(false)} disabled={busy}>
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