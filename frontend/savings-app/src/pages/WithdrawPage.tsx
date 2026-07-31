import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { useSavings, useWithdraw } from '../hooks/useSavings';

export default function WithdrawPage() {
  const { memberId } = useParams();
  const id = Number(memberId);
  const navigate = useNavigate();
  const account = useSavings(id);
  const withdraw = useWithdraw(id);
  const [amount, setAmount] = useState(0);
  const [note, setNote] = useState('');

  return (
    <form className="card max-w-md space-y-4" onSubmit={async (e) => {
      e.preventDefault();
      if (account.data && amount > account.data.balance) {
        toast.error('Insufficient balance'); return;
      }
      try { await withdraw.mutateAsync({ amount, note }); toast.success('Withdrawal recorded'); navigate(`/savings-app/${id}`); }
      catch (err) { toast.error((err as Error).message); }
    }}>
      <h1 className="text-xl font-semibold">Withdraw · {account.data?.memberName ?? `#${id}`}</h1>
      <p className="text-sm text-slate-500">Available: {account.data ? `₹${account.data.balance}` : '…'}</p>
      <div><label className="label">Amount (₹)</label><input className="input" type="number" min="1" value={amount} onChange={(e) => setAmount(Number(e.target.value))} /></div>
      <div><label className="label">Note</label><textarea className="input" rows={2} value={note} onChange={(e) => setNote(e.target.value)} /></div>
      <button type="submit" disabled={withdraw.isPending} className="btn-danger">{withdraw.isPending ? 'Saving…' : 'Confirm Withdrawal'}</button>
    </form>
  );
}