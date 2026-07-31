import type { RecentMember } from '../../types';
import { format } from 'date-fns';

interface Props {
  members: RecentMember[];
}

export default function RecentMembers({ members }: Props) {
  return (
    <div className="card">
      <h2 className="mb-4 text-lg font-semibold">Recent Members</h2>
      {members.length === 0 ? (
        <p className="text-sm text-slate-500">No recent members.</p>
      ) : (
        <ul className="divide-y divide-slate-100">
          {members.map((m) => (
            <li key={m.id} className="flex items-center justify-between py-2">
              <div>
                <p className="font-medium text-slate-800">{m.name}</p>
                <p className="text-xs text-slate-500">{m.code}</p>
              </div>
              <span className="text-xs text-slate-500">
                {format(new Date(m.joinedAt), 'PP')}
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
