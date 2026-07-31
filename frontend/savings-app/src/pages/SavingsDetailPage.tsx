import { Link, useParams } from 'react-router-dom';
import { useSavings, useTransactions } from '../hooks/useSavings';
import { formatINR, formatDate } from '../lib/format';

export default function SavingsDetailPage() {
  const { memberId } = useParams();
  const id = Number(memberId);
  const account = useSavings(id);
  const txns = useTransactions(id);

  if (account.isLoading || !account.data) return <p>Loading…</p>;

  return (
    <div className="space-y-6">
      <div className="card flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">{account.data.memberName}</h1>
          <p className="text-sm text-slate-500">Member #{id}</p>
        </div>
        <div className="text-right">
          <p className="text-xs uppercase text-slate-500">Balance</p>
          <p className="text-3xl font-bold">{formatINR(account.data.balance)}</p>
        </div>
        <div className="flex gap-2">
          <Link to={`${id}/deposit`} className="btn-success">+ Deposit</Link>
          <Link to={`${id}/withdraw`} className="btn-danger">− Withdraw</Link>
        </div>
      </div>

      <div className="card overflow-x-auto p-0">
        <h2 className="px-5 pt-5 text-lg font-semibold">Transactions</h2>
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-5 py-3">Date</th><th className="px-5 py-3">Type</th><th className="px-5 py-3">Amount</th><th className="px-5 py-3">Balance after</th><th className="px-5 py-3">Note</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {txns.data?.length ? txns.data.map((t) => (
              <tr key={t.id}>
                <td className="px-5 py-3">{formatDate(t.occurredAt)}</td>
                <td className="px-5 py-3">
                  <span className={`badge ${t.type === 'deposit' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>{t.type}</span>
                </td>
                <td className="px-5 py-3">{formatINR(t.amount)}</td>
                <td className="px-5 py-3">{formatINR(t.balanceAfter)}</td>
                <td className="px-5 py-3">{t.note ?? '—'}</td>
              </tr>
            )) : (
              <tr><td colSpan={5} className="px-5 py-6 text-center text-slate-500">No transactions.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}