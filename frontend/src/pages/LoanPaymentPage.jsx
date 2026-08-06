// LoanPaymentPage — mirrors loans/payment.php. Loads the loan, shows
// 4-card summary (Total Payable, Total Paid, Remaining, Status), then a
// form to record a payment. POSTs to /api/loans/{id}/payments.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { loansApi } from '../api/loansApi';

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function statusBadgeClass(status) {
  switch ((status || 'active').toLowerCase()) {
    case 'closed': return 'status-badge closed';
    case 'overdue': return 'status-badge overdue';
    case 'written_off': return 'status-badge written_off';
    case 'active':
    default:
      return 'status-badge active';
  }
}

export default function LoanPaymentPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [form, setForm] = useState({
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    note: '',
  });

  useEffect(() => {
    document.title = 'Record Payment · MicroFinance';
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await loansApi.get(id);
      const payload = response?.data ?? response;
      setData(payload);
    } catch (err) {
      setError(err.message || 'Failed to load loan.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      load();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [load]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!data?.loan) return;
    if (!form.amount || Number(form.amount) <= 0) {
      setError('Amount must be greater than zero.');
      return;
    }
    setSubmitting(true);
    setError('');
    try {
      await loansApi.recordPayment(data.loan.loan_id, {
        amount: Number(form.amount),
        payment_date: form.payment_date,
        note: form.note,
      });
      window.sessionStorage.setItem(
        'loans_feedback',
        JSON.stringify({
          kind: 'success',
          message: `Payment of ${formatMoney(form.amount)} recorded for loan ${data.loan.loan_code}.`,
        }),
      );
      navigate(`/loans/${data.loan.loan_id}`);
    } catch (err) {
      setError(err.message || 'Failed to record payment.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="loans-page">
        <div className="orb orb-1"></div>
        <div className="orb orb-2"></div>
        <div className="container">
          <div className="loans-loading">
            <i className="fa-solid fa-spinner fa-spin"></i> Loading…
          </div>
        </div>
      </div>
    );
  }

  if (error || !data?.loan) {
    return (
      <div className="loans-page">
        <div className="orb orb-1"></div>
        <div className="orb orb-2"></div>
        <div className="container">
          <div className="page-header">
            <div className="header-left">
              <div className="header-icon">
                <i className="fa-solid fa-money-bill-transfer"></i>
              </div>
              <div>
                <div className="header-title">
                  <span>Record</span> Payment
                  <span className="header-subtitle">Loan not found</span>
                </div>
              </div>
            </div>
          </div>
          <div className="loans-alert" role="alert">{error || 'Could not load this loan.'}</div>
          <div className="loans-back-wrap">
            <Link to="/loans" className="back-button">
              <i className="fa-solid fa-arrow-left"></i>
              <span className="text">Back to Loans</span>
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const loan = data.loan;
  const remaining = Math.max(0, Number(loan.total_payable || 0) - Number(loan.total_paid || 0));

  return (
    <div className="loans-page">
      <div className="orb orb-1"></div>
      <div className="orb orb-2"></div>
      <div className="orb orb-3"></div>

      <div className="container">
        <div className="page-header">
          <div className="header-left">
            <div className="header-icon">
              <i className="fa-solid fa-money-bill-transfer"></i>
            </div>
            <div>
              <div className="header-title">
                <span>Record</span> Payment
                <span className="header-subtitle">
                  <i className="fa-solid fa-barcode"></i> {loan.loan_code} — {loan.full_name || 'N/A'}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div className="loan-form-container">
          <div className="loan-form-card">
            <div className="form-header">
              <h3><i className="fa-solid fa-money-bill-transfer" style={{ color: '#22c55e' }}></i> Record Payment</h3>
              <p>Loan: {loan.loan_code} — {loan.full_name || ''}</p>
            </div>

            <div className="form-body">
              {/* Summary */}
              <div className="loan-payment-summary">
                <div>
                  <div className="summary-label">Total Payable</div>
                  <div className="summary-value">{formatMoney(loan.total_payable)}</div>
                </div>
                <div>
                  <div className="summary-label">Total Paid</div>
                  <div className="summary-value green">{formatMoney(loan.total_paid)}</div>
                </div>
                <div>
                  <div className="summary-label">Remaining</div>
                  <div className="summary-value pink">{formatMoney(remaining)}</div>
                </div>
                <div>
                  <div className="summary-label">Status</div>
                  <div>
                    <span className={statusBadgeClass(loan.status)}>
                      {String(loan.status || 'active').replace(/^./, (c) => c.toUpperCase())}
                    </span>
                  </div>
                </div>
              </div>

              {error ? (
                <div className="alert" role="alert" style={{
                  background: '#fee2e2',
                  border: '1px solid #fecaca',
                  color: '#991b1b',
                  padding: 16,
                  borderRadius: 12,
                  marginBottom: 20,
                }}>
                  <i className="fa-solid fa-circle-exclamation"></i> {error}
                </div>
              ) : null}

              <form onSubmit={handleSubmit} noValidate>
                <div className="form-group">
                  <label>Payment Amount <span className="required">*</span></label>
                  <input
                    type="number"
                    step="0.01"
                    className="form-control-custom"
                    placeholder="0.00"
                    value={form.amount}
                    onChange={(event) => setForm({ ...form, amount: event.target.value })}
                    max={remaining > 0 ? remaining : undefined}
                    required
                  />
                  {remaining > 0 ? (
                    <span className="form-hint">Maximum: {formatMoney(remaining)}</span>
                  ) : null}
                </div>

                <div className="form-group">
                  <label>Payment Date <span className="required">*</span></label>
                  <input
                    type="date"
                    className="form-control-custom"
                    value={form.payment_date}
                    onChange={(event) => setForm({ ...form, payment_date: event.target.value })}
                    required
                  />
                </div>

                <div className="form-group">
                  <label>Reference / Note</label>
                  <input
                    type="text"
                    className="form-control-custom"
                    placeholder="Receipt #, transaction ID, or note"
                    value={form.note}
                    onChange={(event) => setForm({ ...form, note: event.target.value })}
                  />
                </div>

                <div className="form-actions">
                  <Link to={`/loans/${loan.loan_id}`} className="btn-cancel">
                    <i className="fa-solid fa-times"></i> Cancel
                  </Link>
                  <button type="submit" className="btn-submit" style={{ background: 'linear-gradient(135deg, #22c55e, #16a34a)' }} disabled={submitting}>
                    <i className="fa-solid fa-check"></i> {submitting ? 'Saving…' : 'Record Payment'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}