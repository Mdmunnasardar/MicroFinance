import { useState } from 'react';
import { useInstallments } from '../hooks/useCollections';
import InstallmentTable from '../components/collections/InstallmentTable';
import type { InstallmentFilters } from '../types';

export default function InstallmentsListPage() {
  const [filters, setFilters] = useState<InstallmentFilters>({ status: 'all' });
  const { data, isLoading } = useInstallments(filters);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">Installments</h1>
      <div className="card flex flex-wrap items-end gap-3">
        <div className="flex-1 min-w-[200px]">
          <label className="label" htmlFor="search">Search</label>
          <input id="search" className="input" placeholder="Loan code, member…"
            value={filters.search ?? ''}
            onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))} />
        </div>
        <div>
          <label className="label" htmlFor="status">Status</label>
          <select id="status" className="input"
            value={filters.status ?? 'all'}
            onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value as InstallmentFilters['status'] }))}>
            <option value="all">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>
      </div>
      {isLoading ? <p>Loading…</p> : <InstallmentTable items={data ?? []} />}
    </div>
  );
}