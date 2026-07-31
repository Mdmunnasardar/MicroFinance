// Domain types for the Collections module.
// Covers installments, dues, payments, and the overdue report.

export type InstallmentStatus = 'pending' | 'paid' | 'overdue';

export interface Installment {
  id: number;
  loanId: number;
  loanCode: string;
  memberId: number;
  memberName: string;
  installmentNo: number;
  dueDate: string;
  amount: number;
  paidAmount: number;
  paidAt?: string;
  status: InstallmentStatus;
}

export interface LoanPayment {
  id: number;
  loanId: number;
  loanCode: string;
  memberName: string;
  amount: number;
  paidAt: string;
  installmentNo: number;
  note?: string;
}

export interface Due {
  id: number;
  loanId: number;
  memberName: string;
  amount: number;
  dueDate: string;
  overdueDays: number;
  status: InstallmentStatus;
}

export interface CollectionReport {
  totalCollected: number;
  totalPending: number;
  totalOverdue: number;
  series: { label: string; value: number }[];
}

export interface InstallmentFilters {
  status?: InstallmentStatus | 'all';
  search?: string;
  page?: number;
  pageSize?: number;
}