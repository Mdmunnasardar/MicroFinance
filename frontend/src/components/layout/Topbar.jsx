import { useCallback, useEffect, useRef, useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';

const titleMap = {
  '/': 'Dashboard',
  '/members': 'Members',
  '/members/new': 'Add Member',
  '/committees': 'Committees',
  '/committees/new': 'Add Committee',
  '/loans': 'Loans',
  '/loans/new': 'Create Loan',
  '/installments': 'Installments',
};

// Legacy endpoints used by PHP topbar.php (these work; the /api/* JSON
// endpoints under backend/public/api/ are stubs and return notImplemented).
// We hit them via the Vite dev-server proxy (/MicroFinance/api/*) so the
// browser sees a same-origin request — the legacy PHP shims don't send
// CORS headers and would otherwise be blocked.
const LEGACY_BASE = '/MicroFinance';

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

export default function Topbar() {
  const location = useLocation();
  const { user } = useAuth();

  const computeTitle = () => {
    const { pathname } = location;
    if (pathname.startsWith('/members/') && pathname.endsWith('/edit')) return 'Edit Member';
    if (/^\/members\/\d+$/.test(pathname)) return 'Member Profile';
    if (pathname.startsWith('/committees/') && pathname.endsWith('/edit')) return 'Edit Committee';
    if (pathname.endsWith('/members') && pathname.startsWith('/committees/')) return 'Manage Members';
    if (/^\/committees\/\d+$/.test(pathname)) return 'Committee Details';
    if (pathname.startsWith('/loans/') && pathname.endsWith('/edit')) return 'Edit Loan';
    if (pathname.endsWith('/payment') && pathname.startsWith('/loans/')) return 'Record Payment';
    if (/^\/loans\/\d+$/.test(pathname)) return 'Loan Details';
    return titleMap[pathname] || 'MicroFinance';
  };
  const title = computeTitle();
  const userName = user?.name || 'User';
  const userRole = user?.role || 'user';
  const userId = user?.id ?? 0;
  const userAvatar = user?.avatar ?? null;

  const initials = getInitials(userName);
  const avatarSrc = userAvatar ? `${LEGACY_BASE}/uploads/avatars/${userAvatar}` : null;

  // Sidebar toggle (PHP topbar.php JS section 12) — emits a custom event the
  // Sidebar component can listen to. PHP uses a real id/class toggle; we
  // dispatch an event so the existing React Sidebar can react without us
  // modifying the Sidebar component.
  const handleSidebarToggle = useCallback(() => {
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    if (sidebar) {
      sidebar.classList.toggle('active');
    }
  }, []);

  // ----- Notifications -----
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [notificationsLoaded, setNotificationsLoaded] = useState(false);

  const loadNotifications = useCallback(() => {
    fetch(`${LEGACY_BASE}/api/notifications.php`, { credentials: 'include' })
      .then((res) => res.json())
      .then((data) => {
        const list = data?.notifications || [];
        const unread = data?.unread_count || 0;
        setNotifications(list);
        setUnreadCount(unread);
        setNotificationsLoaded(true);
      })
      .catch(() => {
        setNotificationsLoaded(true);
      });
  }, []);

  // ----- Search -----
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState(null); // null = hidden, [] = empty, array = items
  const searchTimeoutRef = useRef(null);
  const searchInputRef = useRef(null);

  const performSearch = useCallback((query) => {
    fetch(`${LEGACY_BASE}/api/search.php?q=${encodeURIComponent(query)}`, { credentials: 'include' })
      .then((res) => {
        if (!res.ok) throw new Error('Network error');
        return res.json();
      })
      .then((data) => {
        const members = data?.members || [];
        const committees = data?.committees || [];
        const officers = data?.officers || [];
        setSearchResults({ members, committees, officers });
      })
      .catch(() => {
        setSearchResults({ error: true });
      });
  }, []);

  const handleSearchInput = (e) => {
    const query = e.target.value.trim();
    setSearchQuery(query);
    if (searchTimeoutRef.current) clearTimeout(searchTimeoutRef.current);
    if (query.length < 2) {
      setSearchResults(null);
      return;
    }
    setSearchResults('loading');
    searchTimeoutRef.current = setTimeout(() => performSearch(query), 300);
  };

  const handleSearchKeyDown = (e) => {
    if (e.key === 'Escape') {
      setSearchResults(null);
      if (searchInputRef.current) searchInputRef.current.blur();
    }
  };

  // ----- Dropdown toggles -----
  const [userDropdownOpen, setUserDropdownOpen] = useState(false);
  const [notifDropdownOpen, setNotifDropdownOpen] = useState(false);

  const closeAll = useCallback(() => {
    setUserDropdownOpen(false);
    setNotifDropdownOpen(false);
  }, []);

  useEffect(() => {
    loadNotifications();
  }, [loadNotifications]);

  // Outside click closes dropdowns (matches PHP topbar.php JS sections 4 and 11).
  // PHP closes: search dropdown (line 890-895), user dropdown (line 1083-1087),
  // notification dropdown (line 1088-1092).
  useEffect(() => {
    const onDocClick = (e) => {
      const userMenuEl = document.getElementById('userMenuToggle');
      const userDropEl = document.getElementById('userDropdown');
      const notifBtnEl = document.getElementById('notificationToggle');
      const notifDropEl = document.getElementById('notificationDropdown');
      const searchEl = document.getElementById('globalSearch');
      const searchDropEl = document.getElementById('searchResults');
      const searchContainerEl = searchEl?.closest('.topbar-search');

      if (userDropEl && !userDropEl.contains(e.target) && userMenuEl && !userMenuEl.contains(e.target)) {
        setUserDropdownOpen(false);
      }
      if (notifDropEl && !notifDropEl.contains(e.target) && notifBtnEl && !notifBtnEl.contains(e.target)) {
        setNotifDropdownOpen(false);
      }
      if (searchContainerEl && !searchContainerEl.contains(e.target)) {
        setSearchResults(null);
      }
    };
    document.addEventListener('click', onDocClick);
    return () => document.removeEventListener('click', onDocClick);
  }, []);

  const toggleUserMenu = (e) => {
    e.stopPropagation();
    setUserDropdownOpen((v) => !v);
    setNotifDropdownOpen(false);
  };
  const toggleNotifMenu = (e) => {
    e.stopPropagation();
    setNotifDropdownOpen((v) => !v);
    setUserDropdownOpen(false);
  };

  const handleMarkAllRead = (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setUnreadCount(0);
    setNotifications((items) => items.map((n) => ({ ...n, unread: false })));
  };

  const handleLogout = async () => {
    // Mirror PHP topbar.php: <a href="logout.php">. React route: call authApi.
    try {
      await fetch(`${LEGACY_BASE}/logout.php`, { credentials: 'include' });
    } catch (err) {
      // ignore
    }
    window.location.href = '/MicroFinance/login.php';
  };

  // ----- Render helpers -----
  const [avatarLoadFailed, setAvatarLoadFailed] = useState(false);
  useEffect(() => {
    // Reset the broken-image flag whenever the user changes so a fresh
    // avatar URL gets a chance to load (mirrors PHP's file_exists() check).
    setAvatarLoadFailed(false);
  }, [avatarSrc]);

  const renderUserAvatar = (size = 'small') => (
    <>
      {avatarSrc && !avatarLoadFailed ? (
        <img
          src={avatarSrc}
          alt="Avatar"
          className="avatar-img"
          onError={() => setAvatarLoadFailed(true)}
        />
      ) : (
        <span
          className="avatar-text avatar-fallback"
          style={
            size === 'large'
              ? { background: 'linear-gradient(135deg, #4f46e5, #7c3aed)', color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center', width: '100%', height: '100%', fontWeight: 700, fontSize: 24, borderRadius: '50%' }
              : { background: 'linear-gradient(135deg, #4f46e5, #7c3aed)', color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center', width: '100%', height: '100%', fontWeight: 600, fontSize: 16, borderRadius: '50%' }
          }
        >
          {initials}
        </span>
      )}
    </>
  );

  const roleLabel = userRole.replace(/_/g, ' ');

  return (
    <nav className="topbar">
      <div className="topbar-left">
        <button type="button" className="topbar-toggle" id="sidebarToggle" onClick={handleSidebarToggle} aria-label="Toggle sidebar">
          <i className="fas fa-bars"></i>
        </button>
        <div className="topbar-title">
          <span id="pageTitle">{title}</span>
        </div>
      </div>

      <div className="topbar-right">
        {/* Search */}
        <div className="topbar-search">
          <i className="fas fa-search"></i>
          <input
            ref={searchInputRef}
            type="text"
            id="globalSearch"
            placeholder="Search members, committees..."
            autoComplete="off"
            value={searchQuery}
            onChange={handleSearchInput}
            onKeyDown={handleSearchKeyDown}
          />
          <div className={`search-results${searchResults !== null ? ' show' : ''}`} id="searchResults">
            {searchResults === 'loading' && (
              <div className="search-loading">
                <i className="fas fa-spinner fa-spin"></i> Searching...
              </div>
            )}
            {searchResults && searchResults !== 'loading' && searchResults.error && (
              <div className="search-empty">
                <i className="fas fa-exclamation-circle"></i>
                Error searching. Please try again.
              </div>
            )}
            {searchResults && searchResults !== 'loading' && !searchResults.error && (() => {
              const total = (searchResults.members?.length || 0) + (searchResults.committees?.length || 0) + (searchResults.officers?.length || 0);
              if (total === 0) {
                return (
                  <div className="search-empty">
                    <i className="fas fa-search"></i>
                    No results found for "<strong>{searchQuery}</strong>"
                  </div>
                );
              }
              return (
                <>
                  {(searchResults.members || []).map((item) => (
                    <Link key={`m-${item.member_id}`} to={`/members/${item.member_id}`} className="search-result-item">
                      <div className="result-icon member"><i className="fas fa-user"></i></div>
                      <div className="result-info">
                        <div className="result-name">{escapeHtml(item.full_name)}</div>
                        <div className="result-detail">{escapeHtml(item.member_code)}</div>
                      </div>
                      <span className="result-badge">Member</span>
                    </Link>
                  ))}
                  {(searchResults.committees || []).map((item) => (
                    <a key={`c-${item.committee_id}`} href={`${LEGACY_BASE}/Committees/view.php?id=${item.committee_id}`} className="search-result-item">
                      <div className="result-icon committee"><i className="fas fa-users-cog"></i></div>
                      <div className="result-info">
                        <div className="result-name">{escapeHtml(item.committee_name)}</div>
                        <div className="result-detail">{escapeHtml(item.branch_name || 'N/A')}</div>
                      </div>
                      <span className="result-badge">Committee</span>
                    </a>
                  ))}
                  {(searchResults.officers || []).map((item) => (
                    <a key={`o-${item.user_id}`} href={`${LEGACY_BASE}/profile.php?id=${item.user_id}`} className="search-result-item">
                      <div className="result-icon officer"><i className="fas fa-user-tie"></i></div>
                      <div className="result-info">
                        <div className="result-name">{escapeHtml(item.full_name)}</div>
                        <div className="result-detail">{escapeHtml(item.phone || 'No phone')}</div>
                      </div>
                      <span className="result-badge">Officer</span>
                    </a>
                  ))}
                </>
              );
            })()}
          </div>
        </div>

        {/* Notifications */}
        <div className="topbar-notifications">
          <button type="button" className="notification-btn" id="notificationToggle" onClick={toggleNotifMenu} aria-label="Notifications">
            <i className="fas fa-bell"></i>
            <span
              className="notification-badge"
              id="notifBadge"
              style={unreadCount > 0 ? { display: 'flex' } : { display: 'none' }}
            >
              {unreadCount}
            </span>
          </button>
          <div className={`notification-dropdown${notifDropdownOpen ? ' show' : ''}`} id="notificationDropdown">
            <div className="dropdown-header">
              <span>Notifications</span>
              <a href="#" id="markAllRead" onClick={handleMarkAllRead}>Mark all read</a>
            </div>
            <div className="dropdown-body" id="notificationList">
              {!notificationsLoaded && (
                <div className="search-loading">
                  <i className="fas fa-spinner fa-spin"></i> Loading notifications...
                </div>
              )}
              {notificationsLoaded && notifications.length === 0 && (
                <div className="search-empty">
                  <i className="fas fa-check-circle" style={{ color: '#10b981' }}></i>
                  No notifications
                </div>
              )}
              {notificationsLoaded && notifications.length > 0 && notifications.map((n, idx) => {
                const iconClass = n.icon || 'info';
                const isUnread = n.unread ? 'unread' : '';
                const link = n.link || '#';
                const iconMap = {
                  warning: 'fa-exclamation-triangle',
                  success: 'fa-check-circle',
                  info: 'fa-info-circle',
                  primary: 'fa-bell',
                };
                const faClass = iconMap[iconClass] || 'fa-bell';
                return (
                  <a key={`n-${idx}`} href={link} className={`notification-item ${isUnread}`}>
                    <div className={`notification-icon ${iconClass}`}>
                      <i className={`fas ${faClass}`}></i>
                    </div>
                    <div>
                      <p className="notification-text">{escapeHtml(n.text)}</p>
                      <span className="notification-time">{escapeHtml(n.time)}</span>
                    </div>
                  </a>
                );
              })}
            </div>
            <div className="dropdown-footer">
              <a href="#">View all notifications</a>
            </div>
          </div>
        </div>

        {/* User */}
        <div className="topbar-user">
          <button type="button" className={`user-btn${userDropdownOpen ? ' active' : ''}`} id="userMenuToggle" onClick={toggleUserMenu} title={userName}>
            <div className="user-avatar">{renderUserAvatar('small')}</div>
            <div className="user-info">
              <span className="user-name">{escapeHtml(userName)}</span>
              <span className="user-role">{roleLabel.charAt(0).toUpperCase() + roleLabel.slice(1)}</span>
            </div>
            <i className="fas fa-chevron-down user-arrow"></i>
          </button>

          <div className={`user-dropdown${userDropdownOpen ? ' show' : ''}`} id="userDropdown">
            <div className="dropdown-header">
              <div className="user-avatar-large">{renderUserAvatar('large')}</div>
              <div>
                <p className="dropdown-user-name">{escapeHtml(userName)}</p>
                <p className="dropdown-user-role">{roleLabel.charAt(0).toUpperCase() + roleLabel.slice(1)}</p>
              </div>
            </div>
            <div className="dropdown-divider"></div>
            <div className="dropdown-body">
              <a href={`${LEGACY_BASE}/profile.php`} className="dropdown-item">
                <i className="fas fa-user-circle"></i>
                <span>My Profile</span>
              </a>
              <a href={`${LEGACY_BASE}/profile/edit.php`} className="dropdown-item">
                <i className="fas fa-user-edit"></i>
                <span>Edit Profile</span>
              </a>
              <a href={`${LEGACY_BASE}/profile/change-password.php`} className="dropdown-item">
                <i className="fas fa-key"></i>
                <span>Change Password</span>
              </a>
              <div className="dropdown-divider"></div>

              {userRole === 'field_officer' && (
                <>
                  <a href={`${LEGACY_BASE}/field-officer/dashboard.php`} className="dropdown-item">
                    <i className="fas fa-chart-bar"></i>
                    <span>My Dashboard</span>
                  </a>
                  <a href={`${LEGACY_BASE}/field-officer/members.php`} className="dropdown-item">
                    <i className="fas fa-users"></i>
                    <span>My Members</span>
                  </a>
                  <a href={`${LEGACY_BASE}/field-officer/committees.php`} className="dropdown-item">
                    <i className="fas fa-users-cog"></i>
                    <span>My Committees</span>
                  </a>
                </>
              )}

              {(userRole === 'admin' || userRole === 'branch_manager') && (
                <a href={`${LEGACY_BASE}/Committees/officers/index.php`} className="dropdown-item">
                  <i className="fas fa-user-tie"></i>
                  <span>Field Officers</span>
                </a>
              )}

              <div className="dropdown-divider"></div>
              <a href={`${LEGACY_BASE}/logout.php`} className="dropdown-item logout" onClick={(e) => { e.preventDefault(); handleLogout(); }}>
                <i className="fas fa-sign-out-alt"></i>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </nav>
  );
}