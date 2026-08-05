// SavingsTransactionPage — handles /savings/deposit and /savings/withdraw.
// Mirrors savings/deposit.php and savings/withdraw.php (account select,
// amount, notes). Withdraw enforces balance check on the backend.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { savingsApi } from '../api/savingsApi';

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function SavingsTransactionPage({ mode }) {
  const { type } = useParams();
  const navigate = useNavigate();
  const resolvedMode = mode || (type === 'withdraw' ? 'withdraw' : 'deposit');
  const isWithdraw = resolvedMode === 'withdraw';

  const [accounts, setAccounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [form, setForm] = useState({ saving_id: '', amount: '', notes: '' });

  useEffect(() => {
    document.title = `${isWithdraw ? 'Withdraw' : 'Deposit'} Savings · MicroFinance`;
  }, [isWithdraw]);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await savingsApi.list({ page: 1, per_page: 100 });
      const rows = Array.isArray(response.data) ? response.data : [];
      setAccounts(rows);
    } catch (err) {
      setError(err.message || 'Failed to load savings accounts.');
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

  const selectedAccount = accounts.find((a) => String(a.saving_id) === String(form.saving_id)) || null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.saving_id) {
      setError('Please select a savings account.');
      return;
    }
    const amount = Number(form.amount);
    if (!amount || amount <= 0) {
      setError('Amount must be greater than zero.');
      return;
    }
    if (isWithdraw && selectedAccount && amount > Number(selectedAccount.balance || 0)) {
      setError('Insufficient balance.');
      return;
    }

    setSubmitting(true);
    setError('');
    try {
      if (isWithdraw) {
        await savingsApi.withdraw({
          saving_id: Number(form.saving_id),
          amount,
          notes: form.notes,
        });
      } else {
        await savingsApi.deposit({
          saving_id: Number(form.saving_id),
          amount,
          notes: form.notes,
        });
      }

      const memberName = selectedAccount ? (selectedAccount.full_name || 'member') : 'member';
      window.sessionStorage.setItem(
        'savings_feedback',
        JSON.stringify({
          kind: 'success',
          message: `${isWithdraw ? 'Withdrawal' : 'Deposit'} of ${formatMoney(amount)} for ${memberName} recorded.`,
        }),
      );
      navigate('/savings');
    } catch (err) {
      setError(err.message || `Failed to record ${isWithdraw ? 'withdrawal' : 'deposit'}.`);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="savings-page">
      <div className="container">
        <div className="page-header">
          <div className="header-left">
            <h3 className="page-title">
              <i className={`fa-solid ${isWithdraw ? 'fa-circle-arrow-up' : 'fa-circle-arrow-down'}`}></i>
              {' '}
              {isWithdraw ? 'Withdraw' : 'Deposit'} Savings
            </h3>
            <p className="page-subtitle">
              {isWithdraw
                ? 'Withdraw funds from a member savings account'
                : 'Add funds to a member savings account'}
            </p>
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
              <div className="savings-form-group full">
                <label className="savings-form-label">
                  <i className="fa-solid fa-user"></i> {isWithdraw ? 'Select Savings Account' : 'Select Account'}
                </label>
                <select
                  className="savings-form-input"
                  value={form.saving_id}
                  onChange={(e) => update({ saving_id: e.target.value })}
                  required
                  disabled={loading}
                >
                  <option value="">{isWithdraw ? 'Select Savings Account' : 'Select Account'}</option>
                  {accounts.map((a) => (
                    <option key={a.saving_id} value={String(a.saving_id)}>
                      {isWithdraw
                        ? `${a.full_name || 'N/A'} (Balance: ${formatMoney(a.balance)})`
                        : `${a.full_name || 'N/A'}`}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {isWithdraw && selectedAccount ? (
              <div className="savings-balance-hint">
                <i className="fa-solid fa-circle-info"></i> Available balance: {formatMoney(selectedAccount.balance)}
              </div>
            ) : null}

            <div className="savings-form-row">
              <div className="savings-form-group full">
                <label className="savings-form-label">
                  <i className="fa-solid fa-coins"></i> {isWithdraw ? 'Withdraw Amount' : 'Deposit Amount'}
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  className="savings-form-input"
                  placeholder={isWithdraw ? 'Withdraw Amount' : 'Deposit Amount'}
                  value={form.amount}
                  onChange={(e) => update({ amount: e.target.value })}
                  required
                  disabled={loading}
                />
              </div>
            </div>

            <div className="savings-form-row">
              <div className="savings-form-group full">
                <label className="savings-form-label">
                  <i className="fa-solid fa-note-sticky"></i> {isWithdraw ? 'Withdrawal Notes' : 'Notes'}
                </label>
                <textarea
                  rows={3}
                  className="savings-form-input"
                  placeholder={isWithdraw ? 'Withdrawal Notes' : 'Notes'}
                  value={form.notes}
                  onChange={(e) => update({ notes: e.target.value })}
                />
              </div>
            </div>

            <div className="savings-form-actions">
              <button
                type="submit"
                className={`savings-btn ${isWithdraw ? 'warning' : 'success'}`}
                disabled={submitting || loading}
              >
                <i className={`fa-solid ${isWithdraw ? 'fa-circle-arrow-up' : 'fa-circle-arrow-down'}`}></i>
                {' '}
                {submitting
                  ? 'Saving…'
                  : (isWithdraw ? 'Withdraw' : 'Deposit')}
              </button>
              <Link to="/savings" className="savings-btn secondary">
                <i className="fa-solid fa-arrow-left"></i> Back
              </Link>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}