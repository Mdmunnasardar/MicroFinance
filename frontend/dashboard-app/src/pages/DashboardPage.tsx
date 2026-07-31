import { useDashboard } from '../hooks/useDashboard';
import StatGrid from '../components/dashboard/StatGrid';
import RecentMembers from '../components/dashboard/RecentMembers';
import CollectionChart from '../components/dashboard/CollectionChart';

// Placeholder dashboard page. Replace with real widgets once API is wired.

export default function DashboardPage() {
  const { data, isLoading, isError, error } = useDashboard();

  if (isLoading) {
    return <div className="text-slate-500">Loading dashboard…</div>;
  }
  if (isError) {
    return (
      <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">
        Failed to load dashboard: {(error as Error).message}
      </div>
    );
  }
  if (!data) return null;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
      <StatGrid summary={data.summary} />
      <div className="grid gap-6 lg:grid-cols-2">
        <CollectionChart series={data.collectionSeries} />
        <RecentMembers members={data.recentMembers} />
      </div>
    </div>
  );
}
