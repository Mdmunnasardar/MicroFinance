import { useLocation } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const titleMap = {
  '/': 'Dashboard',
};

export default function Topbar() {
  const location = useLocation();
  const { user } = useAuth();

  const initials = (() => {
    if (!user?.name) return 'U';
    const parts = user.name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  })();

  const title = titleMap[location.pathname] || 'MicroFinance';

  return (
    <nav className="topbar">
      <div className="topbar-title">{title}</div>
      <div className="topbar-right">
        <div className="topbar-search">
          <span aria-hidden>🔍</span>
          <input type="text" placeholder="Search members, committees…" disabled />
        </div>
        <div className="topbar-user" title={user?.name}>
          <div className="avatar">{initials}</div>
          <div>
            <div className="user-name">{user?.name ?? 'User'}</div>
            <div className="user-role">{user?.role ? user.role.replace('_', ' ') : 'guest'}</div>
          </div>
        </div>
      </div>
    </nav>
  );
}
