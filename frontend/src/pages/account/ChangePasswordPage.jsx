import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { profileApi } from '../../api/profileApi';
import '../../assets/css/profile.css';

const FIELD_MESSAGES = {
  current_password: { required: 'Current password is required.', incorrect: 'Current password is incorrect.' },
  new_password:     { required: 'New password is required.', min_length: 'New password must be at least 6 characters.' },
  confirm_password: { required: 'Please confirm the new password.', mismatch: 'Passwords do not match.' },
};

function messageFor(field, code) {
  return FIELD_MESSAGES[field]?.[code] || `${field} is invalid.`;
}

export default function ChangePasswordPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ current_password: '', new_password: '', confirm_password: '' });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [info, setInfo] = useState('');
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  function update(field, value) {
    setForm((p) => ({ ...p, [field]: value }));
    setFieldErrors((p) => ({ ...p, [field]: undefined }));
    setError('');
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setInfo('');
    setFieldErrors({});

    // Client-side guards first.
    const clientErrors = {};
    if (!form.current_password) clientErrors.current_password = 'required';
    if (!form.new_password) clientErrors.new_password = 'required';
    else if (form.new_password.length < 6) clientErrors.new_password = 'min_length';
    if (!form.confirm_password) clientErrors.confirm_password = 'required';
    else if (form.confirm_password !== form.new_password) clientErrors.confirm_password = 'mismatch';

    if (Object.keys(clientErrors).length > 0) {
      setFieldErrors(clientErrors);
      setError('Please correct the highlighted fields.');
      return;
    }

    setSaving(true);
    try {
      await profileApi.changePassword({
        current_password: form.current_password,
        new_password: form.new_password,
        confirm_password: form.confirm_password,
      });
      setInfo('Password updated successfully.');
      setForm({ current_password: '', new_password: '', confirm_password: '' });
    } catch (err) {
      if (err?.status === 422 && err?.code === 'VALIDATION_ERROR') {
        const fields = err?.details?.fields || {};
        const fe = {};
        for (const [k, v] of Object.entries(fields)) fe[k] = messageFor(k, v);
        setFieldErrors(fe);
        setError(err?.message || 'Please correct the highlighted fields.');
      } else {
        setError(err?.message || 'Could not update password.');
      }
    } finally {
      setSaving(false);
    }
  }

  const eyeIcon = (visible) => visible ? 'fa-eye-slash' : 'fa-eye';

  return (
    <div className="profile-container" style={{ paddingTop: 8 }}>
      <div className="profile-header animate-slide-up">
        <div className="profile-top" style={{ alignItems: 'flex-start' }}>
          <div className="profile-info">
            <h1 className="profile-name" style={{ fontSize: 22 }}>Change password</h1>
            <p className="profile-username">Use a strong password you don't reuse elsewhere.</p>
          </div>
          <div className="profile-actions">
            <button type="button" className="btn btn-secondary btn-sm" onClick={() => navigate('/profile')}>
              <i className="fas fa-arrow-left" /> Back to profile
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
        <div className="section-title"><i className="fas fa-lock" /> Update your password</div>

        <PasswordField
          id="current_password"
          label="Current password"
          value={form.current_password}
          onChange={(v) => update('current_password', v)}
          visible={showCurrent}
          onToggle={() => setShowCurrent((s) => !s)}
          error={fieldErrors.current_password}
          autoComplete="current-password"
          required
        />
        <PasswordField
          id="new_password"
          label="New password"
          value={form.new_password}
          onChange={(v) => update('new_password', v)}
          visible={showNew}
          onToggle={() => setShowNew((s) => !s)}
          error={fieldErrors.new_password}
          autoComplete="new-password"
          hint="Minimum 6 characters."
          required
        />
        <PasswordField
          id="confirm_password"
          label="Confirm new password"
          value={form.confirm_password}
          onChange={(v) => update('confirm_password', v)}
          visible={showConfirm}
          onToggle={() => setShowConfirm((s) => !s)}
          error={fieldErrors.confirm_password}
          autoComplete="new-password"
          required
        />

        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 8 }}>
          <button type="button" className="btn btn-secondary" onClick={() => navigate('/profile')}>
            Cancel
          </button>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Updating…' : (<><i className="fas fa-key" /> Update password</>)}
          </button>
        </div>
      </form>
    </div>
  );
}

function PasswordField({ id, label, value, onChange, visible, onToggle, error, autoComplete, hint, required }) {
  return (
    <div className="form-group">
      <label className="form-label" htmlFor={id}>
        {label} {required && <span className="required">*</span>}
      </label>
      <div style={{ position: 'relative' }}>
        <input
          id={id}
          className="form-control"
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          autoComplete={autoComplete}
          required={required}
          style={{ paddingRight: 44 }}
        />
        <button
          type="button"
          onClick={onToggle}
          aria-label={visible ? 'Hide password' : 'Show password'}
          style={{
            position: 'absolute',
            right: 8,
            top: '50%',
            transform: 'translateY(-50%)',
            background: 'transparent',
            border: 'none',
            cursor: 'pointer',
            color: 'var(--gray-500)',
            padding: 6,
          }}
        >
          <i className={`fas ${visible ? 'fa-eye-slash' : 'fa-eye'}`} />
        </button>
      </div>
      {hint && !error && (
        <small style={{ color: 'var(--gray-500)' }}>{hint}</small>
      )}
      {error && (
        <small style={{ color: 'var(--danger-dark)' }}>{error}</small>
      )}
    </div>
  );
}