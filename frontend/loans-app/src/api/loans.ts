import { apiGet, apiSend } from './client';
import type { Loan, LoanFilters, LoanListResponse, LoanPayment } from '../types';

function qs(filters: LoanFilters): string {
  const p = new URLSearchParams();
  if (filters.search) p.set('search', filters.search);
  if (filters.status) p.set('status', filters.status);
  if (filters.memberId) p.set('member_id', String(filters.memberId));
  if (filters.page) p.set('page', String(filters.page));
  if (filters.pageSize) p.set('page_size', String(filters.pageSize));
  const s = p.toString();
  return s ? `?${s}` : '';
}

export function fetchLoans(filters: LoanFilters = {}): Promise<LoanListResponse> {
  return apiGet<LoanListResponse>(`/loans${qs(filters)}`);
}
export function fetchLoan(id: number): Promise<Loan> {
  return apiGet<Loan>(`/loans/${id}`);
}
export function createLoan(input: Omit<Loan, 'id' | 'code' | 'totalPaid'>): Promise<Loan> {
  return apiSend<Loan>('POST', '/loans', input);
}
export function updateLoan(id: number, patch: Partial<Loan>): Promise<Loan> {
  return apiSend<Loan>('PUT', `/loans/${id}`, patch);
}
export function deleteLoan(id: number): Promise<void> {
  return apiSend<void>('DELETE', `/loans/${id}`);
}
export function recordPayment(loanId: number, payload: { amount: number; note?: string }): Promise<LoanPayment> {
  return apiSend<LoanPayment>('POST', `/loans/${loanId}/payments`, payload);
}
export function listPayments(loanId: number): Promise<LoanPayment[]> {
  return apiGet<LoanPayment[]>(`/loans/${loanId}/payments`);
}