import { useCallback, useEffect, useMemo, useState } from 'react';
import { installmentsApi } from '../api/installmentsApi';
import InstallmentsFilters from '../components/installments/InstallmentsFilters';
import InstallmentsTable from '../components/installments/InstallmentsTable';
import InstallmentForm from '../components/installments/InstallmentForm';

const DEFAULT_FILTERS = { q: '', status: '' };

function buildStats(items) {
  const summary = { total: 0, pending: 0, paid: 0, dueTotal: 0, paidTotal: 0, balanceTotal: 0 };
  for (const item of items || []) {
    summary.total += 1;
    if (item.status === 'paid') summary.paid += 1;
    else summary.pending += 1;
    summary.dueTotal += Number(item.dueAmount) || 0;
    summary.paidTotal += Number(item.paidAmount) || 0;
    summary.balanceTotal += Number(item.balance) || 0;
  }
  return summary;
}

function formatMoney(value) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(Number(value) || 0);
}

function StatPill({ label, value, tone }) {
  return (
    <div className="stat-card">
      <div className="stat-label">{label}</div>
      <div className="stat-value">{value}</div>
      <div className={`stat-trend ${tone}`}>{tone === 'up' ? 'Healthy' : tone === 'down' ? 'Outstanding' : 'Neutral'}</div>
    </div>
  );
}

export default function InstallmentsPage() {
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ total: 0 });
  const [filters, setFilters] = useState(DEFAULT_FILTERS);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState('pay');
  const [activeInstallment, setActiveInstallment] = useState(null);
  const [modalError, setModalError] = useState('');
  const [busy, setBusy] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(null);

  useEffect(() => { document.title = 'Installments · MicroFinance'; }, []);

  const loadInstallments = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = {};
      if (filters.q.trim()) params.q = filters.q.trim();
      if (filters.status) params.status = filters.status;
      const response = await installmentsApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      setMeta(response.meta || { total: 0 });
    } catch (err) {
      setError(err.message || 'Failed to load installments.');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      if (!active) return;
      loadInstallments();
    }, 0);
    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [loadInstallments]);

  const stats = useMemo(() => buildStats(items), [items]);

  const openModal = (mode, installment) => {
    setActiveInstallment(installment);
    setModalMode(mode);
    setModalError('');
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
    setActiveInstallment(null);
    setModalError('');
  };

  const handleSubmit = async (payload) => {
    if (!activeInstallment) return;
    setBusy(true);
    setModalError('');
    try {
      await installmentsApi.update(activeInstallment.id, payload);
      setFeedback(`Installment #${activeInstallment.installmentNo} updated.`);
      closeModal();
      await loadInstallments();
    } catch (err) {
      setModalError(err.message || 'Failed to save installment.');
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async () => {
    if (!confirmDelete) return;
    setBusy(true);
    try {
      await installmentsApi.remove(confirmDelete.id);
      setFeedback(`Installment #${confirmDelete.installmentNo} deleted.`);
      setConfirmDelete(null);
      await loadInstallments();
    } catch (err) {
      setError(err.message || 'Failed to delete installment.');
      setConfirmDelete(null);
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <div className="welcome">
        <div>
          <h1>Installments</h1>
          <p>Track scheduled loan installments and their payment status.</p>
        </div>
        <div className="quick-actions">
          <a className="btn-quick btn-quick-primary" href="http://localhost/MicroFinance/installments/payment.php">+ Collect Payment</a>
          <a className="btn-quick btn-quick-info" href="http://localhost/MicroFinance/installments/payment_list.php">View All Payments</a>
        </div>
      </div>

      {feedback ? (
        <div className="status-feedback success" role="status">{feedback}</div>
      ) : null}

      <div className="stats-grid">
        <StatPill label="Total installments" value={stats.total} tone="up" />
        <StatPill label="Pending" value={stats.pending} tone="down" />
        <StatPill label="Paid" value={stats.paid} tone="up" />
        <StatPill label="Outstanding balance" value={formatMoney(stats.balanceTotal)} tone="down" />
      </div>

      <div className="card">
        <div className="card-header">
          <h3>Installment schedule</h3>
          <span className="badge-bg">Total: {meta.total ?? stats.total}</span>
        </div>
        <InstallmentsFilters
          filters={filters}
          onChange={setFilters}
          onReset={() => setFilters(DEFAULT_FILTERS)}
        />
        {error ? (
          <div className="error-banner" style={{ background: 'rgba(239, 68, 68, 0.08)', borderColor: '#fecaca', color: '#991b1b' }}>
            {error}
            <button type="button" className="btn-quick btn-quick-primary" style={{ marginLeft: 12 }} onClick={loadInstallments}>
              Retry
            </button>
          </div>
        ) : null}
        {loading ? <p>Loading installments…</p> : <InstallmentsTable items={items} onPay={(item) => openModal('pay', item)} onEdit={(item) => openModal('edit', item)} onDelete={(item) => setConfirmDelete(item)} />}
      </div>

      <InstallmentForm
        open={modalOpen}
        mode={modalMode}
        installment={activeInstallment}
        busy={busy}
        error={modalError}
        onSubmit={handleSubmit}
        onClose={closeModal}
      />

      {confirmDelete ? (
        <div className="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="confirm-delete-title">
          <div className="modal-card">
            <header className="modal-header">
              <h3 id="confirm-delete-title">Delete installment?</h3>
              <button type="button" className="modal-close" onClick={() => setConfirmDelete(null)} aria-label="Close">×</button>
            </header>
            <div className="modal-subtitle">
              {confirmDelete.member?.name} · Loan {confirmDelete.loan?.code || `#${confirmDelete.loanId}`} · Installment #{confirmDelete.installmentNo}
            </div>
            <div className="modal-body">
              <p>This action cannot be undone. Only unpaid installments can be deleted.</p>
              <div className="modal-actions">
                <button type="button" className="btn-quick" onClick={() => setConfirmDelete(null)} disabled={busy}>Cancel</button>
                <button type="button" className="btn-primary" style={{ background: 'linear-gradient(135deg, #ef4444, #f43f5e)' }} onClick={handleDelete} disabled={busy}>
                  {busy ? 'Deleting…' : 'Delete installment'}
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}