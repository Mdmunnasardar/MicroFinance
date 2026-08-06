// DueSystemReportPage — mirrors due_system/report.php. Card with total
// loan, total paid, and remaining due amounts.

import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { dueSystemApi } from '../api/dueSystemApi';

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function DueSystemReportPage() {
  const [data, setData] = useState({ total_loan: 0, total_paid: 0, remaining_due: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => { document.title = 'Due Summary Report · MicroFinance'; }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await dueSystemApi.report();
      setData(response.data || { total_loan: 0, total_paid: 0, remaining_due: 0 });
    } catch (err) {
      setError(err.message || 'Failed to load due report.');
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
            <h3 className="page-title"><i className="fa-solid fa-chart-pie"></i> Due Summary Report</h3>
            <p className="page-subtitle">Aggregate portfolio totals across all loans</p>
          </div>
          <div className="header-right">
            <Link to="/due-system" className="due-btn secondary">
              <i className="fa-solid fa-arrow-left"></i> Back
            </Link>
          </div>
        </div>

        {error ? (
          <div className="due-alert" role="alert">
            <i className="fa-solid fa-circle-exclamation"></i> {error}
          </div>
        ) : null}

        {loading ? (
          <div className="due-loading">
            <i className="fa-solid fa-spinner fa-spin"></i> Loading…
          </div>
        ) : (
          <div className="due-report-card">
            <div className="due-report-row">
              <span className="due-report-label"><i className="fa-solid fa-coins"></i> Total Loan</span>
              <span className="due-report-value">{formatMoney(data.total_loan)}</span>
            </div>
            <div className="due-report-row">
              <span className="due-report-label"><i className="fa-solid fa-check-circle"></i> Total Paid</span>
              <span className="due-report-value">{formatMoney(data.total_paid)}</span>
            </div>
            <div className="due-report-row danger">
              <span className="due-report-label"><i className="fa-solid fa-triangle-exclamation"></i> Remaining Due</span>
              <span className="due-report-value">{formatMoney(data.remaining_due)}</span>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}