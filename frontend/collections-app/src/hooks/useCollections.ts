import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchDues, fetchInstallments, fetchOverdue, fetchPaymentList, fetchReport, recordCollection } from '../api/collections';
import type { InstallmentFilters } from '../types';

export function useInstallments(filters: InstallmentFilters) {
  return useQuery({ queryKey: ['collections', 'installments', filters], queryFn: () => fetchInstallments(filters) });
}
export function usePaymentList() { return useQuery({ queryKey: ['collections', 'payments'], queryFn: fetchPaymentList }); }
export function useOverdue() { return useQuery({ queryKey: ['collections', 'overdue'], queryFn: fetchOverdue }); }
export function useDues() { return useQuery({ queryKey: ['collections', 'dues'], queryFn: fetchDues }); }
export function useReport() { return useQuery({ queryKey: ['collections', 'report'], queryFn: fetchReport }); }
export function useRecordCollection() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { installmentId: number; amount: number; note?: string }) => recordCollection(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['collections'] });
    },
  });
}