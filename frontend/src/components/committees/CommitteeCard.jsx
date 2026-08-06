// CommitteeCard — single card in the committee grid. Mirrors the markup in
// Committees/index.php lines 217-280. Reports actions back to the parent via
// onView / onEdit / onManageMembers / onToggleStatus / onDelete callbacks.

import { Link } from 'react-router-dom';

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatDate(value) {
  if (!value) return 'N/A';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(value) {
  if (!value) return '';
  // PHP uses h:i A; we use the same with toLocaleString to get 12-hour format.
  const [hStr, mStr] = value.split(':');
  const h = Number(hStr);
  const m = Number(mStr);
  if (Number.isNaN(h) || Number.isNaN(m)) return value;
  const am = h < 12 || h === 24;
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  const minute = m.toString().padStart(2, '0');
  const period = am ? 'AM' : 'PM';
  return `${hour12}:${minute} ${period}`;
}

export default function CommitteeCard({ committee, onView, onEdit, onManageMembers, onToggleStatus, onDelete }) {
  const isActive = Number(committee.is_active) === 1;
  const toggleIcon = isActive ? 'fa-pause' : 'fa-play';
  const toggleTitle = isActive ? 'Deactivate' : 'Activate';
  const toggleClass = isActive ? 'warning' : 'success';

  return (
    <div className="committee-card">
      <div className="card-top">
        <div>
          <h3 className="committee-name">{escapeHtml(committee.committee_name)}</h3>
          <span className="committee-code">#{committee.committee_id}</span>
        </div>
        <span className={`badge ${isActive ? 'badge-success' : 'badge-danger'}`}>
          {isActive ? 'Active' : 'Inactive'}
        </span>
      </div>

      <div className="card-body">
        <div className="info-grid">
          <div className="info-item">
            <span className="label"><i className="fa-solid fa-building"></i> Branch</span>
            <span className="value">{escapeHtml(committee.branch_name || 'N/A')}</span>
          </div>
          <div className="info-item">
            <span className="label"><i className="fa-solid fa-user-tie"></i> Officer</span>
            <span className="value">{escapeHtml(committee.officer_name || 'N/A')}</span>
          </div>
          <div className="info-item">
            <span className="label"><i className="fa-solid fa-calendar-alt"></i> Formed</span>
            <span className="value">{formatDate(committee.formed_date)}</span>
          </div>
          <div className="info-item">
            <span className="label"><i className="fa-solid fa-users"></i> Members</span>
            <span className="value">
              <span className="badge-members">{committee.member_count || 0}</span>
            </span>
          </div>
        </div>
        <div style={{ marginTop: 12, paddingTop: 12, borderTop: '1px solid var(--c-gray-100)' }}>
          <span className="badge-day" style={{ display: 'inline-block', padding: '2px 10px', borderRadius: 12, fontSize: 11, fontWeight: 600, background: 'linear-gradient(135deg, var(--c-primary-bg), #dbeafe)', color: 'var(--c-primary)', marginRight: 4 }}>
            <i className="fa-solid fa-calendar-day"></i> {escapeHtml(committee.meeting_day || '')}
          </span>
          <span className="badge-time" style={{ display: 'inline-block', padding: '2px 10px', borderRadius: 12, fontSize: 11, fontWeight: 600, background: 'var(--c-gray-100)', color: 'var(--c-gray-600)' }}>
            <i className="fa-solid fa-clock"></i> {formatTime(committee.meeting_time)}
          </span>
        </div>
      </div>

      <div className="card-footer">
        <div className="action-group">
          <Link
            to={`/committees/${committee.committee_id}`}
            className="action-btn primary"
            title="View"
            onClick={(e) => {
              if (onView) {
                e.preventDefault();
                onView(committee);
              }
            }}
          >
            <i className="fa-solid fa-eye"></i>
          </Link>
          <Link
            to={`/committees/${committee.committee_id}/edit`}
            className="action-btn primary"
            title="Edit"
            onClick={(e) => {
              if (onEdit) {
                e.preventDefault();
                onEdit(committee);
              }
            }}
          >
            <i className="fa-solid fa-edit"></i>
          </Link>
          <Link
            to={`/committees/${committee.committee_id}/members`}
            className="action-btn success"
            title="Manage Members"
            onClick={(e) => {
              if (onManageMembers) {
                e.preventDefault();
                onManageMembers(committee);
              }
            }}
          >
            <i className="fa-solid fa-users"></i>
          </Link>
        </div>
        <div className="action-group">
          <button
            type="button"
            className={`action-btn ${toggleClass}`}
            title={toggleTitle}
            onClick={() => onToggleStatus && onToggleStatus(committee)}
          >
            <i className={`fa-solid ${toggleIcon}`}></i>
          </button>
          <button
            type="button"
            className="action-btn danger"
            title="Delete"
            onClick={() => onDelete && onDelete(committee)}
          >
            <i className="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  );
}