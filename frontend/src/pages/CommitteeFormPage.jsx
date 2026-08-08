// CommitteeFormPage — handles /committees/new (add) and /committees/:id/edit (edit).
// Detects mode from route param, fetches drop-downs + committee via API for
// edit, submits via committeesApi. Returns to the list page with feedback
// via sessionStorage (the list page reads it on mount).

import '../assets/css/committees.css';
import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { committeesApi } from '../api/committeesApi';
import CommitteeForm from '../components/committees/CommitteeForm';

export default function CommitteeFormPage({ mode }) {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = mode === 'edit' && id;

  const [committee, setCommittee] = useState(null);
  const [branches, setBranches] = useState([]);
  const [officers, setOfficers] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    document.title = `${isEdit ? 'Edit' : 'Add'} Committee · MicroFinance`;
  }, [isEdit]);

  const load = useCallback(async () => {
    setError('');
    try {
      const listResponse = await committeesApi.list({});
      const filters = listResponse.meta?.filters || {};
      setBranches(Array.isArray(filters.branches) ? filters.branches : []);
      setOfficers(Array.isArray(filters.officers) ? filters.officers : []);
    } catch (err) {
      // Non-fatal — show empty dropdowns if list endpoint hiccups.
      setBranches((b) => b);
      setOfficers((o) => o);
    }

    if (!isEdit) {
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      const response = await committeesApi.get(id);
      const data = response?.data ?? response;
      setCommittee(data?.committee || data || null);
    } catch (err) {
      setError(err.message || 'Failed to load committee.');
    } finally {
      setLoading(false);
    }
  }, [id, isEdit]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      load();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [load]);

  const handleSubmit = async (payload) => {
    setSubmitting(true);
    setError('');
    try {
      if (isEdit) {
        await committeesApi.update(id, payload);
      } else {
        await committeesApi.create(payload);
      }
      window.sessionStorage.setItem(
        'committees_feedback',
        JSON.stringify({
          kind: 'success',
          message: isEdit
            ? `Committee "${payload.committee_name}" updated successfully.`
            : `Committee "${payload.committee_name}" added successfully.`,
        }),
      );
      navigate(isEdit ? `/committees/${id}` : '/committees');
    } catch (err) {
      setError(err.message || 'Failed to save committee.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="committees-page">
        <div className="page-header">
          <div className="header-left">
            <div className="header-icon primary">
              <i className={isEdit ? 'fa-solid fa-edit' : 'fa-solid fa-plus-circle'}></i>
            </div>
            <div>
              <h1 className="header-title">{isEdit ? 'Edit Committee' : 'Add Committee'}</h1>
              <p className="header-subtitle">Loading…</p>
            </div>
          </div>
          <div className="header-actions">
            <Link to="/committees" className="btn btn-secondary">
              <i className="fa-solid fa-arrow-left"></i> Back to Committees
            </Link>
          </div>
        </div>
        <div className="loading-state">
          <i className="fa-solid fa-spinner fa-spin"></i> Loading…
        </div>
      </div>
    );
  }

  return (
    <div className="committees-page">
      <div className="page-header">
        <div className="header-left">
          <div className="header-icon primary">
            <i className={isEdit ? 'fa-solid fa-edit' : 'fa-solid fa-plus-circle'}></i>
          </div>
          <div>
            <h1 className="header-title">{isEdit ? 'Edit Committee' : 'Add Committee'}</h1>
            <p className="header-subtitle">
              {isEdit ? 'Update committee information' : 'Create a new committee'}
            </p>
          </div>
        </div>
        <div className="header-actions">
          <Link to="/committees" className="btn btn-secondary">
            <i className="fa-solid fa-arrow-left"></i> Back to Committees
          </Link>
        </div>
      </div>

      <CommitteeForm
        initialValue={committee}
        branches={branches}
        officers={officers}
        busy={submitting}
        error={error}
        mode={isEdit ? 'edit' : 'create'}
        onSubmit={handleSubmit}
      />
    </div>
  );
}
