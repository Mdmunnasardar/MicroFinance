import { useAuth } from './useAuth';
import { isAdminLike, roleLabel } from '../utils/roleLabel';

// Wraps useAuth to give the installments section a stable collector shape
// for the "Collected by" payload and collector card in the collect-payment modal.
export default function useCollector() {
  const { user } = useAuth();

  const collector = user
    ? {
        id: user.id ?? user.user_id ?? null,
        name: user.name || user.full_name || user.username || 'User',
        username: user.username || null,
        role: user.role || null,
      }
    : null;

  return {
    collector,
    collectorName: collector?.name || 'User',
    collectorUsername: collector?.username ? `(@${collector.username})` : '',
    roleLabelText: roleLabel(collector?.role),
    authorized: isAdminLike(collector?.role),
  };
}