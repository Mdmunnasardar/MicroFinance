import { useOverdue } from '../hooks/useCollections';
import { formatINR, formatDate } from '../lib/format';

export default function OverduePage() {
  const { data, isLoading } = useOverdue();
  if (isLoading) return <p>Loading…</p>;
  const total = (data ?? []).reduce((s, d) => s + d.amount, 0);
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Overdue Loans</h1>
        <div className="card px-5 py-3">
          <p className="text-xs uppercase text-slate-500">Total overdue</p>
          <p className="text-xl font-semibold text-rose-700">{formatINR(total)}</p>
        </div>
      </div>
      <div className="card overflow-x-auto p-0">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-4 py-3">Member</th><th className="px-4 py-3">Loan</th><th className="px-4 py-3">Due Date</th><th className="px-4 py-3">Days</th><th className="px-4 py-3">Amount</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {(data ?? []).map((d) => (
              <tr key={d.id}>
                <td className="px-4 py-3">{d.memberName}</td>
                <td className="px-4 py-3 font-mono text-xs">#{d.loanId}</td>
                <td className="px-4 py-3">{formatDate(d.dueDate)}</td>
                <td className="px-4 py-3"><span className="badge-danger">{d.overdueDays}d</span></td>
                <td className="px-4 py-3">{formatINR(d.amount)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}