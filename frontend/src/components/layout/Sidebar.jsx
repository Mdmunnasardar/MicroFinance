import { NavLink } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const legacyBase = 'http://localhost/MicroFinance';

const internalLinks = [
  { to: '/installments', label: 'Installments', icon: '💳', end: true },
];

const legacyLinks = [
  { to: `${legacyBase}/dashboard.php`, label: 'Dashboard', icon: '📊' },
  { to: `${legacyBase}/members/`, label: 'Members', icon: '👥' },
  { to: `${legacyBase}/Committees/`, label: 'Committees', icon: '🧩' },
  { to: `${legacyBase}/loans/`, label: 'Loans', icon: '💰' },
  { to: `${legacyBase}/savings/`, label: 'Savings', icon: '🐖' },
  { to: `${legacyBase}/due_system/`, label: 'Due System', icon: '⏰' },
];

export default function Sidebar() {
  const { logout } = useAuth();

  const handleLogout = async (event) => {
    event.preventDefault();
    await logout();
    window.location.href = '/login';
  };

  return (
    <aside className="sidebar">
      <div className="sidebar-logo">
        <div className="logo-icon">
          <span style={{ fontSize: 18 }}>🏦</span>
        </div>
        <div>
          <h4>MicroFinance</h4>
          <small>Management System</small>
        </div>
      </div>

      <div className="sidebar-menu">
        {internalLinks.map((link) => (
          <NavLink key={link.to} to={link.to} end={link.end}
            className={({ isActive }) => (isActive ? 'active' : '')}>
            <span className="icon">{link.icon}</span>
            {link.label}
          </NavLink>
        ))}
        {legacyLinks.map((link) => (
          <a key={link.to} href={link.to}>
            <span className="icon">{link.icon}</span>
            {link.label}
          </a>
        ))}
      </div>

      <div className="sidebar-bottom">
        <a href="/MicroFinance/logout.php" onClick={handleLogout}>
          <span className="icon">🚪</span>
          Logout
        </a>
      </div>
    </aside>
  );
}
