import { useReport } from '../hooks/useCollections';
import { formatINR } from '../lib/format';

export default function ReportPage() {
  const { data, isLoading } = useReport();
  if (isLoading) return <p>Loading…</p>;
  if (!data) return null;
  const max = Math.max(1, ...data.series.map((p) => p.value));

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Collections Report</h1>
      <div className="grid gap-4 sm:grid-cols-3">
        <div className="card"><p className="text-xs uppercase text-slate-500">Collected</p><p className="text-2xl font-semibold">{formatINR(data.totalCollected)}</p></div>
        <div className="card"><p className="text-xs uppercase text-slate-500">Pending</p><p className="text-2xl font-semibold">{formatINR(data.totalPending)}</p></div>
        <div className="card"><p className="text-xs uppercase text-slate-500">Overdue</p><p className="text-2xl font-semibold text-rose-700">{formatINR(data.totalOverdue)}</p></div>
      </div>
      <div className="card">
        <h2 className="mb-4 text-lg font-semibold">Monthly collections</h2>
        <div className="flex h-48 items-end gap-2">
          {data.series.map((p) => (
            <div key={p.label} className="flex flex-1 flex-col items-center gap-1">
              <div className="w-full rounded-t bg-brand-500/80 hover:bg-brand-600"
                style={{ height: `${(p.value / max) * 100}%` }} title={`${p.label}: ${formatINR(p.value)}`} />
              <span className="text-[10px] text-slate-500">{p.label}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}