// CommitteeMembersPage — /committees/:id/members route. Mirrors the PHP
// Committees/assign-member.php page. Loads committee + current/available members
// via the JSON API, supports single bulk assign + single remove, and renders
// success/error feedback inline.

import '../assets/css/committees.css';
import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { committeesApi } from '../api/committeesApi';
import CommitteeAssignMember from '../components/committees/CommitteeAssignMember';

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export default function CommitteeMembersPage() {
  const { id } = useParams();
  const [committee, setCommittee] = useState(null);
  const [current, setCurrent] = useState([]);
  const [available, setAvailable] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);

  useEffect(() => { document.title = 'Manage Members · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [committeeResponse, membersResponse] = await Promise.all([
        committeesApi.get(id),
        committeesApi.members(id),
      ]);

      const committeeData = committeeResponse?.data ?? committeeResponse;
      setCommittee(committeeData?.committee || null);

      const membersData = membersResponse?.data ?? membersResponse;
      setCurrent(Array.isArray(membersData?.current) ? membersData.current : []);
      setAvailable(Array.isArray(membersData?.available) ? membersData.available : []);
    } catch (err) {
      setError(err.message || 'Failed to load members.');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      loadData();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadData]);

  const handleAssignBulk = async (memberIds) => {
    setBusy(true);
    try {
      await committeesApi.addMember(id, { member_ids: memberIds });
      setFeedback({ kind: 'success', message: `${memberIds.length} member(s) assigned successfully!` });
      await loadData();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to assign members.' });
    } finally {
      setBusy(false);
    }
  };

  const handleRemoveSingle = async (member) => {
    setBusy(true);
    try {
      await committeesApi.removeMember(id, member.member_id);
      setFeedback({ kind: 'success', message: 'Member removed successfully!' });
      await loadData();
    } catch (err) {
      setFeedback({ kind: 'error', message: err.message || 'Failed to remove member.' });
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return (
      <div className="committees-page">
        <div className="loading-state">
          <i className="fa-solid fa-spinner fa-spin"></i> Loading…
        </div>
      </div>
    );
  }

  if (error && !committee) {
    return (
      <div className="committees-page">
        <div className="page-header">
          <div className="header-left">
            <div className="header-icon primary">
              <i className="fa-solid fa-user-plus"></i>
            </div>
            <div>
              <h1 className="header-title">Manage Members</h1>
              <p className="header-subtitle">Could not load committee</p>
            </div>
          </div>
          <div className="header-actions">
            <Link to="/committees" className="btn btn-secondary">
              <i className="fa-solid fa-arrow-left"></i> Back
            </Link>
          </div>
        </div>
        <div className="empty-state">
          <div className="empty-icon"><i className="fa-solid fa-triangle-exclamation"></i></div>
          <h3 className="empty-title">Unable to load members</h3>
          <p className="empty-description">{error}</p>
          <Link to="/committees" className="btn btn-primary">
            <i className="fa-solid fa-arrow-left"></i> Back to list
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="committees-page">
      {feedback ? (
        <div className={`toast ${feedback.kind}`} role="status">
          <i className={`fa-solid ${feedback.kind === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`}></i>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      {/* Page header — mirrors assign-member.php lines 102-123 */}
      <div className="page-header animate-slide-up">
        <div className="header-left">
          <div className="header-icon primary">
            <i className="fa-solid fa-user-plus"></i>
          </div>
          <div>
            <h1 className="header-title">Manage Members</h1>
            <p className="header-subtitle">
              <Link
                to={`/committees/${committee?.committee_id || ''}`}
                style={{ color: 'var(--c-primary)', textDecoration: 'none', fontWeight: 600 }}
              >
                {escapeHtml(committee?.committee_name || '')}
              </Link>
            </p>
          </div>
        </div>
        <div className="header-actions">
          {committee ? (
            <Link to={`/committees/${committee.committee_id}`} className="btn btn-secondary">
              <i className="fa-solid fa-arrow-left"></i> Back
            </Link>
          ) : null}
        </div>
      </div>

      {/* Two-column layout */}
      <CommitteeAssignMember
        current={current}
        available={available}
        busy={busy}
        onAssignBulk={handleAssignBulk}
        onRemoveSingle={handleRemoveSingle}
      />
    </div>
  );
}
