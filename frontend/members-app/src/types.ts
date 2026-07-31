// Domain types for the Members module.
// Mirror the columns of `members` and `committees` tables.

export interface Member {
  id: number;
  code: string;
  name: string;
  phone: string;
  email?: string;
  address?: string;
  committeeId?: number;
  committeeName?: string;
  joinedAt: string;
  status: 'active' | 'inactive';
  totalSavings?: number;
  activeLoans?: number;
}

export interface MemberFilters {
  search?: string;
  committeeId?: number;
  status?: 'active' | 'inactive' | 'all';
  page?: number;
  pageSize?: number;
}

export interface MemberListResponse {
  items: Member[];
  total: number;
}