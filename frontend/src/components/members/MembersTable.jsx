// MembersTable — mirrors the members table rendered by members/index.php
// lines 182-252 (5 columns: Member, Phone, Branch, Status, Actions).
// Uses Link for view/edit so navigation stays inside the React SPA, and a
// callback for delete (parent owns the confirm modal + API call).

import { Link } from 'react-router-dom';

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  const div = document.createElement('div');
  div.textContent = String(text);
  return div.innerHTML;
}

function memberInitials(name) {
  if (!name) return '?';
  const upper = String(name).toUpperCase();
  return upper.slice(0, 2);
}

export default function MembersTable({ items, onDelete }) {
  if (!items?.length) {
    return (
      <div className="members-table-empty">
        <i className="fa-solid fa-users" aria-hidden></i>
        <p>No members found</p>
      </div>
    );
  }

  return (
    <div className="table-container">
      <table>
        <thead>
          <tr>
            <th>Member</th>
            <th>Phone</th>
            <th>Branch</th>
            <th>Status</th>
            <th style={{ textAlign: 'center' }}>Actions</th>
          </tr>
        </thead>
        <tbody>
          {items.map((row) => {
            const isActive = Number(row.is_active) === 1 || row.is_active === '1' || row.is_active === true;
            return (
              <tr key={row.member_id ?? `member-${row.member_code}`}>
                <td>
                  <div className="d-flex align-items-center gap-3">
                    <div className="avatar-circle">{memberInitials(row.full_name)}</div>
                    <div>
                      <div className="fw-semibold">{escapeHtml(row.full_name)}</div>
                      <small className="text-muted">{escapeHtml(row.member_code)}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div className="small">
                    <div><i className="fa-solid fa-phone text-muted me-1"></i> {escapeHtml(row.phone)}</div>
                  </div>
                </td>
                <td>
                  <span className="badge bg-info">{escapeHtml(row.branch_name || 'N/A')}</span>
                </td>
                <td>
                  <span className={`badge ${isActive ? 'bg-success' : 'bg-danger'}`}>
                    <i className={`fa-solid ${isActive ? 'fa-circle-check' : 'fa-circle-xmark'}`}></i>
                    {' '}
                    {isActive ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td style={{ textAlign: 'center' }}>
                  <div className="d-flex justify-content-center gap-1">
                    <Link
                      to={`/members/${row.member_id}`}
                      className="btn btn-sm btn-outline-primary"
                      title="View"
                    >
                      <i className="fa-solid fa-eye"></i>
                    </Link>
                    <Link
                      to={`/members/${row.member_id}/edit`}
                      className="btn btn-sm btn-outline-warning"
                      title="Edit"
                    >
                      <i className="fa-solid fa-pen"></i>
                    </Link>
                    <button
                      type="button"
                      className="btn btn-sm btn-outline-danger"
                      title="Delete"
                      onClick={() => onDelete(row)}
                    >
                      <i className="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}