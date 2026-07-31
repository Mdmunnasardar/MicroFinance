import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { useLoan, useRecordPayment } from '../hooks/useLoans';

export default function PaymentPage() {
  const { id } = useParams();
  const loanId = Number(id);
  const navigate = useNavigate();
  const loan = useLoan(loanId);
  const record = useRecordPayment(loanId);

  const [amount, setAmount] = useState<number>(loan.data?.installmentAmount ?? 0);
  const [note, setNote] = useState('');

  if (!loan.data) return <p>Loading…</p>;

  return (
    <form className="card max-w-md space-y-4" onSubmit={async (e) => {
      e.preventDefault();
      try {
        await record.mutateAsync({ amount, note });
        toast.success('Payment recorded');
        navigate(`/loans-app/${loanId}`);
      } catch (err) {
        toast.error((err as Error).message);
      }
    }}>
      <h1 className="text-xl font-semibold">Record Payment · Loan {loan.data.code}</h1>
      <div>
        <label className="label">Amount (₹)</label>
        <input className="input" type="number" min="1" value={amount} onChange={(e) => setAmount(Number(e.target.value))} />
      </div>
      <div>
        <label className="label">Note</label>
        <textarea className="input" rows={2} value={note} onChange={(e) => setNote(e.target.value)} />
      </div>
      <button type="submit" className="btn-primary" disabled={record.isPending}>{record.isPending ? 'Saving…' : 'Save Payment'}</button>
    </form>
  );
}