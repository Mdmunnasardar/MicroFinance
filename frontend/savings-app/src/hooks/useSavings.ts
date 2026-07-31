import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deposit, fetchSavings, fetchSavingsList, fetchTransactions, withdraw } from '../api/savings';

export function useSavingsList() {
  return useQuery({ queryKey: ['savings'], queryFn: fetchSavingsList });
}
export function useSavings(memberId: number) {
  return useQuery({ queryKey: ['savings', memberId], queryFn: () => fetchSavings(memberId), enabled: !!memberId });
}
export function useTransactions(memberId: number) {
  return useQuery({ queryKey: ['savings', memberId, 'transactions'], queryFn: () => fetchTransactions(memberId), enabled: !!memberId });
}
export function useDeposit(memberId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { amount: number; note?: string }) => deposit(memberId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['savings'] });
      qc.invalidateQueries({ queryKey: ['savings', memberId] });
      qc.invalidateQueries({ queryKey: ['savings', memberId, 'transactions'] });
    },
  });
}
export function useWithdraw(memberId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { amount: number; note?: string }) => withdraw(memberId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['savings'] });
      qc.invalidateQueries({ queryKey: ['savings', memberId] });
      qc.invalidateQueries({ queryKey: ['savings', memberId, 'transactions'] });
    },
  });
}