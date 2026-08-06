// DueSystemOverduePage — mirrors due_system/overdue.php. Active loans with
// remaining > 0, displayed in a 3-column table (Loan, Member, Remaining).

import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { dueSystemApi } from '../api/dueSystemApi';

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function escapeHtml(text) {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export default function DueSystemOverduePage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ count: 0, total_overdue: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => { document.title = 'Overdue Loans · MicroFinance'; }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await dueSystemApi.overdue();
      setItems(Array.isArray(response.data) ? response.data : []);
      setMeta(response.meta || { count: 0, total_overdue: 0 });
    } catch (err) {
      setError(err.message || 'Failed to load overdue loans.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => { if (active) load(); }, 0);
    return () => { active = false; clearTimeout(timer); };
  }, [load]);

  return (
    <div className="due-page">
      <div className="container">
        <div className="page-header">
          <div className="header-left">
            <h3 className="page-title"><i className="fa-solid fa-triangle-exclamation"></i> Overdue Loans</h3>
            <p className="page-subtitle">Active loans with remaining balance &gt; 0</p>
          </div>
          <div className="header-right">
            <Link to="/due-system" className="due-btn secondary">
              <i className="fa-solid fa-arrow-left"></i> Back
            </Link>
          </div>
        </div>

        <div className="due-stats-grid">
          <div className="due-stat-card">
            <div className="due-stat-icon red"><i className="fa-solid fa-list"></i></div>
            <div className="due-stat-label">Overdue Loans</div>
            <div className="due-stat-value">{meta.count || items.length}</div>
            <div className="due-stat-meta"><i className="fa-solid fa-circle-info"></i> Active status only</div>
          </div>
          <div className="due-stat-card">
            <div className="due-stat-icon"><i className="fa-solid fa-coins"></i></div>
            <div className="due-stat-label">Total Overdue</div>
            <div className="due-stat-value">{formatMoney(meta.total_overdue)}</div>
            <div className="due-stat-meta"><i className="fa-solid fa-triangle-exclamation"></i> Outstanding</div>
          </div>
        </div>

        {error ? (
          <div className="due-alert" role="alert">
            <i className="fa-solid fa-circle-exclamation"></i> {error}
          </div>
        ) : null}

        <div className="due-card">
          <div className="due-card-header">
            <h5><i className="fa-solid fa-list"></i> Overdue Loans</h5>
            <span className="due-card-count">{items.length} total</span>
          </div>
          <div className="due-table-responsive">
            {loading ? (
              <div className="due-loading">
                <i className="fa-solid fa-spinner fa-spin"></i> Loading…
              </div>
            ) : items.length === 0 ? (
              <div className="due-empty">
                <i className="fa-solid fa-circle-check"></i>
                <p>No overdue loans</p>
              </div>
            ) : (
              <table className="due-table">
                <thead>
                  <tr>
                    <th>Loan</th>
                    <th>Member</th>
                    <th>Remaining</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((row) => (
                    <tr key={row.loan_id}>
                      <td><strong>{escapeHtml(row.loan_code)}</strong></td>
                      <td>{escapeHtml(row.full_name || 'N/A')}</td>
                      <td className="due-remaining danger">
                        <strong>{formatMoney(row.remaining)}</strong>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}