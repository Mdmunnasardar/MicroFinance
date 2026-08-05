import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { profileApi } from '../../api/profileApi';
import { useAuth } from '../../hooks/useAuth';
import LoadingScreen from '../../components/LoadingScreen';
import '../../assets/css/profile.css';

const FALLBACK_LABEL = 'U';

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

export default function ProfilePage() {
  const navigate = useNavigate();
  const { user: authUser, setUser } = useAuth();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  // Tracks whether the current <img> failed to load so we can show initials
  // instead of a broken-image icon (mirrors Topbar's behaviour).
  const [avatarLoadFailed, setAvatarLoadFailed] = useState(false);

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
              <span className={roleClass(profile?.role || authUser?.role)}>
                <i className="fas fa-id-badge" /> {profile?.role || authUser?.role || 'unknown'}
              </span>
              <span className={statusClass(profile?.is_active)}>
                <i className={`fas fa-${profile?.is_active ? 'check-circle' : 'ban'}`} />
                {profile?.is_active ? 'Active' : 'Inactive'}
              </span>
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
            <div className="detail-value" style={{ textTransform: 'capitalize' }}>{profile?.role || '—'}</div>
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
    </div>
  );
}