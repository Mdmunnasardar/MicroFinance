import { useMemo } from 'react';
import { deriveStatus, balanceOf } from '../../utils/installment';
import { formatMoney } from '../../utils/formatMoney';
import { formatDateShort } from '../../utils/installment';

const ChevronIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="9 18 15 12 9 6" />
  </svg>
);

function summarizeLoans(installments) {
  const map = new Map();
  for (const item of installments || []) {
    const loan = item.loan || {};
    const loanId = item.loanId || loan.id || loan.loanId;
    if (loanId == null) continue;
    const bucket = map.get(loanId) || {
      id: loanId,
      code: loan.code || loan.loanCode || loan.loan_code || `Loan #${loanId}`,
      member: item.member || loan.member || null,
      items: [],
    };
    bucket.items.push(item);
    if (loan.code && !bucket.code) bucket.code = loan.code;
    if (loan.loanCode && !bucket.code) bucket.code = loan.loanCode;
    map.set(loanId, bucket);
  }

  return Array.from(map.values()).map((bucket) => {
    const due = bucket.items.reduce((s, i) => s + (Number(i.dueAmount) || 0), 0);
    const paid = bucket.items.reduce((s, i) => s + (Number(i.paidAmount) || 0), 0);
    const balance = Math.max(0, due - paid);
    const statuses = bucket.items.map(deriveStatus);
    const overdueCount = statuses.filter((s) => s === 'overdue').length;
    const pendingCount = statuses.filter((s) => s === 'pending' || s === 'partial').length;
    const paidCount = statuses.filter((s) => s === 'paid').length;
    const sorted = [...bucket.items].sort((a, b) => {
      const ad = a.dueDate ? new Date(a.dueDate).getTime() : Infinity;
      const bd = b.dueDate ? new Date(b.dueDate).getTime() : Infinity;
      return ad - bd;
    });
    const next = sorted.find((i) => deriveStatus(i) !== 'paid');
    return {
      ...bucket,
      due,
      paid,
      balance,
      overdueCount,
      pendingCount,
      paidCount,
      next,
    };
  }).sort((a, b) => {
    const aOverdue = a.overdueCount > 0 ? 0 : 1;
    const bOverdue = b.overdueCount > 0 ? 0 : 1;
    if (aOverdue !== bOverdue) return aOverdue - bOverdue;
    const aNext = a.next?.dueDate ? new Date(a.next.dueDate).getTime() : Infinity;
    const bNext = b.next?.dueDate ? new Date(b.next.dueDate).getTime() : Infinity;
    return aNext - bNext;
  });
}

export default function LoanPicker({ installments, loading, error, selectedLoanId, onSelect }) {
  const loans = useMemo(() => summarizeLoans(installments), [installments]);

  if (loading) {
    return <div className="inst-loan-empty">Loading loans…</div>;
  }
  if (error) {
    return <div className="inst-loan-empty inst-loan-error">{error}</div>;
  }
  if (!loans.length) {
    return (
      <div className="inst-loan-empty">
        <strong>No active loans</strong>
        <span>This member has no installments scheduled yet.</span>
      </div>
    );
  }

  return (
    <div className="inst-loan-list" role="list">
      {loans.map((loan) => {
        const isActive = loan.id === selectedLoanId;
        const progress = loan.due > 0 ? Math.min(100, Math.round((loan.paid / loan.due) * 100)) : 0;
        const cardClass = `inst-loan-card${isActive ? ' is-selected' : ''}${loan.overdueCount > 0 ? ' has-overdue' : ''}`;
        return (
          <button
            key={loan.id}
            type="button"
            className={cardClass}
            onClick={() => onSelect && onSelect(loan)}
            role="listitem"
            aria-pressed={isActive}
          >
            <div className="inst-loan-card-head">
              <div className="inst-loan-card-title">
                <strong>{loan.code}</strong>
                <span>{loan.items.length} installment{loan.items.length === 1 ? '' : 's'}</span>
              </div>
              <div className="inst-loan-card-tags">
                {loan.overdueCount > 0 ? (
                  <span className="inst-tag inst-tag-danger">{loan.overdueCount} overdue</span>
                ) : null}
                {loan.pendingCount > 0 ? (
                  <span className="inst-tag inst-tag-warning">{loan.pendingCount} pending</span>
                ) : null}
                {loan.overdueCount === 0 && loan.pendingCount === 0 ? (
                  <span className="inst-tag inst-tag-success">All paid</span>
                ) : null}
              </div>
            </div>

            <div className="inst-loan-card-meter">
              <div className="inst-loan-card-meter-fill" style={{ width: `${progress}%` }} aria-hidden />
              <span className="inst-loan-card-meter-label">{progress}% collected</span>
            </div>

            <dl className="inst-loan-card-grid">
              <div>
                <dt>Total due</dt>
                <dd>{formatMoney(loan.due)}</dd>
              </div>
              <div>
                <dt>Paid</dt>
                <dd>{formatMoney(loan.paid)}</dd>
              </div>
              <div>
                <dt>Outstanding</dt>
                <dd className={loan.balance > 0 ? 'is-due' : 'is-clear'}>{formatMoney(loan.balance)}</dd>
              </div>
              <div>
                <dt>Next due</dt>
                <dd>{loan.next ? formatDateShort(loan.next.dueDate) : '—'}</dd>
              </div>
            </dl>

            <span className="inst-loan-card-cta" aria-hidden>
              View schedule <ChevronIcon />
            </span>
          </button>
        );
      })}
    </div>
  );
}

// Export helper so consumers can read the next installment from the same group.
export function summarizeLoansForMember(installments) {
  return summarizeLoans(installments);
}

export function balanceForLoan(installments) {
  const total = (installments || []).reduce((sum, i) => sum + (Number(i.paidAmount) || 0), 0);
  const due = (installments || []).reduce((sum, i) => sum + (Number(i.dueAmount) || 0), 0);
  return balanceOf({ dueAmount: due, paidAmount: total });
}
