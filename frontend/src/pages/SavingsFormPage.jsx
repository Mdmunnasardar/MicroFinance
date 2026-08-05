// SavingsFormPage — Add Account form. Mirrors savings/add.php (member
// dropdown, amount, date, note). Loads members via savingsApi.list, submits
// via savingsApi.create, redirects to /savings with sessionStorage feedback.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { savingsApi } from '../api/savingsApi';

const EMPTY = {
  member_id: '',
  saving_type: 'individual',
  balance: '',
  last_transaction_date: '',
  note: '',
};

export default function SavingsFormPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ ...EMPTY, last_transaction_date: new Date().toISOString().slice(0, 10) });
  const [members, setMembers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => { document.title = 'Add Savings · MicroFinance'; }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await savingsApi.list({ page: 1, per_page: 1 });
      const m = response?.meta?.filters?.members || [];
      setMembers(Array.isArray(m) ? m : []);
    } catch (err) {
      setError(err.message || 'Failed to load members.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => { if (active) load(); }, 0);
    return () => { active = false; clearTimeout(timer); };
  }, [load]);

  const update = (patch) => setForm((current) => ({ ...current, ...patch }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.member_id) {
      setError('Please select a member.');
      return;
    }
    if (Number(form.balance) < 0) {
      setError('Balance cannot be negative.');
      return;
    }
    setSubmitting(true);
    setError('');
    try {
      await savingsApi.create({
        member_id: Number(form.member_id),
        saving_type: form.saving_type,
        balance: Number(form.balance || 0),
        last_transaction_date: form.last_transaction_date || new Date().toISOString().slice(0, 10),
        note: form.note,
      });
      const member = members.find((m) => Number(m.member_id) === Number(form.member_id));
      const memberName = member ? member.full_name : 'member';
      window.sessionStorage.setItem(
        'savings_feedback',
        JSON.stringify({
          kind: 'success',
          message: `Savings account for ${memberName} created successfully.`,
        }),
      );
      navigate('/savings');
    } catch (err) {
      setError(err.message || 'Failed to create savings account.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="savings-page">
      <div className="container">
        <div className="page-header">
          <div className="header-left">
            <h3 className="page-title"><i className="fa-solid fa-plus-circle"></i> Add Savings Account</h3>
            <p className="page-subtitle">Open a new savings account for a member</p>
          </div>
          <div className="header-right">
            <Link to="/savings" className="savings-btn secondary">
              <i className="fa-solid fa-arrow-left"></i> Back to Savings
            </Link>
          </div>
        </div>

        <div className="savings-form-card">
          {error ? (
            <div className="savings-alert" role="alert">
              <i className="fa-solid fa-circle-exclamation"></i> {error}
            </div>
          ) : null}

          <form onSubmit={handleSubmit} noValidate>
            <div className="savings-form-row">
              <div className="savings-form-group">
                <label className="savings-form-label"><i className="fa-solid fa-user"></i> Select Member</label>
                <select
                  className="savings-form-input"
                  value={form.member_id}
                  onChange={(e) => update({ member_id: e.target.value })}
                  required
                  disabled={loading}
                >
                  <option value="">Select Member</option>
                  {members.map((m) => (
                    <option key={m.member_id} value={String(m.member_id)}>
                      {m.full_name} ({m.member_code})
                    </option>
                  ))}
                </select>
              </div>
              <div className="savings-form-group">
                <label className="savings-form-label"><i className="fa-solid fa-circle-info"></i> Saving Type</label>
                <select
                  className="savings-form-input"
                  value={form.saving_type}
                  onChange={(e) => update({ saving_type: e.target.value })}
                >
                  <option value="individual">Individual</option>
                  <option value="group">Group</option>
                </select>
              </div>
            </div>

            <div className="savings-form-row">
              <div className="savings-form-group">
                <label className="savings-form-label"><i className="fa-solid fa-coins"></i> Initial Balance</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  className="savings-form-input"
                  placeholder="0.00"
                  value={form.balance}
                  onChange={(e) => update({ balance: e.target.value })}
                />
              </div>
              <div className="savings-form-group">
                <label className="savings-form-label"><i className="fa-solid fa-calendar"></i> Date</label>
                <input
                  type="date"
                  className="savings-form-input"
                  value={form.last_transaction_date}
                  onChange={(e) => update({ last_transaction_date: e.target.value })}
                  required
                />
              </div>
            </div>

            <div className="savings-form-row">
              <div className="savings-form-group full">
                <label className="savings-form-label"><i className="fa-solid fa-note-sticky"></i> Note</label>
                <input
                  type="text"
                  className="savings-form-input"
                  placeholder="Optional reference note"
                  value={form.note}
                  onChange={(e) => update({ note: e.target.value })}
                />
              </div>
            </div>

            <div className="savings-form-actions">
              <button type="submit" className="savings-btn success" disabled={submitting || loading}>
                <i className="fa-solid fa-check"></i> {submitting ? 'Saving…' : 'Add Deposit'}
              </button>
              <Link to="/savings" className="savings-btn secondary">
                <i className="fa-solid fa-times"></i> Cancel
              </Link>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}