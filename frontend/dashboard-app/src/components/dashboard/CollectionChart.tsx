import type { ChartPoint } from '../../types';
import { formatINR } from '../../lib/format';

interface Props {
  series: ChartPoint[];
}

export default function CollectionChart({ series }: Props) {
  const max = Math.max(1, ...series.map((p) => p.value));
  return (
    <div className="card">
      <h2 className="mb-4 text-lg font-semibold">Collections (last 12 months)</h2>
      <div className="flex h-48 items-end gap-2">
        {series.map((p) => (
          <div key={p.label} className="flex flex-1 flex-col items-center gap-1">
            <div
              className="w-full rounded-t bg-brand-500/80 hover:bg-brand-600"
              style={{ height: `${(p.value / max) * 100}%` }}
              title={`${p.label}: ${formatINR(p.value)}`}
            />
            <span className="text-[10px] text-slate-500">{p.label}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
