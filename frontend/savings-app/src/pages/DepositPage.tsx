import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { useDeposit, useSavings } from '../hooks/useSavings';

export default function DepositPage() {
  const { memberId } = useParams();
  const id = Number(memberId);
  const navigate = useNavigate();
  const account = useSavings(id);
  const deposit = useDeposit(id);
  const [amount, setAmount] = useState(0);
  const [note, setNote] = useState('');

  return (
    <form className="card max-w-md space-y-4" onSubmit={async (e) => {
      e.preventDefault();
      try { await deposit.mutateAsync({ amount, note }); toast.success('Deposit recorded'); navigate(`/savings-app/${id}`); }
      catch (err) { toast.error((err as Error).message); }
    }}>
      <h1 className="text-xl font-semibold">Deposit · {account.data?.memberName ?? `#${id}`}</h1>
      <div><label className="label">Amount (₹)</label><input className="input" type="number" min="1" value={amount} onChange={(e) => setAmount(Number(e.target.value))} /></div>
      <div><label className="label">Note</label><textarea className="input" rows={2} value={note} onChange={(e) => setNote(e.target.value)} /></div>
      <button type="submit" disabled={deposit.isPending} className="btn-success">{deposit.isPending ? 'Saving…' : 'Confirm Deposit'}</button>
    </form>
  );
}