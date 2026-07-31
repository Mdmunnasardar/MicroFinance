export interface SavingsAccount {
  memberId: number;
  memberName: string;
  balance: number;
  lastTransactionAt?: string;
}

export type TxnType = 'deposit' | 'withdrawal';

export interface SavingsTransaction {
  id: number;
  memberId: number;
  type: TxnType;
  amount: number;
  balanceAfter: number;
  note?: string;
  occurredAt: string;
}