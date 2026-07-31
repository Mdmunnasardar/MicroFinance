import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createLoan, deleteLoan, fetchLoan, fetchLoans, listPayments, recordPayment, updateLoan } from '../api/loans';
import type { Loan, LoanFilters } from '../types';

export function useLoans(filters: LoanFilters) {
  return useQuery({ queryKey: ['loans', filters], queryFn: () => fetchLoans(filters) });
}
export function useLoan(id: number) {
  return useQuery({ queryKey: ['loans', id], queryFn: () => fetchLoan(id), enabled: !!id });
}
export function useLoanPayments(id: number) {
  return useQuery({ queryKey: ['loans', id, 'payments'], queryFn: () => listPayments(id), enabled: !!id });
}
export function useCreateLoan() {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (input: Omit<Loan, 'id' | 'code' | 'totalPaid'>) => createLoan(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['loans'] }) });
}
export function useUpdateLoan(id: number) {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (patch: Partial<Loan>) => updateLoan(id, patch),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['loans'] }) });
}
export function useDeleteLoan() {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (id: number) => deleteLoan(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['loans'] }) });
}
export function useRecordPayment(loanId: number) {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (payload: { amount: number; note?: string }) => recordPayment(loanId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['loans'] });
      qc.invalidateQueries({ queryKey: ['loans', loanId] });
      qc.invalidateQueries({ queryKey: ['loans', loanId, 'payments'] });
    } });
}