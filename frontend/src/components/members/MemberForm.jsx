// MemberForm — mirrors members/add.php and members/edit.php.
// Three sections (Personal / Guarantor / Organization) with the same field
// names and column widths as the PHP form. Receives `initialValue`, `committees`,
// `branches`, `busy`, `error`, `onSubmit`, and renders a Cancel link.

import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

const EMPTY = {
  member_code: '',
  full_name: '',
  phone: '',
  dob: '',
  address: '',
  national_id: '',
  guarantor_name: '',
  guarantor_phone: '',
  committee_id: '',
  branch_id: '',
  join_date: '',
  is_active: true,
};

function normaliseInitial(initialValue) {
  if (!initialValue) return { ...EMPTY };
  return {
    member_code: initialValue.member_code || '',
    full_name: initialValue.full_name || '',
    phone: initialValue.phone || '',
    dob: initialValue.dob || '',
    address: initialValue.address || '',
    national_id: initialValue.national_id || '',
    guarantor_name: initialValue.guarantor_name || '',
    guarantor_phone: initialValue.guarantor_phone || '',
    committee_id: initialValue.committee_id ?? '',
    branch_id: initialValue.branch_id ?? '',
    join_date: initialValue.join_date || '',
    is_active: Number(initialValue.is_active) === 1 || initialValue.is_active === '1' || initialValue.is_active === true,
  };
}

export default function MemberForm({ initialValue, committees = [], branches = [], busy, error, mode, onSubmit }) {
  const [form, setForm] = useState(() => normaliseInitial(initialValue));

  // When the parent swaps initialValue (e.g. fetched member loaded), refresh.
  useMemo(() => {
    setForm(normaliseInitial(initialValue));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialValue?.member_id]);

  const update = (patch) => setForm((current) => ({ ...current, ...patch }));

  const handleSubmit = (event) => {
    event.preventDefault();
    const payload = {
      ...form,
      committee_id: form.committee_id === '' ? null : Number(form.committee_id),
      branch_id: form.branch_id === '' ? null : Number(form.branch_id),
      is_active: form.is_active ? 1 : 0,
    };
    onSubmit(payload);
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Personal */}
      <h5 className="mb-3"><i className="fa-solid fa-user text-primary"></i> Personal Information</h5>
      <div className="row g-3">
        <div className="col-md-6">
          <label className="form-label">Full Name *</label>
          <input
            type="text"
            className="form-control"
            placeholder="Enter full name"
            value={form.full_name}
            onChange={(event) => update({ full_name: event.target.value })}
            required
          />
        </div>
        <div className="col-md-6">
          <label className="form-label">Member Code *</label>
          <input
            type="text"
            className="form-control"
            placeholder="e.g., MEM-2024-001"
            value={form.member_code}
            onChange={(event) => update({ member_code: event.target.value })}
            required
          />
        </div>
        <div className="col-md-6">
          <label className="form-label">Phone *</label>
          <input
            type="text"
            className="form-control"
            placeholder="Enter phone number"
            value={form.phone}
            onChange={(event) => update({ phone: event.target.value })}
            required
          />
        </div>
        <div className="col-md-6">
          <label className="form-label">Date of Birth</label>
          <input
            type="date"
            className="form-control"
            value={form.dob}
            onChange={(event) => update({ dob: event.target.value })}
          />
        </div>
        <div className="col-md-6">
          <label className="form-label">National ID</label>
          <input
            type="text"
            className="form-control"
            placeholder="Enter NID number"
            value={form.national_id}
            onChange={(event) => update({ national_id: event.target.value })}
          />
        </div>
        <div className="col-12">
          <label className="form-label">Address</label>
          <textarea
            rows={2}
            className="form-control"
            placeholder="Enter full address"
            value={form.address}
            onChange={(event) => update({ address: event.target.value })}
          />
        </div>
      </div>

      <hr className="my-4" />

      {/* Guarantor */}
      <h5 className="mb-3"><i className="fa-solid fa-handshake text-success"></i> Guarantor Information</h5>
      <div className="row g-3">
        <div className="col-md-6">
          <label className="form-label">Guarantor Name</label>
          <input
            type="text"
            className="form-control"
            placeholder="Enter guarantor name"
            value={form.guarantor_name}
            onChange={(event) => update({ guarantor_name: event.target.value })}
          />
        </div>
        <div className="col-md-6">
          <label className="form-label">Guarantor Phone</label>
          <input
            type="text"
            className="form-control"
            placeholder="Enter guarantor phone"
            value={form.guarantor_phone}
            onChange={(event) => update({ guarantor_phone: event.target.value })}
          />
        </div>
      </div>

      <hr className="my-4" />

      {/* Organization */}
      <h5 className="mb-3"><i className="fa-solid fa-building text-info"></i> Organization Information</h5>
      <div className="row g-3">
        <div className="col-md-4">
          <label className="form-label">Committee *</label>
          <select
            className="form-select"
            value={form.committee_id === null ? '' : String(form.committee_id)}
            onChange={(event) => update({ committee_id: event.target.value })}
            required
          >
            <option value="">Select Committee</option>
            {committees.map((c) => (
              <option key={c.committee_id} value={String(c.committee_id)}>
                {c.committee_name}
              </option>
            ))}
          </select>
        </div>
        <div className="col-md-4">
          <label className="form-label">Branch *</label>
          <select
            className="form-select"
            value={form.branch_id === null ? '' : String(form.branch_id)}
            onChange={(event) => update({ branch_id: event.target.value })}
            required
          >
            <option value="">Select Branch</option>
            {branches.map((b) => (
              <option key={b.branch_id} value={String(b.branch_id)}>
                {b.branch_name}
              </option>
            ))}
          </select>
        </div>
        <div className="col-md-4">
          <label className="form-label">Join Date *</label>
          <input
            type="date"
            className="form-control"
            value={form.join_date}
            onChange={(event) => update({ join_date: event.target.value })}
            required
          />
        </div>
        <div className="col-12">
          <div className="form-check">
            <input
              type="checkbox"
              className="form-check-input"
              id="isActive"
              checked={!!form.is_active}
              onChange={(event) => update({ is_active: event.target.checked })}
            />
            <label className="form-check-label" htmlFor="isActive">Active Member</label>
          </div>
        </div>
      </div>

      <hr className="my-4" />

      {error ? (
        <div className="alert alert-danger mb-3" role="alert">{error}</div>
      ) : null}

      <div className="d-flex gap-3">
        <button type="submit" className="btn btn-primary" disabled={busy}>
          <i className="fa-solid fa-save"></i> {busy ? 'Saving…' : (mode === 'edit' ? 'Update Member' : 'Save Member')}
        </button>
        <Link to="/members" className="btn btn-secondary">
          <i className="fa-solid fa-times"></i> Cancel
        </Link>
      </div>
    </form>
  );
}