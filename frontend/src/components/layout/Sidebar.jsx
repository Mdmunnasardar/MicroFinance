import { NavLink } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const legacyBase = 'http://localhost/MicroFinance';

const internalLinks = [
  { to: '/', label: 'Dashboard', icon: 'fa-solid fa-chart-pie', end: true },
  { to: '/members', label: 'Members', icon: 'fa-solid fa-users', end: true },
  { to: '/committees', label: 'Committees', icon: 'fa-solid fa-layer-group', end: true },
  { to: '/loans', label: 'Loans', icon: 'fa-solid fa-money-bill-wave', end: true },
  { to: '/installments', label: 'Installments', icon: 'fa-solid fa-credit-card', end: true },
];

const legacyLinks = [
  { to: `${legacyBase}/savings/`, label: 'Savings', icon: 'fa-solid fa-piggy-bank' },
  { to: `${legacyBase}/due_system/`, label: 'Due System', icon: 'fa-solid fa-clock' },
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
          <i className="fa-solid fa-building-columns"></i>
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
            <i className={link.icon}></i>
            {link.label}
          </NavLink>
        ))}
        {legacyLinks.map((link) => (
          <a key={link.to} href={link.to}>
            <i className={link.icon}></i>
            {link.label}
          </a>
        ))}
      </div>

      <div className="sidebar-bottom">
        <a href="/MicroFinance/logout.php" onClick={handleLogout}>
          <i className="fa-solid fa-right-from-bracket"></i>
          Logout
        </a>
      </div>
    </aside>
  );
}
