import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useLoans } from '../hooks/useLoans';
import LoanTable from '../components/loans/LoanTable';
import LoanFiltersBar from '../components/loans/LoanFiltersBar';
import type { LoanFilters } from '../types';

export default function LoansListPage() {
  const [filters, setFilters] = useState<LoanFilters>({ page: 1, pageSize: 25 });
  const { data, isLoading, isError, error } = useLoans(filters);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold tracking-tight">Loans</h1>
        <Link to="new" className="btn-primary">+ New Loan</Link>
      </div>
      <LoanFiltersBar value={filters} onChange={setFilters} />
      {isLoading && <p className="text-slate-500">Loading…</p>}
      {isError && (
        <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">
          Failed: {(error as Error).message}
        </div>
      )}
      {data && <LoanTable loans={data.items} total={data.total} />}
    </div>
  );
}