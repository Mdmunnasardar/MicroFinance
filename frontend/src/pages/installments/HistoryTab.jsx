import { useMemo } from 'react';
import { deriveStatus, formatDateShort } from '../../utils/installment';
import { formatMoney } from '../../utils/formatMoney';
import { initials } from '../../utils/roleLabel';

const FilterIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
  </svg>
);

export default function HistoryTab({ items, loading, error, onView }) {
  const paid = useMemo(() => {
    const list = (items || []).filter((i) => deriveStatus(i) === 'paid' || deriveStatus(i) === 'partial');
    return list.sort((a, b) => {
      const ad = a.paidDate ? new Date(a.paidDate).getTime() : 0;
      const bd = b.paidDate ? new Date(b.paidDate).getTime() : 0;
      return bd - ad;
    });
  }, [items]);

  const totals = useMemo(() => {
    const collected = paid.reduce((s, i) => s + (Number(i.paidAmount) || 0), 0);
    return { count: paid.length, collected };
  }, [paid]);

  return (
    <div className="inst-history-tab">
      <header className="inst-history-head">
        <div>
          <h3>Payment history</h3>
          <span>Settled and partial installments, most recent first.</span>
        </div>
        <div className="inst-history-stats">
          <span><strong>{totals.count}</strong> installments</span>
          <span><strong>{formatMoney(totals.collected)}</strong> collected</span>
        </div>
      </header>

      {loading ? (
        <div className="inst-history-empty">Loading history…</div>
      ) : error ? (
        <div className="inst-history-empty inst-history-error">{error}</div>
      ) : paid.length === 0 ? (
        <div className="inst-history-empty">
          <strong>No payments recorded yet</strong>
          <span>Once a payment is collected, it will appear here in chronological order.</span>
        </div>
      ) : (
        <ul className="inst-history-list">
          {paid.map((item) => {
            const status = deriveStatus(item);
            return (
              <li key={item.id} className={`inst-history-row is-${status}`}>
                <div className="inst-avatar inst-avatar-md">{initials(item.member?.name || 'M')}</div>
                <div className="inst-history-body">
                  <div className="inst-history-row-head">
                    <strong>{item.member?.name || 'Unknown member'}</strong>
                    <span>{item.loan?.code || `Loan #${item.loanId}`} · Installment #{item.installmentNo}</span>
                  </div>
                  <div className="inst-history-row-meta">
                    <span>{item.member?.code ? `ID ${item.member.code}` : ''}</span>
                    <span>{formatDateShort(item.paidDate) || '—'}</span>
                  </div>
                </div>
                <div className="inst-history-amount">
                  <strong>{formatMoney(Number(item.paidAmount) || 0)}</strong>
                  <span className={`inst-pill inst-pill-${status}`}>{status === 'paid' ? 'Paid' : 'Partial'}</span>
                </div>
                {onView ? (
                  <button type="button" className="inst-btn inst-btn-ghost inst-btn-sm" onClick={() => onView(item)}>
                    <FilterIcon /> Details
                  </button>
                ) : null}
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
