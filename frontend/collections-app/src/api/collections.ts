import { apiGet, apiSend } from './client';
import type { CollectionReport, Due, Installment, InstallmentFilters, LoanPayment } from '../types';

function qs(filters: InstallmentFilters): string {
  const p = new URLSearchParams();
  if (filters.status) p.set('status', filters.status);
  if (filters.search) p.set('search', filters.search);
  if (filters.page) p.set('page', String(filters.page));
  if (filters.pageSize) p.set('page_size', String(filters.pageSize));
  const s = p.toString();
  return s ? `?${s}` : '';
}

export function fetchInstallments(filters: InstallmentFilters = {}): Promise<Installment[]> {
  return apiGet<Installment[]>(`/collections/installments${qs(filters)}`);
}
export function fetchPaymentList(): Promise<LoanPayment[]> {
  return apiGet<LoanPayment[]>('/collections/payments');
}
export function fetchOverdue(): Promise<Due[]> {
  return apiGet<Due[]>('/collections/overdue');
}
export function fetchDues(): Promise<Due[]> {
  return apiGet<Due[]>('/collections/dues');
}
export function fetchReport(): Promise<CollectionReport> {
  return apiGet<CollectionReport>('/collections/report');
}
export function recordCollection(payload: { installmentId: number; amount: number; note?: string }): Promise<LoanPayment> {
  return apiSend<LoanPayment>('POST', '/collections/payments', payload);
}