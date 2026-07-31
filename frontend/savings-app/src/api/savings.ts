import { apiGet, apiSend } from './client';
import type { SavingsAccount, SavingsTransaction } from '../types';

export function fetchSavingsList(): Promise<SavingsAccount[]> { return apiGet<SavingsAccount[]>('/savings'); }
export function fetchSavings(memberId: number): Promise<SavingsAccount> { return apiGet<SavingsAccount>(`/savings/${memberId}`); }
export function fetchTransactions(memberId: number): Promise<SavingsTransaction[]> { return apiGet<SavingsTransaction[]>(`/savings/${memberId}/transactions`); }
export function deposit(memberId: number, payload: { amount: number; note?: string }): Promise<SavingsTransaction> {
  return apiSend<SavingsTransaction>('POST', `/savings/${memberId}/deposit`, payload);
}
export function withdraw(memberId: number, payload: { amount: number; note?: string }): Promise<SavingsTransaction> {
  return apiSend<SavingsTransaction>('POST', `/savings/${memberId}/withdraw`, payload);
}