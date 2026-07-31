import { Link } from 'react-router-dom';
import type { Member } from '../../types';
import { formatDate } from '../../lib/format';

interface Props { members: Member[]; total: number }

export default function MemberTable({ members, total }: Props) {
  return (
    <div className="card overflow-x-auto p-0">
      <table className="w-full text-sm">
        <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th className="px-4 py-3">Code</th>
            <th className="px-4 py-3">Name</th>
            <th className="px-4 py-3">Phone</th>
            <th className="px-4 py-3">Committee</th>
            <th className="px-4 py-3">Joined</th>
            <th className="px-4 py-3">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {members.map((m) => (
            <tr key={m.id} className="hover:bg-slate-50">
              <td className="px-4 py-3 font-mono text-xs">{m.code}</td>
              <td className="px-4 py-3">
                <Link to={`${m.id}`} className="font-medium text-brand-700 hover:underline">{m.name}</Link>
              </td>
              <td className="px-4 py-3">{m.phone}</td>
              <td className="px-4 py-3">{m.committeeName ?? '—'}</td>
              <td className="px-4 py-3">{formatDate(m.joinedAt)}</td>
              <td className="px-4 py-3">
                <span className={`rounded-full px-2 py-0.5 text-xs ${m.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'}`}>
                  {m.status}
                </span>
              </td>
            </tr>
          ))}
          {members.length === 0 && (
            <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-500">No members found.</td></tr>
          )}
        </tbody>
      </table>
      <div className="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">Total: {total}</div>
    </div>
  );
}