const STATUS_LABEL = {
  paid: 'Paid',
  pending: 'Pending',
  partial: 'Partial',
  overdue: 'Overdue',
};

const formatMoney = (value) => {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (value) => {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const relativeDate = (value) => {
  if (!value) return null;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;
  const diffMs = Date.now() - date.getTime();
  const days = Math.round(diffMs / (1000 * 60 * 60 * 24));
  if (days === 0) return 'Today';
  if (days === 1) return 'Yesterday';
  if (days > 1 && days < 30) return `${days}d ago`;
  if (days < 0 && days > -30) return `In ${Math.abs(days)}d`;
  return null;
};

const initials = (value) => {
  if (!value) return '?';
  const parts = String(value).trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return ((parts[0][0] || '') + (parts[parts.length - 1][0] || '')).toUpperCase();
};

const AVATAR_GRADIENTS = [
  'linear-gradient(135deg, #6366f1, #8b5cf6)',
  'linear-gradient(135deg, #06b6d4, #3b82f6)',
  'linear-gradient(135deg, #f59e0b, #ef4444)',
  'linear-gradient(135deg, #10b981, #14b8a6)',
  'linear-gradient(135deg, #ec4899, #8b5cf6)',
  'linear-gradient(135deg, #0ea5e9, #6366f1)',
];

const avatarFor = (value) => {
  if (!value) return AVATAR_GRADIENTS[0];
  let hash = 0;
  for (let i = 0; i < value.length; i += 1) {
    hash = (hash * 31 + value.charCodeAt(i)) >>> 0;
  }
  return AVATAR_GRADIENTS[hash % AVATAR_GRADIENTS.length];
};

const PayIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="12" rx="2" />
    <circle cx="12" cy="12" r="2" />
    <path d="M6 12h.01M18 12h.01" />
  </svg>
);

const EditIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M12 20h9" />
    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z" />
  </svg>
);

const TrashIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="3 6 5 6 21 6" />
    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
    <line x1="10" y1="11" x2="10" y2="17" />
    <line x1="14" y1="11" x2="14" y2="17" />
  </svg>
);

const EmptyIcon = () => (
  <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <path d="M16 2v4M8 2v4M3 10h18" />
    <path d="M9 14h6" />
  </svg>
);

function StatusBadge({ status }) {
  const label = STATUS_LABEL[status] || status;
  return (
    <span className={`inst-badge ${status}`}>
      <span className="dot" aria-hidden />
      {label}
    </span>
  );
}

function ProgressBar({ value, warn }) {
  const pct = Math.max(0, Math.min(100, value || 0));
  return (
    <div className={`inst-money-bar${warn ? ' warn' : ''}`}>
      <span style={{ width: `${pct}%` }} />
    </div>
  );
}

const InfoIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="9" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

export default function InstallmentsTable({ items, onView, onCollect, onPay, onEdit, onDelete }) {
  if (!items?.length) {
    return (
      <div className="inst-state empty">
        <div className="empty-art" aria-hidden><EmptyIcon /></div>
        <h4>No installments match your view</h4>
        <p>Try adjusting your search or status filter, or check back once new installments are scheduled.</p>
        <span className="empty-tip">Tip: clear filters to see all installments</span>
      </div>
    );
  }

  return (
    <div className="inst-table-wrap">
      <table className="inst-table">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Member</th>
            <th scope="col">Loan</th>
            <th scope="col" className="num">Due amount</th>
            <th scope="col" className="num">Paid</th>
            <th scope="col" className="num">Balance</th>
            <th scope="col">Paid date</th>
            <th scope="col">Status</th>
            <th scope="col" className="actions"><span className="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => {
            const paid = Number(item.paidAmount) || 0;
            const due = Number(item.dueAmount) || 0;
            const balance = Number(item.balance) || 0;
            const isPaid = item.status === 'paid';
            const progress = due > 0 ? Math.min(100, Math.round((paid / due) * 100)) : 0;
            const memberName = item.member?.name || 'Unknown member';
            const memberCode = item.member?.code || '—';
            const loanCode = item.loan?.code || `Loan #${item.loanId}`;
            const rel = relativeDate(item.paidDate);
            const collectHandler = typeof onCollect === 'function' ? onCollect : onPay;

            return (
              <tr key={item.id}>
                <td>
                  <span className="inst-no">#{item.installmentNo}</span>
                </td>
                <td>
                  <div className="inst-user">
                    <div className="inst-avatar" style={{ background: avatarFor(memberName) }}>
                      {initials(memberName)}
                    </div>
                    <div className="inst-user-text">
                      <span className="name">{memberName}</span>
                      <span className="code">{memberCode}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div className="inst-loan">
                    <span className="code">{loanCode}</span>
                    <span className="id">Loan #{item.loanId}</span>
                  </div>
                </td>
                <td className="inst-num-cell">
                  <span className="inst-money">{formatMoney(due)}</span>
                </td>
                <td className="inst-num-cell">
                  <div className="inst-money bar">
                    <span className="amt">{formatMoney(paid)}</span>
                    <ProgressBar value={progress} warn={progress < 50 && paid > 0} />
                  </div>
                </td>
                <td className="inst-num-cell">
                  <span className={`inst-money ${balance > 0 ? 'has-balance' : ''}`} style={balance > 0 ? { color: '#b45309', fontWeight: 700 } : { color: '#047857', fontWeight: 700 }}>
                    {formatMoney(balance)}
                  </span>
                </td>
                <td>
                  <div className="inst-date">
                    <span className="date">{formatDate(item.paidDate)}</span>
                    {rel ? <span className="rel">{rel}</span> : null}
                  </div>
                </td>
                <td>
                  <StatusBadge status={item.status} />
                </td>
                <td className="inst-actions-cell">
                  <div className="inst-row-actions">
                    {typeof onView === 'function' ? (
                      <button
                        type="button"
                        className="inst-icon-btn"
                        onClick={() => onView(item)}
                        title="View details"
                        aria-label="View installment details"
                      >
                        <InfoIcon />
                      </button>
                    ) : null}
                    <button
                      type="button"
                      className="inst-collect-btn"
                      onClick={() => collectHandler && collectHandler(item)}
                      disabled={isPaid}
                      title={isPaid ? 'Already paid' : 'Collect payment'}
                      aria-label={isPaid ? 'Already paid — fully settled' : 'Collect daily payment'}
                    >
                      <PayIcon /> Collect Payment
                    </button>
                    <button
                      type="button"
                      className="inst-icon-btn"
                      onClick={() => onEdit(item)}
                      disabled={isPaid}
                      title={isPaid ? 'Already paid' : 'Edit installment'}
                      aria-label={isPaid ? 'Already paid' : 'Edit installment'}
                    >
                      <EditIcon />
                    </button>
                    <button
                      type="button"
                      className="inst-icon-btn danger"
                      onClick={() => onDelete(item)}
                      disabled={paid > 0}
                      title={paid > 0 ? 'Paid installments cannot be deleted' : 'Delete installment'}
                      aria-label={paid > 0 ? 'Paid installments cannot be deleted' : 'Delete installment'}
                    >
                      <TrashIcon />
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}