import type { LoanFilters, LoanStatus } from '../../types';

interface Props { value: LoanFilters; onChange: (next: LoanFilters) => void }

export default function LoanFiltersBar({ value, onChange }: Props) {
  return (
    <div className="card flex flex-wrap items-end gap-3">
      <div className="flex-1 min-w-[200px]">
        <label className="label" htmlFor="search">Search</label>
        <input id="search" className="input" placeholder="Code, member name…"
          value={value.search ?? ''}
          onChange={(e) => onChange({ ...value, search: e.target.value, page: 1 })} />
      </div>
      <div>
        <label className="label" htmlFor="status">Status</label>
        <select id="status" className="input"
          value={value.status ?? 'all'}
          onChange={(e) => onChange({ ...value, status: e.target.value as LoanStatus | 'all', page: 1 })}>
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="overdue">Overdue</option>
          <option value="closed">Closed</option>
          <option value="pending">Pending</option>
        </select>
      </div>
    </div>
  );
}