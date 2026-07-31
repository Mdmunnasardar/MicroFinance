import { Link } from 'react-router-dom';
import { useSavingsList } from '../hooks/useSavings';
import { formatINR, formatDate } from '../lib/format';

export default function SavingsListPage() {
  const { data, isLoading, isError, error } = useSavingsList();
  if (isLoading) return <p>Loading…</p>;
  if (isError) return <p className="text-rose-600">{(error as Error).message}</p>;

  const total = (data ?? []).reduce((sum, a) => sum + a.balance, 0);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Savings</h1>
        <div className="card px-5 py-3">
          <p className="text-xs uppercase text-slate-500">Total balance</p>
          <p className="text-xl font-semibold">{formatINR(total)}</p>
        </div>
      </div>
      <div className="card overflow-x-auto p-0">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-4 py-3">Member</th><th className="px-4 py-3">Balance</th><th className="px-4 py-3">Last activity</th><th></th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {(data ?? []).map((a) => (
              <tr key={a.memberId}>
                <td className="px-4 py-3">
                  <Link to={`${a.memberId}`} className="text-brand-700 hover:underline">{a.memberName}</Link>
                </td>
                <td className="px-4 py-3">{formatINR(a.balance)}</td>
                <td className="px-4 py-3">{a.lastTransactionAt ? formatDate(a.lastTransactionAt) : '—'}</td>
                <td className="px-4 py-3 text-right">
                  <Link to={`${a.memberId}/deposit`} className="btn-success text-xs">Deposit</Link>{' '}
                  <Link to={`${a.memberId}/withdraw`} className="btn-danger text-xs">Withdraw</Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}