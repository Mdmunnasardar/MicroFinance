import type { Installment } from '../../types';
import { formatINR, formatDate } from '../../lib/format';

interface Props { items: Installment[] }

export default function InstallmentTable({ items }: Props) {
  return (
    <div className="card overflow-x-auto p-0">
      <table className="w-full text-sm">
        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th className="px-4 py-3">Loan</th><th className="px-4 py-3">Member</th>
            <th className="px-4 py-3">#</th><th className="px-4 py-3">Due</th>
            <th className="px-4 py-3">Amount</th><th className="px-4 py-3">Paid</th>
            <th className="px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {items.map((i) => (
            <tr key={i.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs">{i.loanCode}</td>
              <td className="px-4 py-3">{i.memberName}</td>
              <td className="px-4 py-3">{i.installmentNo}</td>
              <td className="px-4 py-3">{formatDate(i.dueDate)}</td>
              <td className="px-4 py-3">{formatINR(i.amount)}</td>
              <td className="px-4 py-3">{formatINR(i.paidAmount)}</td>
              <td className="px-4 py-3">
                <span className={i.status === 'overdue' ? 'badge-danger' : i.status === 'paid' ? 'badge-success' : 'badge-warn'}>{i.status}</span>
              </td>
            </tr>
          ))}
          {items.length === 0 && <tr><td colSpan={7} className="px-4 py-6 text-center text-slate-500">No installments.</td></tr>}
        </tbody>
      </table>
    </div>
  );
}