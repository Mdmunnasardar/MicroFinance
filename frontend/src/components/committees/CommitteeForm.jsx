// CommitteeForm — controlled form mirroring Committees/add.php and
// Committees/edit.php. Receives `initialValue`, `branches`, `officers`, `mode`,
// `busy`, `error`, `onSubmit`. Renders the gradient form-header (primary for
// add, warning for edit) and a toggle switch on edit.

import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const EMPTY = {
  committee_name: '',
  branch_id: '',
  field_officer_id: '',
  meeting_day: '',
  meeting_time: '',
  formed_date: '',
  is_active: true,
};

function normalise(initialValue) {
  if (!initialValue) {
    // PHP add.php defaults formed_date to today.
    return { ...EMPTY, formed_date: new Date().toISOString().slice(0, 10) };
  }
  return {
    committee_name: initialValue.committee_name || '',
    branch_id: initialValue.branch_id ?? '',
    field_officer_id: initialValue.field_officer_id ?? '',
    meeting_day: initialValue.meeting_day || '',
    meeting_time: normaliseTime(initialValue.meeting_time),
    formed_date: initialValue.formed_date || '',
    is_active: Number(initialValue.is_active) === 1 || initialValue.is_active === true || initialValue.is_active === '1',
  };
}

// mysqli TIME fields come back as HH:MM:SS — HTML time input wants HH:MM.
function normaliseTime(value) {
  if (!value) return '';
  const parts = String(value).split(':');
  return parts.length >= 2 ? `${parts[0]}:${parts[1]}` : String(value);
}

