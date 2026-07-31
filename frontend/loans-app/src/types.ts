// Domain types for the Loans module.

export type LoanStatus = 'active' | 'closed' | 'overdue' | 'pending';

export interface Loan {
  id: number;
  code: string;
  memberId: number;
  memberName: string;
  principal: number;
  interestRate: number; // annual %
  termMonths: number;
  totalPayable: number;
  installmentAmount: number;
  totalPaid: number;
  disbursedAt: string;
  maturityDate: string;
  status: LoanStatus;
}

export interface LoanPayment {
  id: number;
  loanId: number;
  amount: number;
  paidAt: string;
  installmentNo: number;
  note?: string;
}

export interface LoanFilters {
  search?: string;
  status?: LoanStatus | 'all';
  memberId?: number;
  page?: number;
  pageSize?: number;
}

export interface LoanListResponse {
  items: Loan[];
  total: number;
}

export const STATUS_TONE: Record<LoanStatus, string> = {
  active: 'badge-success',
  closed: 'badge-neutral',
  overdue: 'badge-danger',
  pending: 'badge-warn',
};