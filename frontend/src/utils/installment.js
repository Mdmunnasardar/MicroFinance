import { formatMoney } from './formatMoney';

// Pure derivation helpers. No React, no API calls. Safe to use in render.

export function deriveStatus(item) {
  // The backend only ever sets 'paid' or 'pending' in the DB, but the UI maps
  // to richer labels:
  //   paidAmount === dueAmount   → 'paid'
  //   paidAmount > 0             → 'partial'
  //   paidAmount === 0           → 'pending'
  //   dueDate < today & pending  → 'overdue' (computed client-side)
  if (!item) return 'pending';
  const due = Number(item.dueAmount) || 0;
  const paid = Number(item.paidAmount) || 0;
  if (paid >= due && due > 0) return 'paid';
  if (paid > 0) return 'partial';
  if (item.dueDate) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(item.dueDate);
    if (!Number.isNaN(dueDate.getTime()) && dueDate < today) return 'overdue';
  }
  return 'pending';
}

export const STATUS_LABELS = {
  paid: 'Paid',
  pending: 'Pending',
  partial: 'Partial',
  overdue: 'Overdue',
};

export function statusLabel(status) {
  return STATUS_LABELS[status] || status;
}

export function balanceOf(item) {
  if (!item) return 0;
  const due = Number(item.dueAmount) || 0;
  const paid = Number(item.paidAmount) || 0;
  return Math.max(0, due - paid);
}

export function progressOf(item) {
  const due = Number(item?.dueAmount) || 0;
  const paid = Number(item?.paidAmount) || 0;
  if (due <= 0) return 0;
  return Math.min(100, Math.round((paid / due) * 100));
}

export function buildStats(items) {
  const summary = {
    total: 0,
    pending: 0,
    paid: 0,
    partial: 0,
    overdue: 0,
    dueTotal: 0,
    paidTotal: 0,
    balanceTotal: 0,
    overdueAmount: 0,
  };
  for (const item of items || []) {
    summary.total += 1;
    const status = deriveStatus(item);
    if (status === 'paid') summary.paid += 1;
    else if (status === 'partial') summary.partial += 1;
    else if (status === 'overdue') summary.overdue += 1;
    else summary.pending += 1;
    summary.dueTotal += Number(item.dueAmount) || 0;
    summary.paidTotal += Number(item.paidAmount) || 0;
    summary.balanceTotal += balanceOf(item);
    if (status === 'overdue') summary.overdueAmount += balanceOf(item);
  }
  return summary;
}

export function collectRate(items) {
  const due = items.reduce((sum, i) => sum + (Number(i.dueAmount) || 0), 0);
  if (due <= 0) return 0;
  const paid = items.reduce((sum, i) => sum + (Number(i.paidAmount) || 0), 0);
  return Math.min(100, Math.round((paid / due) * 100));
}

export function formatStatsForCards(stats) {
  return {
    totalLabel: `${stats.total} scheduled`,
    collectedLabel: formatMoney(stats.paidTotal),
    outstandingLabel: formatMoney(stats.balanceTotal),
    overdueLabel: `${stats.overdue} overdue`,
    overdueAmountLabel: formatMoney(stats.overdueAmount),
  };
}

export function today() {
  return new Date().toISOString().slice(0, 10);
}

export function isToday(dateStr) {
  if (!dateStr) return false;
  return dateStr.slice(0, 10) === today();
}

export function formatDateShort(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
