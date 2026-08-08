import { useEffect } from 'react';
import { formatMoney } from '../../utils/formatMoney';
import { formatDateShort } from '../../utils/installment';

const ShieldIcon = () => (
  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
    <polyline points="9 12 11 14 15 10" />
  </svg>
);

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

export default function ConfirmPaymentDialog({
  open,
  installment,
  enteredAmount,
  willTotal,
  willRemain,
  paidDate,
  isFullPayment,
  busy,
  onConfirm,
  onCancel,
}) {
  useEffect(() => {
    if (!open) return undefined;
    function handleKey(event) {
      if (event.key === 'Escape' && !busy) onCancel();
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [open, busy, onCancel]);

  if (!open || !installment) return null;

  const installmentNo = installment.installmentNo ?? installment.installment_no ?? '?';
  const memberName = installment.member?.name || 'Unknown member';
  const loanCode = installment.loan?.code || `Loan #${installment.loanId}`;
  const statusAfter = isFullPayment ? 'PAID' : 'PARTIAL';

  return (
    <div className="inst-backdrop inst-backdrop-nested" role="alertdialog" aria-modal="true" aria-labelledby="confirm-payment-title">
      <div className="inst-modal inst-modal-confirm">
        <header className="inst-modal-head inst-modal-head-confirm">
          <div className="inst-confirm-icon" aria-hidden><ShieldIcon /></div>
          <div className="inst-confirm-title-wrap">
            <span className="inst-modal-eyebrow">Confirm collection</span>
            <h3 id="confirm-payment-title">Record this payment?</h3>
          </div>
          <button type="button" className="inst-modal-close" onClick={onCancel} aria-label="Close" disabled={busy}>
            <CloseIcon />
          </button>
        </header>

        <div className="inst-modal-body inst-confirm-body">
          <p className="inst-confirm-lead">
            You're about to record <strong>{formatMoney(enteredAmount)}</strong> for{' '}
            <strong>Installment #{installmentNo}</strong> of <strong>{loanCode}</strong> belonging to{' '}
            <strong>{memberName}</strong>.
          </p>

          <dl className="inst-confirm-grid">
            <div>
              <dt>Amount recorded</dt>
              <dd className="is-primary">{formatMoney(enteredAmount)}</dd>
            </div>
            <div>
              <dt>Payment date</dt>
              <dd>{formatDateShort(paidDate)}</dd>
            </div>
            <div>
              <dt>New total paid</dt>
              <dd>{formatMoney(willTotal)}</dd>
            </div>
            <div>
              <dt>Balance after</dt>
              <dd className={willRemain > 0 ? 'is-due' : 'is-clear'}>{formatMoney(willRemain)}</dd>
            </div>
            <div className="inst-confirm-grid-full">
              <dt>Status after</dt>
              <dd>
                <span className={`inst-pill inst-pill-${statusAfter.toLowerCase()}`}>{statusAfter}</span>
              </dd>
            </div>
          </dl>

          <div className="inst-confirm-foot">
            <AlertIcon />
            <span>This action is final. The installment record will be updated immediately.</span>
          </div>
        </div>

        <div className="inst-modal-actions">
          <button type="button" className="inst-btn-cancel" onClick={onCancel} disabled={busy}>Back</button>
          <button type="button" className="inst-btn-confirm" onClick={onConfirm} disabled={busy}>
            {busy ? 'Saving…' : `Confirm ${formatMoney(enteredAmount)}`}
          </button>
        </div>
      </div>
    </div>
  );
}

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="10" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);
