// MemberFormPage — handles both /members/new (add) and /members/:id/edit (edit).
// Detects mode from URL params, fetches the existing member record for edit,
// loads committees/branches for the dropdowns, and submits via membersApi.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { membersApi } from '../api/membersApi';
import MemberForm from '../components/members/MemberForm';

export default function MemberFormPage({ mode }) {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = mode === 'edit' && id;

  const [member, setMember] = useState(null);
  const [committees, setCommittees] = useState([]);
  const [branches, setBranches] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    document.title = `${isEdit ? 'Edit' : 'Add'} Member · MicroFinance`;
  }, [isEdit]);

  // Fetch the existing member (edit) and the dropdown lists (both modes).
  const loadData = useCallback(async () => {
    setError('');
    try {
      const listResponse = await membersApi.list({ page: 1, per_page: 1 });
      const filters = listResponse.meta?.filters || {};
      setCommittees(Array.isArray(filters.committees) ? filters.committees : []);
      setBranches(Array.isArray(filters.branches) ? filters.branches : []);
    } catch (err) {
      // Non-fatal — show empty dropdowns if the list endpoint hiccups.
      setCommittees((c) => c);
      setBranches((b) => b);
    }

    if (!isEdit) {
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      const response = await membersApi.get(id);
      const data = response?.data ?? response;
      setMember(data?.member || data || null);
    } catch (err) {
      setError(err.message || 'Failed to load member.');
    } finally {
      setLoading(false);
    }
  }, [id, isEdit]);

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

  const handleSubmit = async (payload) => {
    setSubmitting(true);
    setError('');
    try {
      if (isEdit) {
        await membersApi.update(id, payload);
      } else {
        await membersApi.create(payload);
      }
      navigate('/members', {
        state: {
          feedback: {
            kind: 'success',
            message: isEdit
              ? `Member "${payload.full_name}" updated successfully.`
              : `Member "${payload.full_name}" added successfully.`,
          },
        },
      });
    } catch (err) {
      setError(err.message || 'Failed to save member.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="members-page">
        <Link to="/members" className="back-btn">
          <i className="fa-solid fa-arrow-left"></i> Back to Members
        </Link>
        <h1 className="page-title">
          <i className={isEdit ? 'fa-solid fa-user-edit' : 'fa-solid fa-user-plus'}></i>
          {isEdit ? 'Edit Member' : 'Add New Member'}
        </h1>
        <div className="members-loading">
          <i className="fa-solid fa-spinner fa-spin"></i> Loading…
        </div>
      </div>
    );
  }

  return (
    <div className="members-page">
      <Link to="/members" className="back-btn">
        <i className="fa-solid fa-arrow-left"></i> Back to Members
      </Link>

      <h1 className="page-title">
        <i className={isEdit ? 'fa-solid fa-user-edit' : 'fa-solid fa-user-plus'}></i>
        {isEdit ? 'Edit Member' : 'Add New Member'}
      </h1>

      <div className="card">
        <div className="card-body">
          <MemberForm
            initialValue={member}
            committees={committees}
            branches={branches}
            busy={submitting}
            error={error}
            mode={isEdit ? 'edit' : 'create'}
            onSubmit={handleSubmit}
          />
        </div>
      </div>
    </div>
  );
}