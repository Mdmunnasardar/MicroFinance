import { useEffect, useMemo, useState } from 'react';

const today = () => new Date().toISOString().slice(0, 10);

const formatMoney = (value) => {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const PayIcon = () => (
  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="12" rx="2" />
    <circle cx="12" cy="12" r="2" />
    <path d="M6 12h.01M18 12h.01" />
    <path d="M6 10v4M18 10v4" />
  </svg>
);

const EditIcon = () => (
  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M12 20h9" />
    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z" />
  </svg>
);

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="10" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

const UserIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
    <circle cx="12" cy="7" r="4" />
  </svg>
);

const LoanIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="5" width="20" height="14" rx="2" />
    <line x1="2" y1="10" x2="22" y2="10" />
  </svg>
);

const CalIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <line x1="16" y1="2" x2="16" y2="6" />
    <line x1="8" y1="2" x2="8" y2="6" />
    <line x1="3" y1="10" x2="21" y2="10" />
  </svg>
);

const CheckIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="20 6 9 17 4 12" />
  </svg>
);

export default function InstallmentForm({ open, mode, installment, busy, error, onSubmit, onClose }) {
  const [amount, setAmount] = useState('0');
  const [paidDate, setPaidDate] = useState(today());

  useEffect(() => {
    if (!installment) return;
    setAmount(String(mode === 'pay' ? Number(installment.balance) || 0 : Number(installment.paidAmount) || 0));
    setPaidDate(installment.paidDate ? installment.paidDate.slice(0, 10) : today());
  }, [installment, mode, open]);

  useEffect(() => {
    if (!open) return undefined;
    const handleKey = (event) => {
      if (event.key === 'Escape' && !busy) onClose();
    };
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [open, busy, onClose]);

  const isPay = mode === 'pay';
  const due = Number(installment?.dueAmount) || 0;
  const paidSoFar = Number(installment?.paidAmount) || 0;
  const remaining = Math.max(0, due - paidSoFar);
  const progress = due > 0 ? Math.min(100, Math.round((paidSoFar / due) * 100)) : 0;
  const enteredAmount = useMemo(() => Number(amount) || 0, [amount]);
  const willRemain = Math.max(0, remaining - enteredAmount);
  const willTotal = Math.min(due, paidSoFar + enteredAmount);
  const willProgress = due > 0 ? Math.min(100, Math.round((willTotal / due) * 100)) : 0;
  const isFullPayment = enteredAmount >= remaining && remaining > 0;

  if (!open || !installment) return null;

  const handleSubmit = (event) => {
    event.preventDefault();
    const numeric = Number(amount);
    if (Number.isNaN(numeric) || numeric < 0) return;
    onSubmit({
      paidAmount: numeric,
      paidDate: paidDate || today(),
    });
  };

  const handlePayFull = () => {
    setAmount(String(remaining.toFixed(2)));
  };

  const handleClearAmount = () => {
    setAmount('0');
  };

  return (
    <div className="inst-backdrop" role="dialog" aria-modal="true" aria-labelledby="installment-form-title">
      <div className="inst-modal inst-modal-fintech">
        <header className={`inst-modal-head inst-modal-head-fintech ${isPay ? 'is-pay' : 'is-edit'}`}>
          <div className="inst-modal-head-left">
            <div className={`inst-modal-icon ${isPay ? 'primary' : 'neutral'}`} aria-hidden>
              {isPay ? <PayIcon /> : <EditIcon />}
            </div>
            <div className="inst-modal-title-wrap">
              <span className="inst-modal-eyebrow">
                {isPay ? 'Collect Payment' : 'Edit Payment'}
              </span>
              <h3 id="installment-form-title">
                {isPay ? 'Record installment payment' : 'Edit installment payment'}
              </h3>
              <p>
                {installment.member?.name || 'Unknown member'}
                <span className="inst-modal-sep">·</span>
                Loan {installment.loan?.code || `#${installment.loanId}`}
                <span className="inst-modal-sep">·</span>
                Installment <strong>#{installment.installmentNo}</strong>
              </p>
            </div>
          </div>
          <button type="button" className="inst-modal-close" onClick={onClose} aria-label="Close" disabled={busy}>
            <CloseIcon />
          </button>
        </header>

        <div className="inst-balance-card">
          <div className="inst-balance-card-top">
            <span className="inst-balance-label">Outstanding balance</span>
            <span className={`inst-balance-pill ${remaining > 0 ? 'warn' : 'ok'}`}>
              {installment.status === 'paid' ? 'Settled' : remaining > 0 ? 'Pending' : 'Ready to settle'}
            </span>
          </div>
          <div className="inst-balance-amount">
            <span className="inst-balance-currency" aria-hidden>৳</span>
            <span className="inst-balance-num">{remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
          </div>
          <div className="inst-balance-meta">
            <span><strong>{formatMoney(due)}</strong> due</span>
            <span className="inst-balance-dot" aria-hidden>•</span>
            <span><strong>{formatMoney(paidSoFar)}</strong> paid</span>
            <span className="inst-balance-dot" aria-hidden>•</span>
            <span>Installment <strong>#{installment.installmentNo}</strong></span>
          </div>
          <div className="inst-meter-bar inst-meter-bar-lg">
            <span style={{ width: `${progress}%` }} />
          </div>
          <div className="inst-meter-progress-row">
            <span>Settlement progress</span>
            <span><strong>{progress}%</strong></span>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="inst-modal-body">
          <div className="inst-form-section">
            <div className="inst-form-section-title">Payment details</div>

            <div className="inst-amount-input-wrap">
              <label className="inst-field">
                <span className="inst-field-label">Amount to collect</span>
                <div className="inst-currency-input inst-currency-input-lg">
                  <span className="inst-currency-prefix" aria-hidden>৳</span>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={amount}
                    onChange={(event) => setAmount(event.target.value)}
                    required
                    placeholder="0.00"
                    autoFocus={isPay}
                  />
                </div>
                <div className="inst-quick-actions">
                  <button
                    type="button"
                    className="inst-quick-btn"
                    onClick={handlePayFull}
                    disabled={busy || remaining <= 0}
                  >
                    <CheckIcon /> Pay full ({formatMoney(remaining)})
                  </button>
                  <button
                    type="button"
                    className="inst-quick-btn ghost"
                    onClick={handleClearAmount}
                    disabled={busy}
                  >
                    Clear
                  </button>
                </div>
              </label>
            </div>

            <div className="inst-form-row">
              <label className="inst-field">
                <span className="inst-field-label">
                  <CalIcon /> Payment date
                </span>
                <input
                  type="date"
                  value={paidDate}
                  onChange={(event) => setPaidDate(event.target.value)}
                  required
                />
                <span className="inst-field-hint">When the payment was received.</span>
              </label>
            </div>
          </div>

          {isPay && enteredAmount > 0 ? (
            <div className="inst-summary-card">
              <div className="inst-summary-row">
                <span>Current paid</span>
                <strong>{formatMoney(paidSoFar)}</strong>
              </div>
              <div className="inst-summary-row">
                <span>This payment</span>
                <strong className="ok">+ {formatMoney(enteredAmount)}</strong>
              </div>
              <div className="inst-summary-row inst-summary-divider">
                <span>Total after payment</span>
                <strong>{formatMoney(willTotal)}</strong>
              </div>
              <div className="inst-summary-row">
                <span>Remaining balance</span>
                <strong className={willRemain > 0 ? 'warn' : 'ok'}>
                  {formatMoney(willRemain)}
                </strong>
              </div>
              <div className="inst-meter-bar inst-meter-bar-sm">
                <span style={{ width: `${willProgress}%` }} />
              </div>
              <div className="inst-summary-foot">
                {isFullPayment ? (
                  <span className="inst-badge ok">Full payment · installment will be settled</span>
                ) : (
                  <span className="inst-badge partial">Partial payment · installment remains pending</span>
                )}
              </div>
            </div>
          ) : null}

          <div className="inst-context-row">
            <div className="inst-context-item">
              <span className="inst-context-icon" aria-hidden><UserIcon /></span>
              <div>
                <span className="inst-context-label">Member</span>
                <span className="inst-context-value">{installment.member?.name || '—'}</span>
              </div>
            </div>
            <div className="inst-context-item">
              <span className="inst-context-icon" aria-hidden><LoanIcon /></span>
              <div>
                <span className="inst-context-label">Loan</span>
                <span className="inst-context-value">
                  {installment.loan?.code || `#${installment.loanId}`}
                </span>
              </div>
            </div>
          </div>

          {error ? (
            <div className="inst-modal-error" role="alert">
              <AlertIcon />
              <span>{error}</span>
            </div>
          ) : null}

          <div className="inst-modal-actions">
            <button type="button" className="inst-btn-cancel" onClick={onClose} disabled={busy}>Cancel</button>
            <button type="submit" className="inst-btn-confirm inst-btn-confirm-lg" disabled={busy}>
              {busy ? 'Saving…' : isPay ? `Record ${formatMoney(enteredAmount)}` : 'Save changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}