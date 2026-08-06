// LoansTable — table layout mirroring the deep-blue PHP view.
// Columns mirror loans/index.php lines 357-374 exactly: ID, Code, Member,
// Principal, Rate, Total, Paid, Installment, Status, Disbursement, Maturity,
// Actions.

import { Link } from 'react-router-dom';

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatPercent(value) {
  const n = Number(value) || 0;
  return `${n}%`;
}

function formatDate(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const STATUS_META = {
  active:     { className: 'badge-success', icon: 'fa-check-circle',          label: 'Active' },
  closed:     { className: 'badge-primary', icon: 'fa-check-double',         label: 'Closed' },
  overdue:    { className: 'badge-danger',  icon: 'fa-exclamation-triangle', label: 'Overdue' },
  written_off:{ className: 'badge-dark',    icon: 'fa-times-circle',         label: 'Written Off' },
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

export default function LoansTable({ items, onDelete }) {
  if (items.length === 0) {
    return (
      <div className="loans-empty">
        <i className="fa-solid fa-inbox"></i>
        <h5>No Loans Found</h5>
        <p>Start by creating your first loan application</p>
        <Link to="/loans/new" className="btn btn-primary">
          <i className="fa-solid fa-plus-circle"></i> Create First Loan
        </Link>
      </div>
    );
  }

  return (
    <div className="table-wrapper">
      <div className="table-responsive">
        <table className="table">
          <thead>
            <tr>
              <th><i className="fa-solid fa-hashtag"></i> ID</th>
              <th><i className="fa-solid fa-barcode"></i> Code</th>
              <th><i className="fa-solid fa-user"></i> Member</th>
              <th><i className="fa-solid fa-money-bill-wave"></i> Principal</th>
              <th><i className="fa-solid fa-percent"></i> Rate</th>
              <th><i className="fa-solid fa-calculator"></i> Total</th>
              <th><i className="fa-solid fa-check-circle"></i> Paid</th>
              <th><i className="fa-solid fa-calendar-alt"></i> Installment</th>
              <th><i className="fa-solid fa-info-circle"></i> Status</th>
              <th><i className="fa-solid fa-calendar-day"></i> Disbursement</th>
              <th><i className="fa-solid fa-calendar-check"></i> Maturity</th>
              <th><i className="fa-solid fa-cogs"></i> Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.map((loan, idx) => {
              const statusKey = (loan.status || 'active').toLowerCase();
              const statusMeta = STATUS_META[statusKey] || STATUS_META.active;
              const delay = 0.04 * (idx + 1);
              return (
                <tr key={loan.loan_id} style={{ animationDelay: `${delay}s` }}>
                  <td>
                    <span className="id-badge">
                      <i className="fa-solid fa-hashtag" style={{ fontSize: 8, marginRight: 2, opacity: 0.6 }}></i>
                      {loan.loan_id}
                    </span>
                  </td>
                  <td>{escapeHtml(loan.loan_code || '')}</td>
                  <td>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                      <span className="member-name">{escapeHtml(loan.full_name || 'N/A')}</span>
                      <span className="member-code">
                        <i className="fa-solid fa-id-card"></i> {escapeHtml(loan.member_code || '')}
                      </span>
                    </div>
                  </td>
                  <td>{formatMoney(loan.principal_amount)}</td>
                  <td>{formatPercent(loan.interest_rate)}</td>
                  <td>{formatMoney(loan.total_payable)}</td>
                  <td>{formatMoney(loan.total_paid)}</td>
                  <td>{formatMoney(loan.installment_amount)}</td>
                  <td>
                    <span className={`badge ${statusMeta.className}`}>
                      <i className={`fa-solid ${statusMeta.icon}`} style={{ fontSize: 8 }}></i>
                      {statusMeta.label}
                    </span>
                  </td>
                  <td>{formatDate(loan.disbursement_date)}</td>
                  <td>{formatDate(loan.maturity_date)}</td>
                  <td>
                    <div style={{ display: 'flex', gap: 4, flexWrap: 'wrap' }}>
                      <Link to={`/loans/${loan.loan_id}/edit`} className="btn btn-warning btn-sm" title="Edit Loan" style={{ padding: '4px 10px', fontSize: 10 }}>
                        <i className="fa-solid fa-pen"></i>
                      </Link>
                      <button
                        type="button"
                        className="btn btn-danger btn-sm"
                        title="Delete Loan"
                        style={{ padding: '4px 10px', fontSize: 10 }}
                        onClick={() => onDelete && onDelete(loan)}
                      >
                        <i className="fa-solid fa-trash"></i>
                      </button>
                      <Link to={`/loans/${loan.loan_id}`} className="btn btn-secondary btn-sm" title="View Details" style={{ padding: '4px 10px', fontSize: 10 }}>
                        <i className="fa-solid fa-eye"></i>
                      </Link>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}
