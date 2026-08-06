// CommitteeAssignMember — two-column layout mirroring Committees/assign-member.php
// lines 148-276. Renders Current Members (left) and Available Members (right)
// with select-all + bulk assign. Receives current, available, busy, onAssignBulk
// (member_ids array), and onRemoveSingle (member_id) callbacks.

import { useMemo, useState } from 'react';
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

function initialsFromName(name) {
  if (!name) return '?';
  const trimmed = String(name).trim();
  if (trimmed.includes(' ')) {
    const parts = trimmed.split(/\s+/);
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return trimmed.slice(0, 2).toUpperCase();
}

export default function CommitteeAssignMember({
  current = [],
  available = [],
  busy,
  onAssignBulk,
  onRemoveSingle,
}) {
  const [selected, setSelected] = useState(new Set());

  const toggleOne = (id) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const allIds = useMemo(() => available.map((m) => m.member_id), [available]);
  const allSelected = available.length > 0 && selected.size === available.length;
  const toggleAll = () => {
    if (allSelected) {
      setSelected(new Set());
    } else {
      setSelected(new Set(allIds));
    }
  };

  const handleAssignSelected = (event) => {
    event.preventDefault();
    if (selected.size === 0) return;
    onAssignBulk(Array.from(selected));
  };

  const handleRemove = (member) => {
    if (!window.confirm(`Remove ${member.full_name} from this committee?`)) return;
    onRemoveSingle(member);
  };

  return (
    <div className="grid grid-cols-2 gap-6">
      {/* Current Members */}
      <div className="detail-section">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-bold text-gray-800 flex items-center">
            <i className="fa-solid fa-users text-primary mr-2"></i>
            Current Members
            <span className="feature-pill">{current.length}</span>
          </h3>
        </div>

        <div className="custom-scroll" style={{ maxHeight: 500, overflowY: 'auto' }}>
          {current.length > 0 ? (
            current.map((member) => (
              <div className="current-member" key={member.member_id}>
                <div className="flex items-center gap-3 min-w-0">
                  <div className="avatar">{initialsFromName(member.full_name)}</div>
                  <div className="min-w-0 flex-1">
                    <p className="font-medium text-gray-800 truncate">{escapeHtml(member.full_name)}</p>
                    <p className="text-xs text-gray-500">
                      <i className="fa-solid fa-id-card mr-1"></i> {escapeHtml(member.member_code || '')}
                      {member.phone ? (
                        <> &nbsp;•&nbsp; <i className="fa-solid fa-phone mr-1"></i>{escapeHtml(member.phone)}</>
                      ) : null}
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  className="action-btn danger"
                  onClick={() => handleRemove(member)}
                  disabled={busy}
                  title="Remove"
                >
                  <i className="fa-solid fa-user-minus"></i>
                </button>
              </div>
            ))
          ) : (
            <div className="text-center py-12">
              <i className="fa-solid fa-users text-4xl text-gray-300 mb-3" style={{ fontSize: 36, color: '#cbd5e1' }}></i>
              <p className="text-gray-500">No members assigned</p>
            </div>
          )}
        </div>
      </div>

      {/* Available Members */}
      <div className="detail-section">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-bold text-gray-800 flex items-center">
            <i className="fa-solid fa-user-plus text-success mr-2"></i>
            Available Members
            <span className="feature-pill">{available.length}</span>
          </h3>
        </div>

        {available.length > 0 ? (
          <form onSubmit={handleAssignSelected}>
            <div className="select-all-wrapper">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={allSelected}
                  onChange={toggleAll}
                  style={{ width: 16, height: 16, borderRadius: 4 }}
                />
                <span className="text-sm font-medium text-gray-700">Select All</span>
              </label>
              <span className="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full" style={{ background: 'var(--c-gray-100)', padding: '2px 12px', borderRadius: 20 }}>
                {selected.size} selected
              </span>
            </div>

            <div className="custom-scroll" style={{ maxHeight: 350, overflowY: 'auto' }}>
              {available.map((member) => {
                const isSelected = selected.has(member.member_id);
                return (
                  <label
                    className={`member-item${isSelected ? ' selected' : ''}`}
                    key={member.member_id}
                  >
                    <input
                      type="checkbox"
                      checked={isSelected}
                      onChange={() => toggleOne(member.member_id)}
                      style={{ width: 16, height: 16, borderRadius: 4 }}
                    />
                    <div className="flex items-center gap-3 flex-1 cursor-pointer min-w-0">
                      <div className="avatar">{initialsFromName(member.full_name)}</div>
                      <div className="min-w-0 flex-1">
                        <p className="name">{escapeHtml(member.full_name)}</p>
                        <p className="details">
                          <i className="fa-solid fa-id-card mr-1"></i> {escapeHtml(member.member_code || '')}
                          {member.phone ? (
                            <> &nbsp;•&nbsp; <i className="fa-solid fa-phone mr-1"></i>{escapeHtml(member.phone)}</>
                          ) : null}
                        </p>
                      </div>
                    </div>
                  </label>
                );
              })}
            </div>

            <div className="mt-4">
              <button
                type="submit"
                className="btn btn-primary btn-block"
                disabled={busy || selected.size === 0}
                style={selected.size === 0 ? { opacity: 0.5, cursor: 'not-allowed' } : undefined}
              >
                <i className="fa-solid fa-user-plus"></i>
                {busy ? 'Assigning…' : 'Assign Selected Members'}
              </button>
            </div>
          </form>
        ) : (
          <div className="text-center py-12">
            <i className="fa-solid fa-check-circle text-4xl mb-3" style={{ fontSize: 36, color: '#10b981' }}></i>
            <p className="text-gray-500">All members are already assigned</p>
            <Link to="/members" className="text-primary text-sm mt-2 inline-block" style={{ color: 'var(--c-primary)' }}>
              <i className="fa-solid fa-arrow-right mr-1"></i> View All Members
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}