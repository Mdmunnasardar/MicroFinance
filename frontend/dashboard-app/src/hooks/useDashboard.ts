import { useQuery } from '@tanstack/react-query';
import { fetchDashboard } from '../api/dashboard';
import type { DashboardData } from '../types';

export function useDashboard() {
  return useQuery<DashboardData>({
    queryKey: ['dashboard'],
    queryFn: fetchDashboard,
  });
}
