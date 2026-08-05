import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { membersApi } from '../api/membersApi';

// Mirrors members/view.php + backend/app/Controllers/Members/MemberViewController.php.
// All data is fetched from GET /api/members/{id}.

function getInitials(name) {
  if (!name) return '';
  const upper = String(name).toUpperCase();
  if (upper.includes(' ')) {
    const parts = upper.split(/\s+/);
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return upper.slice(0, 2);
}

function formatDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function formatMoney(value) {
  const n = Number(value) || 0;
  return `$${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function loanStatusBadgeClass(status) {
  if (status === 'active') return 'bg-success';
  if (status === 'completed') return 'bg-info';
  return 'bg-danger';
}

export default function MemberProfilePage() {
  const { id } = useParams();
  const memberId = Number.parseInt(id, 10);

  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    document.title = 'Member Profile · MicroFinance';
  }, []);

  useEffect(() => {
    if (!Number.isFinite(memberId) || memberId <= 0) {
      setError('Invalid member id.');
      setLoading(false);
      return undefined;
    }
    let active = true;
    setLoading(true);
    setError('');
    membersApi
      .get(memberId)
      .then((response) => {
        if (!active) return;
        setData(response.data ?? response);
        setLoading(false);
      })
      .catch((err) => {
        if (!active) return;
        setError(err.message || 'Failed to load member.');
        setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [memberId]);

  if (loading) return <p>Loading member…</p>;
  if (error) {
    return (
      <div className="member-profile">
        <Link to="/" className="back-btn">
          <i className="fa-solid fa-arrow-left"></i> Back to Dashboard
        </Link>
        <div className="alert alert-danger" role="alert">{error}</div>
      </div>
    );
  }
  if (!data || !data.member) {
    return (
      <div className="member-profile">
        <Link to="/" className="back-btn">
          <i className="fa-solid fa-arrow-left"></i> Back to Dashboard
        </Link>
        <p>Member not found.</p>
      </div>
    );
  }

  const member = data.member;
  const loans = data.loans || [];
  const savings = data.savings || [];
  const payments = data.payments || [];
  const summary = data.summary || { loan_total: 0, paid_total: 0, due_total: 0, saving_total: 0 };
  const initials = getInitials(member.full_name);

  return (
    <div className="member-profile">
      <Link to="/" className="back-btn">
        <i className="fa-solid fa-arrow-left"></i> Back to Dashboard
      </Link>

      {/* Profile Header — mirrors members/view.php lines 77-104 */}
      <div className="card mb-4">
        <div className="card-body">
          <div className="row align-items-center">
            <div className="col-md-8 d-flex align-items-center gap-4">
              <div className="avatar-circle-lg bg-primary text-white">
                {initials}
              </div>
              <div>
                <h3 className="mb-0">{member.full_name}</h3>
                <p className="text-muted mb-0">{member.member_code}</p>
                <div className="mt-2">
                  <span className="badge bg-light text-dark me-2">
                    <i className="fa-solid fa-phone"></i> {member.phone ?? '—'}
                  </span>
                  <span className={`badge ${member.is_active === 1 || member.is_active === '1' || member.is_active === true ? 'bg-success' : 'bg-danger'}`}>
                    {member.is_active === 1 || member.is_active === '1' || member.is_active === true ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
            </div>
            <div className="col-md-4 text-md-end mt-3 mt-md-0">
              <a href={`/MicroFinance/members/edit.php?id=${memberId}`} className="btn btn-warning">
                <i className="fa-solid fa-pen"></i> Edit
              </a>
            </div>
          </div>
        </div>
      </div>

      {/* Summary Cards — mirrors members/view.php lines 107-140 */}
      <div className="row g-3 mb-4">
        <div className="col-md-3">
          <div className="card">
            <div className="card-body">
              <h6 className="text-muted mb-1">Total Loans</h6>
              <h4 className="text-primary">{formatMoney(summary.loan_total)}</h4>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card">
            <div className="card-body">
              <h6 className="text-muted mb-1">Total Paid</h6>
              <h4 className="text-success">{formatMoney(summary.paid_total)}</h4>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card">
            <div className="card-body">
              <h6 className="text-muted mb-1">Due Amount</h6>
              <h4 className="text-danger">{formatMoney(summary.due_total)}</h4>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card">
            <div className="card-body">
              <h6 className="text-muted mb-1">Total Savings</h6>
              <h4 className="text-info">{formatMoney(summary.saving_total)}</h4>
            </div>
          </div>
        </div>
      </div>

      {/* Loans Table — mirrors members/view.php lines 142-186 */}
      <div className="card mb-4">
        <div className="card-header">
          <h5><i className="fa-solid fa-hand-holding-usd text-primary"></i> Loans</h5>
        </div>
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Amount</th>
                  <th>Paid</th>
                  <th>Status</th>
                  <th>Maturity</th>
                </tr>
              </thead>
              <tbody>
                {loans.length === 0 ? (
                  <tr><td colSpan={5} className="text-center text-muted py-3">No loans found</td></tr>
                ) : (
                  loans.map((loan) => (
                    <tr key={loan.loan_id ?? `${loan.loan_code}-${loan.principal_amount}`}>
                      <td>{loan.loan_code}</td>
                      <td>{formatMoney(loan.principal_amount)}</td>
                      <td>{formatMoney(loan.total_paid)}</td>
                      <td>
                        <span className={`badge ${loanStatusBadgeClass(loan.status)}`}>
                          {loan.status ? loan.status.charAt(0).toUpperCase() + loan.status.slice(1) : '—'}
                        </span>
                      </td>
                      <td>{formatDate(loan.maturity_date)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Savings Table — mirrors members/view.php lines 188-221 */}
      <div className="card mb-4">
        <div className="card-header">
          <h5><i className="fa-solid fa-piggy-bank text-success"></i> Savings</h5>
        </div>
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Balance</th>
                  <th>Last Transaction</th>
                </tr>
              </thead>
              <tbody>
                {savings.length === 0 ? (
                  <tr><td colSpan={3} className="text-center text-muted py-3">No savings found</td></tr>
                ) : (
                  savings.map((s, idx) => (
                    <tr key={s.saving_id ?? idx}>
                      <td>{s.saving_type ? s.saving_type.charAt(0).toUpperCase() + s.saving_type.slice(1) : '—'}</td>
                      <td className="text-success fw-bold">{formatMoney(s.balance)}</td>
                      <td>{formatDate(s.last_transaction_date)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Payments Table — mirrors members/view.php lines 223-258 */}
      <div className="card">
        <div className="card-header">
          <h5><i className="fa-solid fa-receipt text-purple"></i> Loan Payments</h5>
        </div>
        <div className="card-body p-0">
          <div className="table-responsive">
            <table className="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Loan</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                {payments.length === 0 ? (
                  <tr><td colSpan={4} className="text-center text-muted py-3">No payments found</td></tr>
                ) : (
                  payments.map((p, idx) => (
                    <tr key={p.payment_id ?? idx}>
                      <td>{p.loan_code ?? '—'}</td>
                      <td className="fw-bold">{formatMoney(p.amount)}</td>
                      <td>{formatDate(p.payment_date)}</td>
                      <td>{p.note ?? '-'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}