import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldOfficersApi } from '../../api/fieldOfficersApi';
import { useAuth } from '../../hooks/useAuth';
import LoadingScreen from '../../components/LoadingScreen';
import '../../assets/css/profile.css';

const ALLOWED_ROLES = new Set(['admin', 'branch_manager']);

function statusClass(isActive) {
  return `status-badge ${isActive ? 'active' : 'inactive'}`;
}

export default function FieldOfficersPage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const role = user?.role;
  const hasPermission = ALLOWED_ROLES.has(role);

  const [officers, setOfficers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!hasPermission) {
      setLoading(false);
      return;
    }
    let cancelled = false;
    setLoading(true);
    fieldOfficersApi.list()
      .then((data) => {
        if (cancelled) return;
        setOfficers(data?.field_officers || []);
      })
      .catch((err) => {
        if (cancelled) return;
        setError(err?.message || 'Unable to load field officers.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => { cancelled = true; };
  }, [hasPermission]);

  if (!hasPermission) {
    return (
      <div className="profile-container" style={{ paddingTop: 8 }}>
        <div className="detail-section">
          <div className="section-title">
            <i className="fas fa-lock" /> Access denied
          </div>
          <p style={{ color: 'var(--gray-700)' }}>
            You don't have permission to view field officers. This page is available to
            administrators and branch managers.
          </p>
          <button className="btn btn-primary" onClick={() => navigate('/')} style={{ marginTop: 8 }}>
            <i className="fas fa-arrow-left" /> Back to dashboard
          </button>
        </div>
      </div>
    );
  }

  if (loading) return <LoadingScreen message="Loading field officers…" />;

  return (
    <div className="profile-container" style={{ paddingTop: 8 }}>
      <div className="profile-header animate-slide-up">
        <div className="profile-top">
          <div className="profile-info">
            <h1 className="profile-name" style={{ fontSize: 22 }}>Field officers</h1>
            <p className="profile-username">
              {role === 'admin'
                ? 'All field officers in the system.'
                : 'Field officers in your branch.'}
            </p>
          </div>
          <div className="profile-actions">
            <span className="status-badge active">
              <i className="fas fa-users" /> {officers.length} total
            </span>
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

      <div className="detail-section">
        {officers.length === 0 ? (
          <div style={{ textAlign: 'center', color: 'var(--gray-500)', padding: 24 }}>
            <i className="fas fa-inbox" style={{ fontSize: 24, display: 'block', marginBottom: 8 }} />
            No field officers found.
          </div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ borderBottom: '2px solid var(--gray-200)', textAlign: 'left' }}>
                  <th style={th}>Name</th>
                  <th style={th}>Username</th>
                  <th style={th}>Phone</th>
                  <th style={th}>Branch</th>
                  <th style={th}>Status</th>
                </tr>
              </thead>
              <tbody>
                {officers.map((o) => (
                  <tr key={o.user_id} style={{ borderBottom: '1px solid var(--gray-100)' }}>
                    <td style={td}>
                      <strong>{o.full_name}</strong>
                    </td>
                    <td style={td}>{o.username}</td>
                    <td style={td}>{o.phone || '—'}</td>
                    <td style={td}>{o.branch_name || '—'}</td>
                    <td style={td}>
                      <span className={statusClass(o.is_active)}>
                        {o.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

const th = { padding: '12px 10px', fontSize: 12, fontWeight: 700, color: 'var(--gray-600)', textTransform: 'uppercase', letterSpacing: 0.5 };
const td = { padding: '12px 10px', fontSize: 14, color: 'var(--gray-800)' };