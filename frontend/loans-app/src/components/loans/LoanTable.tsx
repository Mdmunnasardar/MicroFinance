import { Link } from 'react-router-dom';
import type { Loan } from '../../types';
import { STATUS_TONE } from '../../types';
import { formatINR, formatDate } from '../../lib/format';

interface Props { loans: Loan[]; total: number }

export default function LoanTable({ loans, total }: Props) {
  return (
    <div className="card overflow-x-auto p-0">
      <table className="w-full text-sm">
        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th className="px-4 py-3">Code</th><th className="px-4 py-3">Member</th>
            <th className="px-4 py-3">Principal</th><th className="px-4 py-3">Paid</th>
            <th className="px-4 py-3">Disbursed</th><th className="px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {loans.map((l) => (
            <tr key={l.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs">
                <Link to={`${l.id}`} className="text-brand-700 hover:underline">{l.code}</Link>
              </td>
              <td className="px-4 py-3">{l.memberName}</td>
              <td className="px-4 py-3">{formatINR(l.principal)}</td>
              <td className="px-4 py-3">{formatINR(l.totalPaid)} / {formatINR(l.totalPayable)}</td>
              <td className="px-4 py-3">{formatDate(l.disbursedAt)}</td>
              <td className="px-4 py-3"><span className={STATUS_TONE[l.status]}>{l.status}</span></td>
            </tr>
          ))}
          {loans.length === 0 && (
            <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-500">No loans.</td></tr>
          )}
        </tbody>
      </table>
      <div className="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">Total: {total}</div>
    </div>
  );
}