// Shared types for the dashboard module.
// These will be sourced from `backend/api/` JSON endpoints.

export interface Stat {
  label: string;
  value: number | string;
  change?: number; // % vs last period
  icon?: string;
  tone?: 'positive' | 'negative' | 'neutral';
}

export interface DashboardSummary {
  totalMembers: number;
  activeLoans: number;
  totalSavings: number;
  totalCollected: number;
  overdueAmount: number;
}

export interface ChartPoint {
  label: string;
  value: number;
}

export interface RecentMember {
  id: number;
  name: string;
  code: string;
  joinedAt: string;
}

export interface DashboardData {
  summary: DashboardSummary;
  collectionSeries: ChartPoint[];
  loanStatusBreakdown: ChartPoint[];
  recentMembers: RecentMember[];
}