export default function CommitteeForm({
  initialValue,
  branches = [],
  officers = [],
  busy,
  error,
  mode,
  onSubmit,
}) {
  const isEdit = mode === 'edit';
  const [form, setForm] = useState(() => normalise(initialValue));

  useEffect(() => {
    setForm(normalise(initialValue));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialValue?.committee_id]);

  const update = (patch) => setForm((current) => ({ ...current, ...patch }));

  const handleSubmit = (event) => {
    event.preventDefault();
    const payload = {
      ...form,
      branch_id: form.branch_id === '' ? null : Number(form.branch_id),
      field_officer_id: form.field_officer_id === '' ? null : Number(form.field_officer_id),
      is_active: form.is_active ? 1 : 0,
    };
    onSubmit(payload);
  };

  return (
    <div className="form-container animate-slide-up">
      <div className="form-card">
        <div className={`form-header ${isEdit ? 'warning' : 'primary'}`}>
          <div className="header-content">
            <div className="header-icon">
              <i className={isEdit ? 'fa-solid fa-edit' : 'fa-solid fa-plus-circle'}></i>
            </div>
            <div>
              <h2>{isEdit ? 'Edit Committee' : 'Create New Committee'}</h2>
              <p>{isEdit ? 'Update committee information' : 'Fill in the details to add a new committee'}</p>
            </div>
          </div>
        </div>

        <div className="form-body">
          <form onSubmit={handleSubmit} noValidate>
            <div className="form-group">
              <label className="form-label">
                <i className={`fa-solid fa-tag label-icon ${isEdit ? 'warning-icon' : 'primary-icon'}`}></i>
                Committee Name <span className="required">*</span>
              </label>
              <div className="input-with-icon">
                <i className="fa-solid fa-building input-icon"></i>
                <input
                  type="text"
                  className={`form-control ${isEdit ? 'warning-focus' : ''}`}
                  placeholder="e.g., Village Development Committee"
                  value={form.committee_name}
                  onChange={(event) => update({ committee_name: event.target.value })}
                  required
                  autoFocus
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label className="form-label">
                  <i className={`fa-solid fa-store label-icon ${isEdit ? 'warning-icon' : 'primary-icon'}`}></i>
                  Branch <span className="required">*</span>
                </label>
                <select
                  className="form-control"
                  value={form.branch_id === '' ? '' : String(form.branch_id)}
                  onChange={(event) => update({ branch_id: event.target.value })}
                  required
                >
                  <option value="">Select Branch</option>
                  {branches.map((branch) => (
                    <option key={branch.branch_id} value={String(branch.branch_id)}>
                      {branch.branch_name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label className="form-label">
                  <i className={`fa-solid fa-user-tie label-icon ${isEdit ? 'warning-icon' : 'primary-icon'}`}></i>
                  Field Officer <span className="required">*</span>
                </label>
                <select
                  className="form-control"
                  value={form.field_officer_id === '' ? '' : String(form.field_officer_id)}
                  onChange={(event) => update({ field_officer_id: event.target.value })}
                  required
                >
                  <option value="">Select Field Officer</option>
                  {officers.map((officer) => (
                    <option key={officer.user_id} value={String(officer.user_id)}>
                      {officer.full_name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label className="form-label">
                  <i className={`fa-solid fa-calendar-day label-icon ${isEdit ? 'warning-icon' : 'primary-icon'}`}></i>
                  Meeting Day <span className="required">*</span>
                </label>
                <select
                  className="form-control"
                  value={form.meeting_day}
                  onChange={(event) => update({ meeting_day: event.target.value })}
                  required
                >
                  <option value="">Select Day</option>
                  {DAYS.map((day) => (
                    <option key={day} value={day}>{day}</option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label className="form-label">
                  <i className={`fa-solid fa-clock label-icon ${isEdit ? 'warning-icon' : 'primary-icon'}`}></i>
                  Meeting Time <span className="required">*</span>
                </label>
                <div className="input-with-icon">
                  <i className="fa-solid fa-clock input-icon"></i>
                  <input
                    type="time"
                    className="form-control"
                    value={form.meeting_time}
                    onChange={(event) => update({ meeting_time: event.target.value })}
                    required
                  />
                </div>
              </div>
            </div>

            <div className="form-group">
              <label className="form-label">
                <i className={`fa-solid fa-calendar-alt label-icon ${isEdit ? 'warning-icon' : 'primary-icon'}`}></i>
                Formation Date <span className="required">*</span>
              </label>
              <div className="input-with-icon">
                <i className="fa-solid fa-calendar input-icon"></i>
                <input
                  type="date"
                  className="form-control"
                  value={form.formed_date}
                  onChange={(event) => update({ formed_date: event.target.value })}
                  required
                />
              </div>
            </div>

            {isEdit ? (
              <div className="form-group">
                <div className="toggle-switch">
                  <input
                    type="checkbox"
                    id="isActive"
                    checked={!!form.is_active}
                    onChange={(event) => update({ is_active: event.target.checked })}
                  />
                  <label className="toggle-label" htmlFor="isActive">Committee Status</label>
                  <span className={`toggle-status ${form.is_active ? 'active' : 'inactive'}`}>
                    {form.is_active ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
            ) : null}

            {error ? (
              <div className="alert" role="alert" style={{
                padding: '12px 16px',
                marginBottom: 16,
                borderRadius: 8,
                background: 'var(--c-danger-bg)',
                color: 'var(--c-danger-dark)',
                fontSize: 13,
                fontWeight: 500,
                border: '1px solid var(--c-danger)',
              }}>
                {error}
              </div>
            ) : null}

            <div className="form-actions">
              <button type="submit" className={`btn ${isEdit ? 'btn-warning' : 'btn-primary'}`} disabled={busy}>
                <i className="fa-solid fa-save"></i> {busy ? 'Saving…' : (isEdit ? 'Update Committee' : 'Create Committee')}
              </button>
              <Link
                to={isEdit ? `/committees/${initialValue?.committee_id}` : '/committees'}
                className="btn btn-secondary"
              >
                <i className="fa-solid fa-times"></i> Cancel
              </Link>
              {isEdit && initialValue?.committee_id ? (
                <Link
                  to={`/committees/${initialValue.committee_id}`}
                  className="btn btn-danger"
                  onClick={(event) => {
                    if (!window.confirm('Delete this committee?')) event.preventDefault();
                  }}
                >
                  <i className="fa-solid fa-trash"></i> Delete
                </Link>
              ) : null}
            </div>
          </form>
        </div>
      </div>

      {!isEdit ? (
        <div className="mt-6 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
          <i className="fa-solid fa-info-circle text-primary"></i>
          <span>All fields marked with <span className="text-danger">*</span> are required</span>
        </div>
      ) : null}
    </div>
  );
}