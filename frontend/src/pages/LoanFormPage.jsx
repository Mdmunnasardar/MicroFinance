// LoanFormPage — handles /loans/new (add) and /loans/:id/edit (edit).
// Mirrors loans/add.php and loans/edit.php. Loads members for the dropdown
// and the existing loan for edit. Returns to the list with sessionStorage
// feedback so the redirect banner shows.

import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { loansApi } from '../api/loansApi';
import LoanForm from '../components/loans/LoanForm';

export default function LoanFormPage({ mode }) {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = mode === 'edit' && id;

  const [loan, setLoan] = useState(null);
  const [members, setMembers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    document.title = `${isEdit ? 'Edit Loan' : 'Create Loan'} · MicroFinance`;
  }, [isEdit]);

  const load = useCallback(async () => {
    setError('');
    try {
      const listResponse = await loansApi.list({ page: 1, per_page: 1 });
      const filters = listResponse.meta?.filters || {};
      setMembers(Array.isArray(filters.members) ? filters.members : []);
    } catch (err) {
      // non-fatal
      setMembers((m) => m);
    }

    if (!isEdit) {
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      const response = await loansApi.get(id);
      const data = response?.data ?? response;
      setLoan(data?.loan || data || null);
    } catch (err) {
      setError(err.message || 'Failed to load loan.');
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
        await loansApi.update(id, payload);
      } else {
        await loansApi.create(payload);
      }
      window.sessionStorage.setItem(
        'loans_feedback',
        JSON.stringify({
          kind: 'success',
          message: isEdit
            ? `Loan "${payload.loan_code}" updated successfully.`
            : `Loan "${payload.loan_code}" created successfully.`,
        }),
      );
      navigate(isEdit ? `/loans/${id}` : '/loans');
    } catch (err) {
      setError(err.message || 'Failed to save loan.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="loans-page">
        <div className="container" style={{ padding: 20 }}>
          <div className="loans-loading">
            <i className="fa-solid fa-spinner fa-spin"></i> Loading…
          </div>
          <div style={{ marginTop: 16, textAlign: 'center' }}>
            <Link to="/loans" className="btn btn-secondary">
              <i className="fa-solid fa-arrow-left"></i> Back to Loans
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <LoanForm
      initialValue={loan}
      members={members}
      busy={submitting}
      error={error}
      mode={isEdit ? 'edit' : 'create'}
      onSubmit={handleSubmit}
    />
  );
}