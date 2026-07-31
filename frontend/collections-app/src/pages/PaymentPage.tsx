import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { useRecordCollection } from '../hooks/useCollections';

export default function PaymentPage() {
  const navigate = useNavigate();
  const record = useRecordCollection();
  const [installmentId, setInstallmentId] = useState(0);
  const [amount, setAmount] = useState(0);
  const [note, setNote] = useState('');

  return (
    <form className="card max-w-md space-y-4" onSubmit={async (e) => {
      e.preventDefault();
      try { await record.mutateAsync({ installmentId, amount, note }); toast.success('Payment recorded'); navigate('payment-list'); }
      catch (err) { toast.error((err as Error).message); }
    }}>
      <h1 className="text-xl font-semibold">Record Payment</h1>
      <div><label className="label">Installment ID</label><input className="input" type="number" value={installmentId} onChange={(e) => setInstallmentId(Number(e.target.value))} /></div>
      <div><label className="label">Amount (₹)</label><input className="input" type="number" min="1" value={amount} onChange={(e) => setAmount(Number(e.target.value))} /></div>
      <div><label className="label">Note</label><textarea className="input" rows={2} value={note} onChange={(e) => setNote(e.target.value)} /></div>
      <button type="submit" disabled={record.isPending} className="btn-primary">{record.isPending ? 'Saving…' : 'Save Payment'}</button>
    </form>
  );
}