// LoanForm — controlled form for both add and edit flows.
// Mirrors loans/add.php and loans/edit.php. Two-column rows, same fields.
// The PHP edit form shows a Status dropdown that the add form does not — we
// match that by rendering status only in edit mode.

import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

const EMPTY = {
  loan_code: '',
  member_id: '',
  principal_amount: '',
  interest_rate: '',
  interest_type: 'flat',
  loan_term_months: '',
  installment_type: 'monthly',
  disbursement_date: '',
  first_installment_date: '',
  purpose: '',
  status: 'active',
};

function normalise(initialValue) {
  if (!initialValue) {
    return { ...EMPTY, disbursement_date: new Date().toISOString().slice(0, 10) };
  }
  return {
    loan_code: initialValue.loan_code || '',
    member_id: initialValue.member_id != null ? String(initialValue.member_id) : '',
    principal_amount: initialValue.principal_amount != null ? String(initialValue.principal_amount) : '',
    interest_rate: initialValue.interest_rate != null ? String(initialValue.interest_rate) : '',
    interest_type: initialValue.interest_type || 'flat',
    loan_term_months: initialValue.loan_term_months != null ? String(initialValue.loan_term_months) : '',
    installment_type: initialValue.installment_type || 'monthly',
    disbursement_date: initialValue.disbursement_date || '',
    first_installment_date: initialValue.first_installment_date || '',
    purpose: initialValue.purpose || '',
    status: initialValue.status || 'active',
  };
}

export default function LoanForm({ initialValue, members = [], busy, error, mode, onSubmit }) {
  const isEdit = mode === 'edit';
  const [form, setForm] = useState(() => normalise(initialValue));

  useEffect(() => {
    setForm(normalise(initialValue));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialValue?.loan_id]);

  const update = (patch) => setForm((current) => ({ ...current, ...patch }));

  const handleSubmit = (event) => {
    event.preventDefault();
    const payload = {
      ...form,
      member_id: form.member_id === '' ? 0 : Number(form.member_id),
      principal_amount: form.principal_amount === '' ? 0 : Number(form.principal_amount),
      interest_rate: form.interest_rate === '' ? 0 : Number(form.interest_rate),
      loan_term_months: form.loan_term_months === '' ? 0 : Number(form.loan_term_months),
    };
    if (isEdit) {
      payload.status = form.status;
    }
    onSubmit(payload);
  };

  return (
    <div className="loans-page">
      <div className="orb orb-1"></div>
      <div className="orb orb-2"></div>

      <div className="container">
        <div className="form-wrapper">
          <div className="form-title">
            <div className="icon"><i className={isEdit ? 'fa-solid fa-edit' : 'fa-solid fa-plus-circle'}></i></div>
            <span>{isEdit ? `Edit Loan #${initialValue?.loan_code || ''}` : 'Create New Loan'}</span>
          </div>

          {error ? (
            <div className="alert" role="alert" style={{
              padding: '12px 16px',
              marginBottom: 20,
              borderRadius: 8,
              background: 'rgba(255, 82, 82, 0.12)',
              border: '1px solid rgba(255, 82, 82, 0.4)',
              color: '#FF8A80',
              fontSize: 13,
              fontWeight: 500,
            }}>
              <i className="fa-solid fa-circle-exclamation"></i> {error}
            </div>
          ) : null}

          <form onSubmit={handleSubmit} noValidate>
            <div className="form-row">
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-barcode"></i> Loan Code</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="e.g., L001"
                  value={form.loan_code}
                  onChange={(event) => update({ loan_code: event.target.value })}
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-user"></i> Select Member</label>
                <select
                  className="form-control"
                  value={form.member_id}
                  onChange={(event) => update({ member_id: event.target.value })}
                  required
                  disabled={isEdit}
                >
                  <option value="">Select Member</option>
                  {members.map((member) => (
                    <option key={member.member_id} value={String(member.member_id)}>
                      {member.full_name} ({member.member_code})
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-money-bill-wave"></i> Principal Amount</label>
                <input
                  type="number"
                  step="0.01"
                  className="form-control"
                  placeholder="Enter principal amount"
                  value={form.principal_amount}
                  onChange={(event) => update({ principal_amount: event.target.value })}
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-percent"></i> Interest Rate</label>
                <input
                  type="number"
                  step="0.01"
                  className="form-control"
                  placeholder="Enter interest rate"
                  value={form.interest_rate}
                  onChange={(event) => update({ interest_rate: event.target.value })}
                  required
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-calculator"></i> Interest Type</label>
                <select
                  className="form-control"
                  value={form.interest_type}
                  onChange={(event) => update({ interest_type: event.target.value })}
                >
                  <option value="flat">Flat Rate</option>
                  <option value="reducing_balance">Reducing Balance</option>
                </select>
              </div>
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-clock"></i> Loan Term (Months)</label>
                <input
                  type="number"
                  className="form-control"
                  placeholder="e.g., 12"
                  value={form.loan_term_months}
                  onChange={(event) => update({ loan_term_months: event.target.value })}
                  required
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-calendar-alt"></i> Installment Type</label>
                <select
                  className="form-control"
                  value={form.installment_type}
                  onChange={(event) => update({ installment_type: event.target.value })}
                >
                  <option value="monthly">Monthly</option>
                  <option value="weekly">Weekly</option>
                </select>
              </div>
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-calendar-day"></i> Disbursement Date</label>
                <input
                  type="date"
                  className="form-control"
                  value={form.disbursement_date}
                  onChange={(event) => update({ disbursement_date: event.target.value })}
                  required
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-calendar-check"></i> First Installment Date</label>
                <input
                  type="date"
                  className="form-control"
                  value={form.first_installment_date}
                  onChange={(event) => update({ first_installment_date: event.target.value })}
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label"><i className="fa-solid fa-info-circle"></i> Loan Purpose</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Enter loan purpose"
                  value={form.purpose}
                  onChange={(event) => update({ purpose: event.target.value })}
                />
              </div>
            </div>

            {isEdit ? (
              <div className="form-row">
                <div className="form-group">
                  <label className="form-label"><i className="fa-solid fa-flag"></i> Status</label>
                  <select
                    className="form-control"
                    value={form.status}
                    onChange={(event) => update({ status: event.target.value })}
                  >
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                    <option value="overdue">Overdue</option>
                    <option value="written_off">Written Off</option>
                  </select>
                </div>
                <div className="form-group"></div>
              </div>
            ) : null}

            <button type="submit" className="btn btn-success" style={{ marginTop: 8 }}>
              <i className="fa-solid fa-save"></i> {busy ? 'Saving…' : (isEdit ? 'Update Loan' : 'Create Loan')}
            </button>
            <Link to={isEdit ? `/loans/${initialValue?.loan_id}` : '/loans'} className="btn btn-secondary" style={{ marginTop: 8 }}>
              <i className="fa-solid fa-arrow-left"></i> Back to Loan List
            </Link>
          </form>
        </div>
      </div>
    </div>
  );
}
