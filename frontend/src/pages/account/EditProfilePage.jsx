import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { profileApi } from '../../api/profileApi';
import { branchesApi } from '../../api/branchesApi';
import { useAuth } from '../../hooks/useAuth';
import LoadingScreen from '../../components/LoadingScreen';
import '../../assets/css/profile.css';

const FIELD_MESSAGES = {
  full_name: { required: 'Full name is required.', max_length: 'Full name is too long (max 100 characters).' },
  phone:     { max_length: 'Phone is too long (max 15 characters).' },
  branch_id: { invalid: 'Branch is invalid.', not_found: 'Selected branch does not exist.' },
};

function messageFor(field, code) {
  return FIELD_MESSAGES[field]?.[code] || `${field} is invalid.`;
}

function initialsFor(name) {
  if (!name) return 'U';
  const parts = String(name).trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function avatarUrl(filename) {
  if (!filename) return null;
  const fn = String(filename).trim();
  if (!fn) return null;
  if (/^https?:\/\//i.test(fn)) return fn;
  return `/MicroFinance/uploads/avatars/${fn}`;
}

export default function EditProfilePage() {
  const navigate = useNavigate();
  const { user: authUser, setUser } = useAuth();
  const [form, setForm] = useState({ full_name: '', phone: '', branch_id: '' });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [info, setInfo] = useState('');
  const [profile, setProfile] = useState(null);
  const [branches, setBranches] = useState([]);
  const [avatarLoadFailed, setAvatarLoadFailed] = useState(false);

  // Keep a ref to the latest AuthContext user so the mount-only effect below
  // can read it without depending on it (depending on `authUser` would create
  // an infinite loop because the effect itself calls setUser(...)).
  const authUserRef = useRef(authUser);
  authUserRef.current = authUser;

  // Sync helper that only updates AuthContext when something actually changed.
  const syncAuthUser = (next) => {
    const cur = authUserRef.current;
    if (!cur) return;
    const newName = next.full_name ?? cur.name;
    const newAvatar = next.avatar ?? cur.avatar;
    if (newName === cur.name && newAvatar === cur.avatar) return;
    setUser({ ...cur, name: newName, avatar: newAvatar });
  };

  useEffect(() => {
    let cancelled = false;

    // Fetch profile + branches in parallel. Any individual failure is reported
    // but does not block the other.
    Promise.allSettled([
      profileApi.show(),
      branchesApi.list(),
    ]).then(([profileRes, branchesRes]) => {
      if (cancelled) return;
      if (profileRes.status === 'fulfilled') {
        const p = profileRes.value?.user ?? profileRes.value?.data?.user ?? null;
        setProfile(p);
        setAvatarLoadFailed(false);
        // Treat null/undefined phone and null branch_id as empty strings so the
        // form always renders. The original DB values can be empty.
        setForm({
          full_name: typeof p?.full_name === 'string' ? p.full_name : '',
          phone:      typeof p?.phone      === 'string' ? p.phone      : '',
          branch_id:  p?.branch_id == null ? '' : String(p.branch_id),
        });
        if (p) syncAuthUser(p);
      } else {
        const msg = profileRes.reason?.message || 'Unable to load profile.';
        setError(msg);
        // Make sure the user is not stuck on the loading screen.
        setProfile(null);
      }
      if (branchesRes.status === 'fulfilled') {
        const list = branchesRes.value?.branches ?? branchesRes.value?.data?.branches ?? [];
        setBranches(Array.isArray(list) ? list : []);
      } else {
        // Branch list failure is non-fatal — render an empty dropdown.
        setBranches([]);
      }
    }).finally(() => {
      if (!cancelled) setLoading(false);
    });

    return () => { cancelled = true; };
    // Mount-only fetch. Do NOT add `authUser` here — it would loop because the
    // effect itself calls setUser(...). See commit history for details.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  function update(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }));
    setFieldErrors((prev) => ({ ...prev, [field]: undefined }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setInfo('');
    setFieldErrors({});

    const clientErrors = {};
    if (!form.full_name.trim()) clientErrors.full_name = 'required';
    if (Object.keys(clientErrors).length > 0) {
      setFieldErrors(clientErrors);
      return;
    }

    setSaving(true);
    try {
      const payload = {
        full_name: form.full_name.trim(),
        phone: form.phone.trim(),
        // Empty string means "no branch". We send null so the server treats it as a clear.
        branch_id: form.branch_id === '' ? null : Number(form.branch_id),
      };
      const data = await profileApi.update(payload);
      const next = data?.user ?? data?.data?.user ?? null;
      if (next) {
        setProfile(next);
        setForm({
          full_name: typeof next.full_name === 'string' ? next.full_name : '',
          phone:      typeof next.phone      === 'string' ? next.phone      : '',
          branch_id:  next.branch_id == null ? '' : String(next.branch_id),
        });
        syncAuthUser(next);
      }
      setInfo('Profile updated successfully.');
      setTimeout(() => navigate('/profile'), 800);
    } catch (err) {
      if (err?.status === 422 && err?.code === 'VALIDATION_ERROR') {
        const fields = err?.details?.fields || {};
        const fe = {};
        for (const [k, v] of Object.entries(fields)) fe[k] = messageFor(k, v);
        setFieldErrors(fe);
        setError(err?.message || 'Please correct the highlighted fields.');
      } else {
        setError(err?.message || 'Could not save changes.');
      }
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <LoadingScreen message="Loading profile…" />;

  const avatarFilename = profile?.avatar ?? authUser?.avatar ?? null;
  const avatarSrc = avatarUrl(avatarFilename);
  const fallbackInitials = initialsFor(profile?.full_name || authUser?.name);

  return (
    <div className="profile-container" style={{ paddingTop: 8 }}>
      <div className="profile-header animate-slide-up">
        <div className="profile-top" style={{ alignItems: 'flex-start' }}>
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
            <h1 className="profile-name" style={{ fontSize: 22 }}>Edit profile</h1>
            <p className="profile-username">@{profile?.username} — username cannot be changed</p>
          </div>
          <div className="profile-actions">
            <button type="button" className="btn btn-secondary btn-sm" onClick={() => navigate('/profile')}>
              <i className="fas fa-arrow-left" /> Cancel
            </button>
          </div>
        </div>
      </div>

      {error && (
        <div className="detail-section" style={{ borderLeft: '4px solid var(--danger)' }}>
          <div className="detail-value" style={{ color: 'var(--danger-dark)' }}>
            <i className="fas fa-circle-exclamation" /> {error}
          </div>
        </div>
      )}
      {info && (
        <div className="detail-section" style={{ borderLeft: '4px solid var(--success)' }}>
          <div className="detail-value" style={{ color: 'var(--success-dark)' }}>
            <i className="fas fa-circle-check" /> {info}
          </div>
        </div>
      )}

      <form className="detail-section" onSubmit={handleSubmit} noValidate>
        <div className="section-title"><i className="fas fa-user-pen" /> Your details</div>

        <div className="form-group">
          <label className="form-label">Username</label>
          <input className="form-control" type="text" value={profile?.username || ''} disabled />
          <small style={{ color: 'var(--gray-500)' }}>Username cannot be changed.</small>
        </div>

        <div className="form-row">
          <div className="form-group">
            <label className="form-label">
              Full name <span className="required">*</span>
            </label>
            <input
              className="form-control"
              type="text"
              value={form.full_name}
              maxLength={100}
              onChange={(e) => update('full_name', e.target.value)}
              required
            />
            {fieldErrors.full_name && (
              <small style={{ color: 'var(--danger-dark)' }}>{fieldErrors.full_name}</small>
            )}
          </div>

          <div className="form-group">
            <label className="form-label">Phone</label>
            <input
              className="form-control"
              type="text"
              value={form.phone}
              maxLength={15}
              onChange={(e) => update('phone', e.target.value)}
              placeholder="e.g. 01711111111"
            />
            {fieldErrors.phone && (
              <small style={{ color: 'var(--danger-dark)' }}>{fieldErrors.phone}</small>
            )}
          </div>
        </div>

        <div className="form-group">
          <label className="form-label" htmlFor="branch_id">Branch</label>
          <select
            id="branch_id"
            className="form-control"
            value={form.branch_id === null ? '' : (form.branch_id ?? '')}
            onChange={(e) => update('branch_id', e.target.value)}
          >
            <option value="">— No branch —</option>
            {branches.map((b) => (
              <option key={b.branch_id} value={b.branch_id}>
                {b.branch_name}{b.is_active ? '' : ' (inactive)'}
              </option>
            ))}
          </select>
          <small style={{ color: 'var(--gray-500)' }}>
            Pick the branch you belong to. Choose "No branch" to clear it.
          </small>
          {fieldErrors.branch_id && (
            <small style={{ color: 'var(--danger-dark)', display: 'block' }}>{fieldErrors.branch_id}</small>
          )}
        </div>

        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <button type="button" className="btn btn-secondary" onClick={() => navigate('/profile')}>
            Cancel
          </button>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Saving…' : (<><i className="fas fa-save" /> Save changes</>)}
          </button>
        </div>
      </form>
    </div>
  );
}