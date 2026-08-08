import { useEffect, useMemo, useState } from 'react';
import { formatMoney } from '../../utils/formatMoney';
import { initials, roleLabel } from '../../utils/roleLabel';
import { today as todayStr } from '../../utils/installment';
import ConfirmPaymentDialog from './ConfirmPaymentDialog';

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="10" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

const CheckIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="20 6 9 17 4 12" />
  </svg>
);

const CashIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="12" rx="2" />
    <circle cx="12" cy="12" r="2.5" />
    <path d="M6 12h.01M18 12h.01" />
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

const NoteIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M4 4h12l4 4v12a2 2 0 0 1-2 2H4Z" />
    <path d="M16 4v4h4" />
    <line x1="8" y1="12" x2="16" y2="12" />
    <line x1="8" y1="16" x2="14" y2="16" />
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

const HashIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="4" y1="9" x2="20" y2="9" />
    <line x1="4" y1="15" x2="20" y2="15" />
    <line x1="10" y1="3" x2="8" y2="21" />
    <line x1="16" y1="3" x2="14" y2="21" />
  </svg>
);

const AVATAR_GRADIENT = 'linear-gradient(135deg, #6366f1, #8b5cf6)';

export default function CollectPaymentModal({ open, installment, busy, error, collector, onSubmit, onClose }) {
  const [amount, setAmount] = useState('0');
  const [paidDate, setPaidDate] = useState(todayStr());
  const [notes, setNotes] = useState('');
  const [confirming, setConfirming] = useState(false);

  useEffect(() => {
    if (!installment) return;
    setAmount(String(Number(installment.balance) || 0));
    setPaidDate(installment.paidDate ? installment.paidDate.slice(0, 10) : todayStr());
    setNotes(installment.notes || '');
    setConfirming(false);
  }, [installment, open]);

  useEffect(() => {
    if (!open) return undefined;
    function handleKey(event) {
      if (event.key === 'Escape' && !busy && !confirming) onClose();
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [open, busy, confirming, onClose]);

  const due = Number(installment?.dueAmount) || 0;
  const paidSoFar = Number(installment?.paidAmount) || 0;
  const remaining = Math.max(0, due - paidSoFar);
  const progress = due > 0 ? Math.min(100, Math.round((paidSoFar / due) * 100)) : 0;

  const enteredAmount = useMemo(() => Number(amount) || 0, [amount]);
  const overLimit = enteredAmount > remaining + 0.001;
  const willTotal = Math.min(due, paidSoFar + enteredAmount);
  const willRemain = Math.max(0, remaining - enteredAmount);
  const willProgress = due > 0 ? Math.min(100, Math.round((willTotal / due) * 100)) : 0;
  const isFullPayment = enteredAmount >= remaining && remaining > 0;
  const isExactMatch = Math.abs(enteredAmount - remaining) < 0.001 && remaining > 0;
  const canSubmit = !busy && !overLimit && enteredAmount > 0;

  if (!open || !installment) return null;

  const collectorName = collector?.name || roleLabel(collector?.role) || 'User';
  const collectorUsername = collector?.username ? `(@${collector.username})` : '';
  const installmentNo = installment.installmentNo ?? installment.installment_no ?? '?';

  function handleConfirmRequest(event) {
    event.preventDefault();
    if (!canSubmit) return;
    setConfirming(true);
  }

  function handleConfirmAccept() {
    const numeric = Number(amount);
    const cumulativePaid = Math.min(due, paidSoFar + numeric);
    setConfirming(false);
    if (onSubmit) {
      onSubmit({
        paidAmount: cumulativePaid,
        paidDate: paidDate || todayStr(),
        notes: notes.trim() || null,
        collectedBy: collector?.id ?? null,
      });
    }
  }

  function handleConfirmCancel() {
    setConfirming(false);
  }

  function handlePayFull() { setAmount(remaining.toFixed(2)); }
  function handlePayHalf() { setAmount((remaining / 2).toFixed(2)); }
  function handleClearAmount() { setAmount('0'); }

  return (
    <>
      <div className="inst-backdrop" role="dialog" aria-modal="true" aria-labelledby="collect-payment-title">
        <div className="inst-modal inst-modal-fintech inst-modal-collect">
          <header className="inst-modal-head inst-modal-head-fintech is-collect">
            <div className="inst-modal-head-left">
              <div className="inst-modal-icon primary" aria-hidden><CashIcon /></div>
              <div className="inst-modal-title-wrap">
                <span className="inst-modal-eyebrow">Collect Daily Payment</span>
                <h3 id="collect-payment-title">Record installment collection</h3>
                <p>
                  {installment.member?.name || 'Unknown member'}
                  <span className="inst-modal-sep">·</span>
                  Loan {installment.loan?.code || `#${installment.loanId}`}
                  <span className="inst-modal-sep">·</span>
                  Installment <strong>#{installmentNo}</strong>
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
                {installment.status === 'paid' ? 'Settled' : remaining > 0 ? 'Pending collection' : 'Ready to settle'}
              </span>
            </div>
            <div className="inst-balance-amount">
              <span className="inst-balance-currency" aria-hidden>৳</span>
              <span className="inst-balance-num">{remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
            </div>
            <div className="inst-balance-meta">
              <span><strong>{formatMoney(due)}</strong> due</span>
              <span className="inst-balance-dot" aria-hidden>•</span>
              <span><strong>{formatMoney(paidSoFar)}</strong> already paid</span>
              <span className="inst-balance-dot" aria-hidden>•</span>
              <span>Installment <strong>#{installmentNo}</strong></span>
            </div>
            <div className="inst-meter-bar inst-meter-bar-lg">
              <span style={{ width: `${progress}%` }} />
            </div>
            <div className="inst-meter-progress-row">
              <span>Settlement progress</span>
              <span><strong>{progress}%</strong></span>
            </div>
          </div>

          <form onSubmit={handleConfirmRequest} className="inst-modal-body">
            <div className="inst-form-section">
              <div className="inst-form-section-title">Schedule reference</div>
              <div className="inst-context-row inst-context-row-multi">
                <div className="inst-context-item">
                  <span className="inst-context-icon" aria-hidden><UserIcon /></span>
                  <div>
                    <span className="inst-context-label">Member</span>
                    <span className="inst-context-value">
                      {installment.member?.name || '—'}
                      {installment.member?.code ? <span className="inst-context-sub"> ({installment.member.code})</span> : null}
                    </span>
                  </div>
                </div>
                <div className="inst-context-item">
                  <span className="inst-context-icon" aria-hidden><LoanIcon /></span>
                  <div>
                    <span className="inst-context-label">Loan</span>
                    <span className="inst-context-value">
                      {installment.loan?.code || `#${installment.loanId}`}
                      <span className="inst-context-sub"> Loan #{installment.loanId}</span>
                    </span>
                  </div>
                </div>
                <div className="inst-context-item">
                  <span className="inst-context-icon" aria-hidden><HashIcon /></span>
                  <div>
                    <span className="inst-context-label">Installment #</span>
                    <span className="inst-context-value">#{installmentNo}</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="inst-form-section">
              <div className="inst-form-section-title">Payment breakdown</div>
              <div className="inst-context-row inst-context-row-multi">
                <div className="inst-context-item">
                  <span className="inst-context-icon" aria-hidden><CashIcon /></span>
                  <div>
                    <span className="inst-context-label">Due amount</span>
                    <span className="inst-context-value inst-amount-strong">{formatMoney(due)}</span>
                  </div>
                </div>
                <div className="inst-context-item">
                  <span className="inst-context-icon" aria-hidden><CheckIcon /></span>
                  <div>
                    <span className="inst-context-label">Already paid</span>
                    <span className="inst-context-value inst-amount-strong">{formatMoney(paidSoFar)}</span>
                  </div>
                </div>
                <div className="inst-context-item">
                  <span className="inst-context-icon" aria-hidden><CalIcon /></span>
                  <div>
                    <span className="inst-context-label">Remaining balance</span>
                    <span className={`inst-context-value inst-amount-strong ${remaining > 0 ? 'warn' : 'ok'}`}>{formatMoney(remaining)}</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="inst-form-section">
              <div className="inst-form-section-title">Amount collected today</div>
              <div className="inst-amount-input-wrap">
                <label className="inst-field">
                  <div className={`inst-currency-input inst-currency-input-lg ${overLimit ? 'is-error' : ''}`}>
                    <span className="inst-currency-prefix" aria-hidden>৳</span>
                    <input
                      type="number"
                      min="0"
                      max={remaining}
                      step="0.01"
                      value={amount}
                      onChange={(event) => setAmount(event.target.value)}
                      required
                      placeholder="0.00"
                      autoFocus
                    />
                  </div>
                  {overLimit ? (
                    <span className="inst-field-hint inst-field-hint-error">
                      <AlertIcon /> Amount cannot exceed remaining balance ({formatMoney(remaining)}).
                    </span>
                  ) : (
                    <span className="inst-field-hint">Enter the cash received from the member for this installment.</span>
                  )}
                  <div className="inst-quick-actions">
                    <button type="button" className="inst-quick-btn" onClick={handlePayFull} disabled={busy || remaining <= 0}>
                      <CheckIcon /> Pay full ({formatMoney(remaining)})
                    </button>
                    <button type="button" className="inst-quick-btn" onClick={handlePayHalf} disabled={busy || remaining <= 0}>
                      50% ({formatMoney(remaining / 2)})
                    </button>
                    <button type="button" className="inst-quick-btn ghost" onClick={handleClearAmount} disabled={busy}>
                      Clear
                    </button>
                  </div>
                </label>
              </div>
            </div>

            <div className="inst-form-section">
              <div className="inst-form-section-title">Date & note</div>
              <div className="inst-form-row inst-form-row-split">
                <label className="inst-field">
                  <span className="inst-field-label"><CalIcon /> Payment date</span>
                  <input
                    type="date"
                    value={paidDate}
                    onChange={(event) => setPaidDate(event.target.value)}
                    required
                  />
                  <span className="inst-field-hint">When the payment was received.</span>
                </label>
              </div>
              <label className="inst-field">
                <span className="inst-field-label"><NoteIcon /> Note</span>
                <textarea
                  className="inst-textarea"
                  rows={3}
                  value={notes}
                  onChange={(event) => setNotes(event.target.value)}
                  placeholder="Optional note about this collection (e.g. cash collected at branch, partial because member short on funds)"
                  maxLength={1000}
                />
                <span className="inst-field-hint">Up to 1000 characters. Saved with this installment.</span>
              </label>
            </div>

            <div className="inst-form-section">
              <div className="inst-form-section-title">Collector</div>
              <div className="inst-collector-card">
                <div className="inst-collector-avatar" style={{ background: AVATAR_GRADIENT }} aria-hidden>{initials(collectorName)}</div>
                <div className="inst-collector-info">
                  <span className="inst-collector-name">{collectorName}</span>
                  {collectorUsername ? <span className="inst-collector-username">{collectorUsername}</span> : null}
                </div>
                <span className="inst-collector-role">
                  {collector?.role === 'admin' || collector?.role === 'branch_manager' ? 'Authorized' : roleLabel(collector?.role)}
                </span>
              </div>
            </div>

            {enteredAmount > 0 && !overLimit ? (
              <div className="inst-summary-card inst-summary-card-live">
                <div className="inst-summary-row">
                  <span>Current paid</span>
                  <strong>{formatMoney(paidSoFar)}</strong>
                </div>
                <div className="inst-summary-row">
                  <span>Amount collected today</span>
                  <strong className="ok">+ {formatMoney(enteredAmount)}</strong>
                </div>
                <div className="inst-summary-row inst-summary-divider">
                  <span>Total after payment</span>
                  <strong>{formatMoney(willTotal)}</strong>
                </div>
                <div className="inst-summary-row">
                  <span>Remaining balance</span>
                  <strong className={willRemain > 0 ? 'warn' : 'ok'}>{formatMoney(willRemain)}</strong>
                </div>
                <div className="inst-meter-bar inst-meter-bar-sm">
                  <span style={{ width: `${willProgress}%` }} />
                </div>
                <div className="inst-summary-foot">
                  {isExactMatch || isFullPayment ? (
                    <span className="inst-badge ok">Full payment · installment will be settled</span>
                  ) : (
                    <span className="inst-badge partial">Partial payment · installment remains pending</span>
                  )}
                </div>
              </div>
            ) : null}

            {error ? (
              <div className="inst-modal-error" role="alert">
                <AlertIcon />
                <span>{error}</span>
              </div>
            ) : null}

            <div className="inst-modal-actions">
              <button type="button" className="inst-btn-cancel" onClick={onClose} disabled={busy}>Cancel</button>
              <button type="submit" className="inst-btn-confirm inst-btn-confirm-lg" disabled={!canSubmit}>
                <CheckIcon />
                {busy
                  ? 'Saving…'
                  : isExactMatch || isFullPayment
                    ? `Settle ${formatMoney(enteredAmount)}`
                    : `Record ${formatMoney(enteredAmount)}`}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmPaymentDialog
        open={confirming}
        installment={installment}
        enteredAmount={enteredAmount}
        willTotal={willTotal}
        willRemain={willRemain}
        paidDate={paidDate}
        isFullPayment={isFullPayment || isExactMatch}
        busy={busy}
        onConfirm={handleConfirmAccept}
        onCancel={handleConfirmCancel}
      />
    </>
  );
}
