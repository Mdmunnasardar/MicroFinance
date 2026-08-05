import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { profileApi } from '../../api/profileApi';
import { apiClient } from '../../api/client';
import { useAuth } from '../../hooks/useAuth';
import LoadingScreen from '../../components/LoadingScreen';
import '../../assets/css/profile.css';

const FALLBACK_LABEL = 'U';

// Format a raw role string ("admin", "field_officer", "branch_manager") into
// a human label ("Admin", "Field Officer", "Branch Manager"). Single source
// of truth — do NOT hardcode role labels anywhere else.
function roleLabel(role) {
  if (!role) return 'Unknown';
  const map = {
    admin: 'Admin',
    branch_manager: 'Branch Manager',
    field_officer: 'Field Officer',
    member: 'Member',
  };
  if (map[role]) return map[role];
  // Unknown role — fall back to a "Title Cased With Spaces" rendering.
  return String(role)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function initialsFor(name) {
  if (!name) return FALLBACK_LABEL;
  const parts = String(name).trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

// Build a browser-loadable URL for a stored avatar filename. Returns null when
// the filename is missing/empty so callers can decide to render initials.
function avatarUrl(filename) {
  if (!filename) return null;
  const fn = String(filename).trim();
  if (!fn) return null;
  // Already a fully-qualified URL? Use as-is.
  if (/^https?:\/\//i.test(fn)) return fn;
  // Same path used by the PHP legacy code (Topbar.php) and the Vite dev-server
  // proxy, so the file Apache wrote to <project-root>/uploads/avatars/<fn>
  // is reachable from both dev and prod.
  return `/MicroFinance/uploads/avatars/${fn}`;
}

function roleClass(role) {
  return `role-badge ${role || ''}`;
}

function statusClass(isActive) {
  return `status-badge ${isActive ? 'active' : 'inactive'}`;
}

// Format a YYYY-MM-DD / datetime string into "Aug 2026".
function joinedLabel(value) {
  if (!value) return '';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function formatNumber(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '0';
  return n.toLocaleString();
}

function formatMoney(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '$0';
  return `$${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function ProfilePage() {
  const navigate = useNavigate();
  const { user: authUser, setUser } = useAuth();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  // Tracks whether the current <img> failed to load so we can show initials
  // instead of a broken-image icon (mirrors Topbar's behaviour).
  const [avatarLoadFailed, setAvatarLoadFailed] = useState(false);

  // Field-officer-only stats (Total Committees, Total Members, Today's
  // Collection, Total Collection). Loaded only when the user is a field
  // officer so we don't waste a round trip for admins / branch managers.
  const [stats, setStats] = useState(null);
  const [statsLoading, setStatsLoading] = useState(false);

  // Ref so the mount-only effect can read the latest auth user without
  // depending on it (depending on `authUser` would re-run the effect because
  // the effect itself calls setUser(...) — an infinite loop).
  const authUserRef = useRef(authUser);
  authUserRef.current = authUser;

  const syncAuthUser = (next) => {
    const cur = authUserRef.current;
    if (!cur || !next) return;
    const newName = next.full_name ?? cur.name;
    const newAvatar = next.avatar ?? cur.avatar;
    if (newName === cur.name && newAvatar === cur.avatar) return;
    setUser({ ...cur, name: newName, avatar: newAvatar });
  };

  useEffect(() => {
    let cancelled = false;
    profileApi.show()
      .then((data) => {
        if (cancelled) return;
        const next = data?.user ?? data?.data?.user ?? null;
        setProfile(next);
        // Reset the broken-image flag whenever the URL changes so a fresh
        // avatar gets a chance to load.
        setAvatarLoadFailed(false);
        if (next) syncAuthUser(next);
      })
      .catch((err) => {
        if (cancelled) return;
        setError(err?.message || 'Unable to load profile.');
        setProfile(null);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => { cancelled = true; };
    // Mount-only fetch.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Once we know the role, fetch the officer-only stats. We do this in a
  // separate effect so we only fire the network request when the role
  // actually resolves to field_officer.
  useEffect(() => {
    if (loading) return;
    const role = profile?.role ?? authUser?.role ?? null;
    if (role !== 'field_officer') {
      setStats(null);
      return;
    }
    let cancelled = false;
    setStatsLoading(true);
    apiClient
      .get('/field-officers/summary')
      .then((data) => {
        if (cancelled) return;
        // Response shape: { success, data: { summary: { ... } } }
        // The axios interceptor unwraps `response.data`, so the first `.data`
        // is the JSON envelope and the next `.data` is the summary payload.
        const summary = data?.summary ?? data?.data?.summary ?? null;
        setStats(summary);
      })
      .catch(() => {
        // Non-fatal: just hide the stats grid.
        if (!cancelled) setStats(null);
      })
      .finally(() => {
        if (!cancelled) setStatsLoading(false);
      });
    return () => { cancelled = true; };
    // Re-run only when the role actually resolves to field_officer.
  }, [loading, profile?.role, profile?.id, authUser?.role, authUser?.id]);

  if (loading) return <LoadingScreen message="Loading profile…" />;

  if (error && !profile) {
    return (
      <div className="profile-container" style={{ padding: '40px 20px' }}>
        <div className="detail-section">
          <div className="section-title"><i className="fas fa-circle-exclamation" /> Couldn't load profile</div>
          <div className="detail-value">{error}</div>
          <button className="btn btn-primary" onClick={() => navigate('/')} style={{ marginTop: 12 }}>
            Back to dashboard
          </button>
        </div>
      </div>
    );
  }

  // Single source of truth for the displayed role. Read from the freshly
  // fetched profile, fall back to the AuthContext cache (which is the same
  // string the Topbar is rendering), and finally fall back to null.
  const role = profile?.role ?? authUser?.role ?? null;

  // Prefer the freshly-fetched profile's avatar, fall back to the AuthContext
  // user (which the Topbar uploaded into). Either source feeds the same URL
  // builder so a stale DB filename that no longer has a file on disk will
  // gracefully fall through to initials via the <img onError>.
  const avatarFilename = profile?.avatar ?? authUser?.avatar ?? null;
  const avatarSrc = avatarUrl(avatarFilename);
  const displayName = profile?.full_name || authUser?.name || '';
  const fallbackInitials = initialsFor(displayName);

  return (
    <div className="profile-container">
      <div className="profile-header animate-slide-up">
        <div className="profile-top">
          <div className="profile-avatar">
            {avatarSrc && !avatarLoadFailed ? (
              <img
                src={avatarSrc}
                alt="avatar"
                className="avatar-img"
                onError={() => setAvatarLoadFailed(true)}
              />
            ) : (
              <div className="avatar-img">{fallbackInitials}</div>
            )}
          </div>
          <div className="profile-info">
            <h1 className="profile-name">{displayName || 'Unnamed user'}</h1>
            <p className="profile-username">@{profile?.username || authUser?.username || ''}</p>
            <div className="profile-meta">
              <span className={roleClass(role)}>
                <i className="fas fa-id-badge" /> {roleLabel(role)}
              </span>
              <span className={statusClass(profile?.is_active)}>
                <i className={`fas fa-${profile?.is_active ? 'check-circle' : 'ban'}`} />
                {profile?.is_active ? 'Active' : 'Inactive'}
              </span>
              {profile?.branch_name && (
                <span className="meta-item">
                  <i className="fas fa-building" /> {profile.branch_name}
                </span>
              )}
              {profile?.created_at && (
                <span className="meta-item">
                  <i className="fas fa-calendar-alt" /> Joined {joinedLabel(profile.created_at)}
                </span>
              )}
            </div>
          </div>
          <div className="profile-actions">
            <Link to="/profile/edit" className="btn btn-primary btn-sm">
              <i className="fas fa-pen" /> Edit profile
            </Link>
            <Link to="/profile/change-password" className="btn btn-secondary btn-sm">
              <i className="fas fa-key" /> Change password
            </Link>
          </div>
        </div>
      </div>

      {/* ============================================
          FIELD OFFICER STATS - Only for field_officer
          ============================================ */}
      {role === 'field_officer' && (
        <div className="stat-grid animate-slide-up" style={{ animationDelay: '0.1s' }}>
          <div className="stat-card primary">
            <div className="stat-top">
              <div>
                <p className="stat-label">Total Committees</p>
                <p className="stat-number primary-text">{formatNumber(stats?.total_committees)}</p>
              </div>
              <div className="stat-icon primary-icon">
                <i className="fas fa-users-cog" />
              </div>
            </div>
          </div>
          <div className="stat-card success">
            <div className="stat-top">
              <div>
                <p className="stat-label">Total Members</p>
                <p className="stat-number success-text">{formatNumber(stats?.total_members)}</p>
              </div>
              <div className="stat-icon success-icon">
                <i className="fas fa-users" />
              </div>
            </div>
          </div>
          <div className="stat-card warning">
            <div className="stat-top">
              <div>
                <p className="stat-label">Today&rsquo;s Collection</p>
                <p className="stat-number warning-text">{formatMoney(stats?.today_collection)}</p>
              </div>
              <div className="stat-icon warning-icon">
                <i className="fas fa-money-bill-wave" />
              </div>
            </div>
          </div>
          <div className="stat-card purple">
            <div className="stat-top">
              <div>
                <p className="stat-label">Total Collection</p>
                <p className="stat-number purple-text">{formatMoney(stats?.total_collection)}</p>
              </div>
              <div className="stat-icon purple-icon">
                <i className="fas fa-hand-holding-usd" />
              </div>
            </div>
          </div>
          {statsLoading && (
            <p style={{ gridColumn: '1 / -1', textAlign: 'center', color: 'var(--gray-500)', marginTop: 8 }}>
              <i className="fas fa-spinner fa-spin" /> Loading stats…
            </p>
          )}
        </div>
      )}

      <div className="detail-section">
        <div className="section-title">
          <i className="fas fa-id-card" /> Account details
        </div>
        <div className="detail-grid">
          <div className="detail-item">
            <div className="detail-label">Full name</div>
            <div className="detail-value">{profile?.full_name || '—'}</div>
          </div>
          <div className="detail-item">
            <div className="detail-label">Username</div>
            <div className="detail-value">{profile?.username || '—'}</div>
          </div>
          <div className="detail-item">
            <div className="detail-label">Role</div>
            <div className="detail-value">{roleLabel(role)}</div>
          </div>
          <div className="detail-item">
            <div className="detail-label">Phone</div>
            <div className="detail-value">{profile?.phone || '—'}</div>
          </div>
          <div className="detail-item">
            <div className="detail-label">Branch</div>
            <div className="detail-value">{profile?.branch_name || '—'}</div>
          </div>
          <div className="detail-item">
            <div className="detail-label">Account status</div>
            <div className="detail-value">
              <span className={statusClass(profile?.is_active)}>
                {profile?.is_active ? 'Active' : 'Inactive'}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================
          FIELD OFFICER WORK LINKS - Only for field_officer
          ============================================ */}
      {role === 'field_officer' && (
        <div className="detail-section animate-slide-up" style={{ animationDelay: '0.2s' }}>
          <div className="section-title">
            <i className="fas fa-briefcase" /> My Work
          </div>
          <div className="work-grid">
            <Link to="/committees" className="work-item">
              <div className="work-icon primary">
                <i className="fas fa-users-cog" />
              </div>
              <div className="work-info">
                <p className="work-title">Committees</p>
                <p className="work-desc">View my committees</p>
              </div>
            </Link>
            <Link to="/members" className="work-item">
              <div className="work-icon success">
                <i className="fas fa-users" />
              </div>
              <div className="work-info">
                <p className="work-title">Members</p>
                <p className="work-desc">View my members</p>
              </div>
            </Link>
            <Link to="/installments" className="work-item">
              <div className="work-icon warning">
                <i className="fas fa-hand-holding-usd" />
              </div>
              <div className="work-info">
                <p className="work-title">Collections</p>
                <p className="work-desc">Collect payments</p>
              </div>
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}