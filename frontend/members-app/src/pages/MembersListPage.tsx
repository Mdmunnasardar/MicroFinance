import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMembers } from '../hooks/useMembers';
import MemberTable from '../components/members/MemberTable';
import MemberFiltersBar from '../components/members/MemberFiltersBar';
import type { MemberFilters } from '../types';

export default function MembersListPage() {
  const [filters, setFilters] = useState<MemberFilters>({ page: 1, pageSize: 25 });
  const { data, isLoading, isError, error } = useMembers(filters);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight">Members</h1>
        <Link to="new" className="btn-primary">+ Add Member</Link>
      </div>
      <MemberFiltersBar value={filters} onChange={setFilters} />
      {isLoading && <p className="text-slate-500">Loading…</p>}
      {isError && (
        <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">
          Failed to load members: {(error as Error).message}
        </div>
      )}
      {data && <MemberTable members={data.items} total={data.total} />}
    </div>
  );
}