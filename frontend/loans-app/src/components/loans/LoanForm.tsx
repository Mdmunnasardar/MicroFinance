import { useForm } from 'react-hook-form';
import type { Loan } from '../../types';

type FormValues = Pick<Loan, 'memberId' | 'principal' | 'interestRate' | 'termMonths'>;

interface Props {
  initial?: Loan;
  submitting?: boolean;
  onSubmit: (values: Omit<Loan, 'id' | 'code' | 'totalPaid'>) => Promise<void> | void;
}

export default function LoanForm({ initial, submitting, onSubmit }: Props) {
  const { register, handleSubmit } = useForm<FormValues>({
    defaultValues: {
      memberId: initial?.memberId ?? 0,
      principal: initial?.principal ?? 0,
      interestRate: initial?.interestRate ?? 12,
      termMonths: initial?.termMonths ?? 12,
    },
  });

  return (
    <form className="card grid grid-cols-1 gap-4 md:grid-cols-2"
      onSubmit={handleSubmit(async (raw) => {
        await onSubmit({
          ...raw,
          memberName: initial?.memberName ?? '',
          totalPayable: initial?.totalPayable ?? 0,
          installmentAmount: initial?.installmentAmount ?? 0,
          disbursedAt: initial?.disbursedAt ?? new Date().toISOString(),
          maturityDate: initial?.maturityDate ?? new Date().toISOString(),
          status: initial?.status ?? 'pending',
        });
      })}>
      <div className="md:col-span-2">
        <h1 className="text-xl font-semibold">{initial ? 'Edit Loan' : 'New Loan'}</h1>
      </div>
      <div><label className="label">Member ID</label><input className="input" type="number" {...register('memberId')} /></div>
      <div><label className="label">Principal (₹)</label><input className="input" type="number" {...register('principal')} /></div>
      <div><label className="label">Annual Interest (%)</label><input className="input" type="number" step="0.01" {...register('interestRate')} /></div>
      <div><label className="label">Term (months)</label><input className="input" type="number" {...register('termMonths')} /></div>
      <div className="md:col-span-2 flex justify-end gap-2">
        <button type="submit" disabled={submitting} className="btn-primary">{submitting ? 'Saving…' : 'Save'}</button>
      </div>
    </form>
  );
}