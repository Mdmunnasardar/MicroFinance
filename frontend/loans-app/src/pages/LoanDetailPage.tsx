import { Link, useParams } from 'react-router-dom';
import { useLoan, useLoanPayments } from '../hooks/useLoans';
import { formatINR, formatDate } from '../lib/format';
import { STATUS_TONE } from '../types';

export default function LoanDetailPage() {
  const { id } = useParams();
  const loanId = Number(id);
  const { data: loan, isLoading } = useLoan(loanId);
  const { data: payments } = useLoanPayments(loanId);

  if (isLoading || !loan) return <p>Loading…</p>;

  const progress = loan.totalPayable ? Math.min(100, (loan.totalPaid / loan.totalPayable) * 100) : 0;

  return (
    <div className="space-y-6">
      <div className="card flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Loan {loan.code}</h1>
          <p className="text-sm text-slate-500">{loan.memberName}</p>
          <span className={`mt-2 inline-block ${STATUS_TONE[loan.status]}`}>{loan.status}</span>
        </div>
        <div className="flex gap-2">
          <Link to={`${loanId}/payment`} className="btn-primary">Record Payment</Link>
          <Link to={`${loanId}/edit`} className="btn-ghost">Edit</Link>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div className="card"><p className="text-xs uppercase text-slate-500">Principal</p><p className="text-xl font-semibold">{formatINR(loan.principal)}</p></div>
        <div className="card"><p className="text-xs uppercase text-slate-500">Total Payable</p><p className="text-xl font-semibold">{formatINR(loan.totalPayable)}</p></div>
        <div className="card"><p className="text-xs uppercase text-slate-500">Total Paid</p><p className="text-xl font-semibold">{formatINR(loan.totalPaid)}</p></div>
        <div className="card"><p className="text-xs uppercase text-slate-500">Installment</p><p className="text-xl font-semibold">{formatINR(loan.installmentAmount)}</p></div>
      </div>

      <div className="card">
        <p className="mb-2 text-sm text-slate-500">Progress: {progress.toFixed(1)}%</p>
        <div className="h-2 w-full rounded-full bg-slate-100">
          <div className="h-2 rounded-full bg-brand-600" style={{ width: `${progress}%` }} />
        </div>
      </div>

      <div className="card overflow-x-auto p-0">
        <h2 className="px-5 pt-5 text-lg font-semibold">Payments</h2>
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-5 py-3">#</th><th className="px-5 py-3">Date</th><th className="px-5 py-3">Amount</th><th className="px-5 py-3">Note</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {payments?.length ? payments.map((p) => (
              <tr key={p.id}>
                <td className="px-5 py-3">{p.installmentNo}</td>
                <td className="px-5 py-3">{formatDate(p.paidAt)}</td>
                <td className="px-5 py-3">{formatINR(p.amount)}</td>
                <td className="px-5 py-3">{p.note ?? '—'}</td>
              </tr>
            )) : (
              <tr><td colSpan={4} className="px-5 py-6 text-center text-slate-500">No payments yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}