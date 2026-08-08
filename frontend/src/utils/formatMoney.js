// Single source of truth for money formatting across the installments section.
// Use ৳ (Bangladeshi Taka) prefix with two-decimal locale formatting.
export function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Compact form used in tight spaces (e.g. stat cards, table cells).
// Keeps the currency symbol but drops trailing zeros where safe.
export function formatMoneyCompact(value) {
  const n = Number(value) || 0;
  const fixed = n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  return `৳ ${fixed}`;
}