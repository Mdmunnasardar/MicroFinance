import { usePaymentList } from '../hooks/useCollections';
import { formatINR, formatDate } from '../lib/format';

export default function PaymentListPage() {
  const { data, isLoading } = usePaymentList();
  if (isLoading) return <p>Loading…</p>;
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">Payments</h1>
      <div className="card overflow-x-auto p-0">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-4 py-3">Date</th><th className="px-4 py-3">Loan</th><th className="px-4 py-3">Member</th><th className="px-4 py-3">#</th><th className="px-4 py-3">Amount</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {(data ?? []).map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-3">{formatDate(p.paidAt)}</td>
                <td className="px-4 py-3 font-mono text-xs">{p.loanCode}</td>
                <td className="px-4 py-3">{p.memberName}</td>
                <td className="px-4 py-3">{p.installmentNo}</td>
                <td className="px-4 py-3">{formatINR(p.amount)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}