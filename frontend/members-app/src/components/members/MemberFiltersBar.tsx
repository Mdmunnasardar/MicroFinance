import type { MemberFilters } from '../../types';

interface Props { value: MemberFilters; onChange: (next: MemberFilters) => void }

export default function MemberFiltersBar({ value, onChange }: Props) {
  return (
    <div className="card flex flex-wrap items-end gap-3">
      <div className="flex-1 min-w-[200px]">
        <label className="label" htmlFor="search">Search</label>
        <input id="search" className="input" placeholder="Name, code, phone…"
          value={value.search ?? ''}
          onChange={(e) => onChange({ ...value, search: e.target.value, page: 1 })} />
      </div>
      <div>
        <label className="label" htmlFor="status">Status</label>
        <select id="status" className="input"
          value={value.status ?? 'all'}
          onChange={(e) => onChange({ ...value, status: e.target.value as MemberFilters['status'], page: 1 })}>
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
  );
}