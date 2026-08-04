import { useEffect, useState } from 'react';

const today = () => new Date().toISOString().slice(0, 10);

export default function InstallmentForm({ open, mode, installment, busy, error, onSubmit, onClose }) {
  const [amount, setAmount] = useState('0');
  const [paidDate, setPaidDate] = useState(today());

  useEffect(() => {
    if (!installment) return;
    setAmount(String(mode === 'pay' ? Number(installment.balance) || 0 : Number(installment.paidAmount) || 0));
    setPaidDate(installment.paidDate ? installment.paidDate.slice(0, 10) : today());
  }, [installment, mode, open]);

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

  return (
    <div className="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="installment-form-title">
      <div className="modal-card">
        <header className="modal-header">
          <h3 id="installment-form-title">{mode === 'pay' ? 'Pay installment' : 'Edit installment'}</h3>
          <button type="button" className="modal-close" onClick={onClose} aria-label="Close">×</button>
        </header>
        <div className="modal-subtitle">
          {installment.member?.name} · Loan {installment.loan?.code || `#${installment.loanId}`} · Installment #{installment.installmentNo}
        </div>
        <form onSubmit={handleSubmit} className="modal-body">
          <div className="field">
            <label htmlFor="installment-amount">Amount</label>
            <input
              id="installment-amount"
              type="number"
              min="0"
              step="0.01"
              max={installment.dueAmount}
              value={amount}
              onChange={(event) => setAmount(event.target.value)}
              required
            />
            <small className="meta">Due amount: {Number(installment.dueAmount).toLocaleString()}</small>
          </div>
          <div className="field">
            <label htmlFor="installment-date">Paid date</label>
            <input
              id="installment-date"
              type="date"
              value={paidDate}
              onChange={(event) => setPaidDate(event.target.value)}
              required
            />
          </div>
          {error ? <div className="error-banner" style={{ marginTop: 0 }}>{error}</div> : null}
          <div className="modal-actions">
            <button type="button" className="btn-quick" onClick={onClose} disabled={busy}>Cancel</button>
            <button type="submit" className="btn-primary" disabled={busy}>
              {busy ? 'Saving…' : mode === 'pay' ? 'Mark as paid' : 'Save changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}