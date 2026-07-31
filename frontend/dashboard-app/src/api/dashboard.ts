import { apiGet } from './client';
import type { DashboardData } from '../types';

// Placeholder endpoint path. Will be wired to the real PHP API in a later step.
export function fetchDashboard(): Promise<DashboardData> {
  return apiGet<DashboardData>('/dashboard');
}
