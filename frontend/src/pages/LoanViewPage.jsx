// LoanViewPage — mirrors loans/view.php. Deep-blue header, 4 summary cards,
// loan/member/dates/purpose detail sections, action buttons (Edit, Delete,
// Print). Confirmation modal for destructive operations.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { loansApi } from '../api/loansApi';

const STATUS_META = {
  active:     { className: 'status-badge active',     icon: 'fa-check-circle',          label: 'Active' },
  closed:     { className: 'status-badge closed',     icon: 'fa-check-double',         label: 'Closed' },
  overdue:    { className: 'status-badge overdue',    icon: 'fa-exclamation-triangle', label: 'Overdue' },
  written_off:{ className: 'status-badge written_off',icon: 'fa-times-circle',         label: 'Written Off' },
};

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatLongDate(value) {
  if (!value) return 'N/A';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
}

function titleCase(value) {
  if (!value) return '';
  return String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function LoanViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [busy, setBusy] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => { document.title = 'Loan Details · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  useEffect(() => {
    const sessionFeedback = window.sessionStorage?.getItem('loans_feedback');
    if (sessionFeedback) {
      try {
        const parsed = JSON.parse(sessionFeedback);
        if (parsed?.message) setFeedback(parsed);
      } catch (_e) {
        // ignore
      }
      window.sessionStorage.removeItem('loans_feedback');
    }
  }, []);

  const loadData = useCallback(async () => {
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
      loadData();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadData]);

  const handleDelete = async () => {
    if (!data?.loan) return;
    setBusy(true);
    try {
      await loansApi.remove(data.loan.loan_id);
      window.sessionStorage.setItem(
        'loans_feedback',
        JSON.stringify({
          kind: 'success',
          message: `Loan "${data.loan.loan_code}" deleted.`,
        }),
      );
      navigate('/loans');
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to delete loan.' });
      setConfirmDelete(false);
    } finally {
      setBusy(false);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  if (loading) {
    return (
      <div className="loans-page">
        <div className="orb orb-1"></div>
        <div className="orb orb-2"></div>
        <div className="orb orb-3"></div>
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
        <div className="orb orb-3"></div>
        <div className="container">
          <div className="page-header">
            <div className="header-left">
              <div className="header-icon">
                <i className="fa-solid fa-file-invoice"></i>
              </div>
              <div>
                <div className="header-title">
                  <span>Loan</span> Details
                  <span className="header-subtitle">Loan not found</span>
                </div>
              </div>
            </div>
          </div>
          <div className="loans-alert" role="alert">
            {error || 'The loan could not be found.'}
          </div>
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
  const payments = data.payments || [];
  const statusKey = (loan.status || 'active').toLowerCase();
  const statusMeta = STATUS_META[statusKey] || STATUS_META.active;
  const remaining = Math.max(0, Number(loan.total_payable || 0) - Number(loan.total_paid || 0));

  return (
    <div className="loans-page">
      {feedback ? (
        <div className={`loans-toast ${feedback.kind}`} role="status">
          <span className="loans-toast-icon" aria-hidden>{feedback.kind === 'success' ? '✓' : '!'}</span>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      <div className="orb orb-1"></div>
      <div className="orb orb-2"></div>
      <div className="orb orb-3"></div>

      <div className="container">
        {/* Print header — mirrors view.php lines 742-747 */}
        <div className="print-header">
          <h2>Loan Management System</h2>
          <p>Loan Details - {loan.loan_code}</p>
          <p style={{ fontSize: 12, color: '#999' }}>
            Printed on: {new Date().toLocaleString('en-GB', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}
          </p>
        </div>

        {/* Page header — mirrors view.php lines 749-768 */}
        <div className="page-header no-print">
          <div className="header-left">
            <div className="header-icon">
              <i className="fa-solid fa-file-invoice"></i>
            </div>
            <div>
              <div className="header-title">
                <span>Loan</span> Details
                <span className="header-subtitle">
                  <i className="fa-solid fa-barcode"></i> {loan.loan_code}
                </span>
              </div>
            </div>
          </div>
          <div>
            <Link to="/loans" className="back-button">
              <i className="fa-solid fa-arrow-left"></i> Back to List
            </Link>
          </div>
        </div>

        {/* Summary cards — mirrors view.php lines 771-788 */}
        <div className="summary-grid">
          <div className="summary-card">
            <div className="summary-label">Principal Amount</div>
            <div className="summary-value blue">{formatMoney(loan.principal_amount)}</div>
          </div>
          <div className="summary-card">
            <div className="summary-label">Total Payable</div>
            <div className="summary-value gold">{formatMoney(loan.total_payable)}</div>
          </div>
          <div className="summary-card">
            <div className="summary-label">Total Paid</div>
            <div className="summary-value green">{formatMoney(loan.total_paid)}</div>
          </div>
          <div className="summary-card">
            <div className="summary-label">Remaining Balance</div>
            <div className="summary-value pink">{formatMoney(remaining)}</div>
          </div>
        </div>

        {/* Loan Information — mirrors view.php lines 791-834 */}
        <div className="detail-section">
          <div className="section-title">
            <i className="fa-solid fa-info-circle"></i> Loan Information
          </div>
          <div className="detail-grid">
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-hashtag"></i> Loan ID</div>
              <div className="detail-value">#{loan.loan_id}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-barcode"></i> Loan Code</div>
              <div className="detail-value blue">{escapeHtml(loan.loan_code)}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-info-circle"></i> Status</div>
              <div className="detail-value">
                <span className={statusMeta.className}>
                  <i className={`fa-solid ${statusMeta.icon}`}></i>
                  {statusMeta.label}
                </span>
              </div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-percent"></i> Interest Rate</div>
              <div className="detail-value cyan">{loan.interest_rate}%</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-calculator"></i> Interest Type</div>
              <div className="detail-value blue">{titleCase(loan.interest_type)}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-clock"></i> Loan Term</div>
              <div className="detail-value cyan">{loan.loan_term_months} Months</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-calendar-alt"></i> Installment Type</div>
              <div className="detail-value gold">{titleCase(loan.installment_type)}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-money-bill-wave"></i> Installment Amount</div>
              <div className="detail-value gold">{formatMoney(loan.installment_amount)}</div>
            </div>
          </div>
        </div>

        {/* Member Information — mirrors view.php lines 837-851 */}
        <div className="detail-section">
          <div className="section-title">
            <i className="fa-solid fa-user"></i> Member Information
          </div>
          <div className="detail-grid">
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-user"></i> Full Name</div>
              <div className="detail-value blue">{escapeHtml(loan.full_name || 'N/A')}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-id-card"></i> Member Code</div>
              <div className="detail-value">{escapeHtml(loan.member_code || 'N/A')}</div>
            </div>
          </div>
        </div>

        {/* Dates — mirrors view.php lines 854-872 */}
        <div className="detail-section">
          <div className="section-title">
            <i className="fa-solid fa-calendar-alt"></i> Dates
          </div>
          <div className="detail-grid">
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-calendar-day"></i> Disbursement Date</div>
              <div className="detail-value">{formatLongDate(loan.disbursement_date)}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-calendar-check"></i> Maturity Date</div>
              <div className="detail-value">{formatLongDate(loan.maturity_date)}</div>
            </div>
            <div className="detail-item">
              <div className="detail-label"><i className="fa-solid fa-calendar-plus"></i> First Installment Date</div>
              <div className="detail-value gold">{formatLongDate(loan.first_installment_date)}</div>
            </div>
          </div>
        </div>

        {/* Purpose — mirrors view.php lines 875-883 */}
        {loan.purpose ? (
          <div className="detail-section">
            <div className="section-title">
              <i className="fa-solid fa-info-circle"></i> Purpose
            </div>
            <div className="detail-item" style={{ padding: '16px 20px' }}>
              <div className="detail-value">{escapeHtml(loan.purpose)}</div>
            </div>
          </div>
        ) : null}

        {/* Payments history — read-only summary added alongside the PHP view */}
        {payments.length > 0 ? (
          <div className="detail-section">
            <div className="section-title">
              <i className="fa-solid fa-receipt"></i> Payment History
            </div>
            <div className="table-wrapper">
              <div className="table-responsive">
                <table className="table">
                  <thead>
                    <tr>
                      <th><i className="fa-solid fa-hashtag"></i> ID</th>
                      <th><i className="fa-solid fa-calendar-day"></i> Date</th>
                      <th><i className="fa-solid fa-money-bill-wave"></i> Amount</th>
                      <th><i className="fa-solid fa-info-circle"></i> Note</th>
                    </tr>
                  </thead>
                  <tbody>
                    {payments.map((payment, idx) => (
                      <tr key={payment.payment_id} style={{ animationDelay: `${0.04 * (idx + 1)}s` }}>
                        <td>
                          <span className="id-badge">
                            <i className="fa-solid fa-hashtag" style={{ fontSize: 8, marginRight: 2, opacity: 0.6 }}></i>
                            {payment.payment_id}
                          </span>
                        </td>
                        <td>{formatLongDate(payment.payment_date)}</td>
                        <td>{formatMoney(payment.amount)}</td>
                        <td>{payment.note ? escapeHtml(payment.note) : '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        ) : null}

        {/* Action buttons — mirrors view.php lines 887-897 */}
        <div className="action-buttons no-print">
          <Link to={`/loans/${loan.loan_id}/edit`} className="btn btn-warning">
            <i className="fa-solid fa-edit"></i> Edit Loan
          </Link>
          <button
            type="button"
            className="btn btn-danger"
            onClick={() => setConfirmDelete(true)}
            disabled={busy}
          >
            <i className="fa-solid fa-trash"></i> Delete Loan
          </button>
          <Link to={`/loans/${loan.loan_id}/payment`} className="btn btn-success">
            <i className="fa-solid fa-money-bill-transfer"></i> Record Payment
          </Link>
          <button type="button" className="btn btn-primary" onClick={handlePrint}>
            <i className="fa-solid fa-print"></i> Print Details
          </button>
        </div>
      </div>

      {confirmDelete ? (
        <div className="loans-backdrop" role="dialog" aria-modal="true">
          <div className="loans-modal danger">
            <div className="loans-modal-head">
              <div className="loans-modal-icon danger" aria-hidden><i className="fa-solid fa-trash"></i></div>
              <div>
                <h3>Delete loan?</h3>
                <p>"{loan.loan_code}" (Member: {loan.full_name || 'N/A'})</p>
              </div>
            </div>
            <div className="loans-modal-body">
              <p style={{ margin: 0, color: '#7A9BCB', fontSize: 13, lineHeight: 1.5 }}>
                ⚠️ Are you sure you want to delete this loan? Loans with recorded payments cannot be deleted.
              </p>
              <div className="loans-modal-actions">
                <button type="button" className="btn btn-secondary" onClick={() => setConfirmDelete(false)} disabled={busy}>Cancel</button>
                <button type="button" className="btn btn-danger" onClick={handleDelete} disabled={busy}>
                  <i className="fa-solid fa-trash"></i> {busy ? 'Deleting…' : 'Delete loan'}
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}