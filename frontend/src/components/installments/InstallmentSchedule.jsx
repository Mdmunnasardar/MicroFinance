import { useMemo } from 'react';
import { deriveStatus, balanceOf, statusLabel, formatDateShort, isToday } from '../../utils/installment';
import { formatMoney } from '../../utils/formatMoney';

const CollectIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="14" rx="2" />
    <path d="M2 10h20" />
    <path d="M6 14h4" />
  </svg>
);

const ViewIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
    <circle cx="12" cy="12" r="3" />
  </svg>
);

function installmentNo(item, index) {
  return item.installmentNo ?? item.installment_no ?? item.number ?? index + 1;
}

export default function InstallmentSchedule({
  items,
  loading,
  error,
  selectedId,
  onSelect,
  onCollect,
  onView,
  canCollect = true,
  emptyMessage = 'No installments scheduled yet.',
}) {
  const sorted = useMemo(() => {
    const copy = Array.isArray(items) ? [...items] : [];
    copy.sort((a, b) => {
      const ad = a.dueDate ? new Date(a.dueDate).getTime() : Infinity;
      const bd = b.dueDate ? new Date(b.dueDate).getTime() : Infinity;
      return ad - bd;
    });
    return copy;
  }, [items]);

  const nextIndex = useMemo(() => {
    if (!sorted.length) return -1;
    const idx = sorted.findIndex((i) => deriveStatus(i) !== 'paid');
    return idx;
  }, [sorted]);

  if (loading) {
    return <div className="inst-schedule-empty">Loading schedule…</div>;
  }
  if (error) {
    return <div className="inst-schedule-empty inst-schedule-error">{error}</div>;
  }
  if (!sorted.length) {
    return <div className="inst-schedule-empty">{emptyMessage}</div>;
  }

  return (
    <ol className="inst-schedule" role="list">
      {sorted.map((item, index) => {
        const status = deriveStatus(item);
        const due = Number(item.dueAmount) || 0;
        const paid = Number(item.paidAmount) || 0;
        const balance = balanceOf(item);
        const progress = due > 0 ? Math.min(100, Math.round((paid / due) * 100)) : 0;
        const dueLabel = formatDateShort(item.dueDate);
        const dueIsToday = isToday(item.dueDate);
        const isNext = index === nextIndex;
        const isSelected = item.id === selectedId;
        const className = [
          'inst-schedule-row',
          `is-${status}`,
          isNext ? 'is-next' : '',
          isSelected ? 'is-selected' : '',
          dueIsToday ? 'is-today' : '',
        ].filter(Boolean).join(' ');

        return (
          <li key={item.id ?? `${item.loanId}-${index}`} className={className}>
            <div className="inst-schedule-no" aria-hidden>
              <span>#{installmentNo(item, index)}</span>
            </div>
            <div className="inst-schedule-body">
              <div className="inst-schedule-head">
                <div className="inst-schedule-title">
                  <strong>Installment #{installmentNo(item, index)}</strong>
                  {isNext ? <span className="inst-tag inst-tag-info">Next</span> : null}
                  {dueIsToday ? <span className="inst-tag inst-tag-warning">Due today</span> : null}
                </div>
                <span className={`inst-pill inst-pill-${status}`}>{statusLabel(status)}</span>
              </div>

              <dl className="inst-schedule-meta">
                <div>
                  <dt>Due</dt>
                  <dd>{dueLabel}</dd>
                </div>
                <div>
                  <dt>Amount</dt>
                  <dd>{formatMoney(due)}</dd>
                </div>
                <div>
                  <dt>Paid</dt>
                  <dd>{formatMoney(paid)}</dd>
                </div>
                <div>
                  <dt>Balance</dt>
                  <dd className={balance > 0 ? 'is-due' : 'is-clear'}>{formatMoney(balance)}</dd>
                </div>
              </dl>

              <div className="inst-schedule-meter" aria-label={`Progress ${progress}%`}>
                <div className="inst-schedule-meter-fill" style={{ width: `${progress}%` }} aria-hidden />
              </div>

              <div className="inst-schedule-actions">
                <button type="button" className="inst-btn inst-btn-ghost inst-btn-sm" onClick={() => onSelect && onSelect(item)} aria-pressed={isSelected}>
                  {isSelected ? 'Selected' : 'Inspect'}
                </button>
                {onView ? (
                  <button type="button" className="inst-btn inst-btn-ghost inst-btn-sm" onClick={() => onView(item)}>
                    <ViewIcon /> View
                  </button>
                ) : null}
                {canCollect && status !== 'paid' ? (
                  <button type="button" className="inst-btn inst-btn-primary inst-btn-sm" onClick={() => onCollect && onCollect(item)}>
                    <CollectIcon /> Collect
                  </button>
                ) : null}
              </div>
            </div>
          </li>
        );
      })}
    </ol>
  );
}