// Maps a raw role string to a human-readable label.
// Single source of truth across the installments section.
const ROLE_LABELS = {
  admin: 'Admin',
  branch_manager: 'Branch Manager',
  field_officer: 'Field Officer',
  member: 'Member',
};

export function roleLabel(role) {
  if (!role) return 'User';
  if (ROLE_LABELS[role]) return ROLE_LABELS[role];
  return String(role).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function initials(value) {
  if (!value) return 'FO';
  const parts = String(value).trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return ((parts[0][0] || '') + (parts[parts.length - 1][0] || '')).toUpperCase();
}

export function isAdminLike(role) {
  return role === 'admin' || role === 'branch_manager';
}