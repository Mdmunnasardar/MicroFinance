// Locale-aware formatters used across the dashboard.

const nf = new Intl.NumberFormat('en-IN');

export function formatINR(value: number | string): string {
  const n = typeof value === 'string' ? Number(value) : value;
  if (!Number.isFinite(n)) return '₹0';
  return `₹${nf.format(Math.round(n))}`;
}

export function formatNumber(value: number | string): string {
  const n = typeof value === 'string' ? Number(value) : value;
  if (!Number.isFinite(n)) return '0';
  return nf.format(n);
}
