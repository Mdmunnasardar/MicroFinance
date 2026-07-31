import { useDues } from '../hooks/useCollections';
import { formatINR, formatDate } from '../lib/format';

export default function DueListPage() {
  const { data, isLoading } = useDues();
  if (isLoading) return <p>Loading…</p>;
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">Due List</h1>
      <div className="card overflow-x-auto p-0">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-4 py-3">Member</th><th className="px-4 py-3">Loan</th><th className="px-4 py-3">Due Date</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Amount</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {(data ?? []).map((d) => (
              <tr key={d.id}>
                <td className="px-4 py-3">{d.memberName}</td>
                <td className="px-4 py-3 font-mono text-xs">#{d.loanId}</td>
                <td className="px-4 py-3">{formatDate(d.dueDate)}</td>
                <td className="px-4 py-3">
                  <span className={d.status === 'overdue' ? 'badge-danger' : d.status === 'paid' ? 'badge-success' : 'badge-warn'}>{d.status}</span>
                </td>
                <td className="px-4 py-3">{formatINR(d.amount)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}