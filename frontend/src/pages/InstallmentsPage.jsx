import { useCallback, useEffect, useMemo, useState } from 'react';
import { installmentsApi } from '../api/installmentsApi';
import InstallmentsTable from '../components/installments/InstallmentsTable';
import InstallmentForm from '../components/installments/InstallmentForm';
import CollectPaymentForm from '../components/installments/CollectPaymentForm';
import { useAuth } from '../hooks/useAuth';

const DEFAULT_PAGE_SIZE = 25;

function buildStats(items) {
  const summary = { total: 0, pending: 0, paid: 0, partial: 0, dueTotal: 0, paidTotal: 0, balanceTotal: 0 };
  for (const item of items || []) {
    summary.total += 1;
    if (item.status === 'paid') summary.paid += 1;
    else if (item.status === 'partial') summary.partial += 1;
    else summary.pending += 1;
    summary.dueTotal += Number(item.dueAmount) || 0;
    summary.paidTotal += Number(item.paidAmount) || 0;
    summary.balanceTotal += Number(item.balance) || 0;
  }
  return summary;
}

function formatMoney(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

const heroIcons = {
  total: (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="4" width="18" height="18" rx="2" />
      <path d="M16 2v4M8 2v4M3 10h18" />
      <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01" />
    </svg>
  ),
  pending: (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="9" />
      <polyline points="12 7 12 12 15 14" />
    </svg>
  ),
  paid: (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
      <polyline points="22 4 12 14.01 9 11.01" />
    </svg>
  ),
  rate: (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
      <polyline points="16 7 22 7 22 13" />
    </svg>
  ),
};

const TrashIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="3 6 5 6 21 6" />
    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
  </svg>
);

const PayIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="12" rx="2" />
    <circle cx="12" cy="12" r="2" />
  </svg>
);

const ReceiptIcon = () => (
  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M4 3h16v18l-3-2-2 2-2-2-2 2-2-2-2 2-3-2z" />
    <line x1="8" y1="8" x2="16" y2="8" />
    <line x1="8" y1="12" x2="16" y2="12" />
    <line x1="8" y1="16" x2="13" y2="16" />
  </svg>
);

const UserIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
    <circle cx="12" cy="7" r="4" />
  </svg>
);

const LoanIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="5" width="20" height="14" rx="2" />
    <line x1="2" y1="10" x2="22" y2="10" />
  </svg>
);

const CalIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <line x1="16" y1="2" x2="16" y2="6" />
    <line x1="8" y1="2" x2="8" y2="6" />
    <line x1="3" y1="10" x2="21" y2="10" />
  </svg>
);

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="10" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

function StatCard({ label, value, sub, tone, icon, spark = [40, 70, 55, 90, 65, 80, 50, 75, 95, 60, 85, 100] }) {
  const heights = useMemo(() => spark.map((h) => Math.max(15, Math.min(100, h))), [spark]);
  return (
    <div className={`inst-stat ${tone}`}>
      <div className="inst-stat-head">
        <div className="inst-stat-icon" aria-hidden>{icon}</div>
        <span className="inst-stat-label">{label}</span>
      </div>
      <div className="inst-stat-value">{value}</div>
      {sub ? <div className="inst-stat-sub">{sub}</div> : null}
      <div className="inst-stat-spark" aria-hidden>
        {heights.map((h, i) => (
          <span key={i} style={{ height: `${h}%`, animationDelay: `${i * 40}ms` }} />
        ))}
      </div>
    </div>
  );
}

export default function InstallmentsPage() {
  const { user } = useAuth();
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ page: 1, per_page: DEFAULT_PAGE_SIZE, total: 0, last_page: 1 });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PAGE_SIZE);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [feedback, setFeedback] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState('pay');
  const [activeInstallment, setActiveInstallment] = useState(null);
  const [modalError, setModalError] = useState('');
  const [busy, setBusy] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [detailsInstallment, setDetailsInstallment] = useState(null);
  const [detailsLoading, setDetailsLoading] = useState(false);
  const [detailsError, setDetailsError] = useState('');
  const [collectOpen, setCollectOpen] = useState(false);
  const [collectInstallment, setCollectInstallment] = useState(null);
  const [collectError, setCollectError] = useState('');

  useEffect(() => { document.title = 'Installments · MicroFinance'; }, []);

  useEffect(() => {
    if (!feedback) return undefined;
    const timer = setTimeout(() => setFeedback(null), 3500);
    return () => clearTimeout(timer);
  }, [feedback]);

  const loadInstallments = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page, per_page: perPage };
      const response = await installmentsApi.list(params);
      setItems(Array.isArray(response.data) ? response.data : []);
      setMeta(response.meta || { page, per_page: perPage, total: 0, last_page: 1 });
    } catch (err) {
      setError(err.message || 'Failed to load installments.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage]);

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

  const openCollect = (installment) => {
    setCollectInstallment(installment);
    setCollectError('');
    setCollectOpen(true);
  };

  const closeCollect = () => {
    setCollectOpen(false);
    setCollectInstallment(null);
    setCollectError('');
  };

  const handleSubmit = async (payload) => {
    if (!activeInstallment) return;
    setBusy(true);
    setModalError('');
    try {
      if (modalMode === 'pay') {
        await installmentsApi.pay(activeInstallment.id, payload);
        setFeedback({ kind: 'success', message: `Payment recorded for installment #${activeInstallment.installmentNo}.` });
      } else {
        await installmentsApi.update(activeInstallment.id, payload);
        setFeedback({ kind: 'success', message: `Installment #${activeInstallment.installmentNo} updated successfully.` });
      }
      closeModal();
      await loadInstallments();
    } catch (err) {
      setModalError(err.message || 'Failed to save installment.');
    } finally {
      setBusy(false);
    }
  };

  const handleCollectSubmit = async (payload) => {
    if (!collectInstallment) return;
    setBusy(true);
    setCollectError('');
    try {
      const response = await installmentsApi.pay(collectInstallment.id, payload);
      const fresh = response?.data ?? null;
      const isSettled = Number(fresh?.balance || 0) <= 0 || fresh?.status === 'paid';
      setFeedback({
        kind: 'success',
        message: isSettled
          ? `Payment collected for installment #${collectInstallment.installmentNo}. Installment settled.`
          : `Partial payment collected for installment #${collectInstallment.installmentNo}.`,
      });
      closeCollect();
      await loadInstallments();
    } catch (err) {
      setCollectError(err.message || 'Failed to record payment.');
    } finally {
      setBusy(false);
    }
  };

  const handleViewDetails = async (item) => {
    setDetailsOpen(true);
    setDetailsInstallment(item);
    setDetailsLoading(true);
    setDetailsError('');
    try {
      const response = await installmentsApi.get(item.id);
      const fresh = response?.data ?? response;
      if (fresh && typeof fresh === 'object') {
        setDetailsInstallment(fresh);
      }
    } catch (err) {
      setDetailsError(err.message || 'Failed to load installment details.');
    } finally {
      setDetailsLoading(false);
    }
  };

  const closeDetails = () => {
    setDetailsOpen(false);
    setDetailsInstallment(null);
    setDetailsError('');
  };

  const handleDelete = async () => {
    if (!confirmDelete) return;
    setBusy(true);
    try {
      await installmentsApi.remove(confirmDelete.id);
      setFeedback({ kind: 'success', message: `Installment #${confirmDelete.installmentNo} deleted.` });
      setConfirmDelete(null);
      await loadInstallments();
    } catch (err) {
      setError(err.message || 'Failed to delete installment.');
      setConfirmDelete(null);
    } finally {
      setBusy(false);
    }
  };

  const collectionRate = stats.dueTotal > 0 ? Math.min(100, Math.round((stats.paidTotal / stats.dueTotal) * 100)) : 0;

  return (
    <div className="inst-page">
      {feedback ? (
        <div className={`inst-toast ${feedback.kind}`} role="status">
          <span className="inst-toast-icon" aria-hidden>{feedback.kind === 'success' ? '✓' : '!'}</span>
          <span>{feedback.message}</span>
        </div>
      ) : null}

      <header className="inst-page-head">
        <div className="inst-page-head-text">
          <span className="inst-page-eyebrow">
            <span className="dot" aria-hidden /> Loan Repayments
          </span>
          <h1>Installments</h1>
          <p>Track every scheduled installment, payment, and outstanding balance across your lending portfolio.</p>
        </div>
        <div className="inst-page-head-stats">
          <div className="inst-page-stat">
            <span className="inst-page-stat-label">Outstanding</span>
            <span className="inst-page-stat-value">{formatMoney(stats.balanceTotal)}</span>
          </div>
          <div className="inst-page-stat">
            <span className="inst-page-stat-label">Collected</span>
            <span className="inst-page-stat-value">{formatMoney(stats.paidTotal)}</span>
          </div>
          <button
            type="button"
            className="inst-page-cta"
            onClick={() => {
              const next = items.find((it) => it.status !== 'paid') || items[0];
              if (next) openCollect(next);
            }}
            disabled={loading || items.length === 0}
            title={items.length === 0 ? 'No installments available' : 'Record a payment for the next pending installment'}
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
              <rect x="2" y="6" width="20" height="12" rx="2" />
              <circle cx="12" cy="12" r="2" />
            </svg>
            <span>Collect Payment</span>
          </button>
        </div>
      </header>

      <div className="inst-stats">
        <StatCard
          label="Total installments"
          value={stats.total}
          sub="Across all loans"
          tone="indigo"
          icon={heroIcons.total}
        />
        <StatCard
          label="Pending"
          value={stats.pending}
          sub={`${formatMoney(stats.balanceTotal)} outstanding`}
          tone="amber"
          icon={heroIcons.pending}
        />
        <StatCard
          label="Paid"
          value={stats.paid}
          sub={`${formatMoney(stats.paidTotal)} collected`}
          tone="emerald"
          icon={heroIcons.paid}
        />
        <StatCard
          label="Collection rate"
          value={`${collectionRate}%`}
          sub={collectionRate >= 75 ? 'On target' : 'Needs attention'}
          tone={collectionRate >= 75 ? 'emerald' : 'rose'}
          icon={heroIcons.rate}
        />
      </div>

      <section className="inst-panel">
        <header className="inst-panel-head">
          <div className="inst-panel-title">
            <div className="inst-panel-eyebrow">
              <span className="dot" aria-hidden /> Loan Repayments
            </div>
            <h2>Installment schedule</h2>
            <p>Search, filter, and manage all scheduled installments across your lending portfolio.</p>
          </div>
          <div className="inst-panel-meta">
            <span className="inst-counter">
              <span className="inst-counter-num">{meta.total ?? stats.total}</span>
              <span>total</span>
            </span>
          </div>
        </header>

        <div className="inst-divider" />

        {error ? (
          <div className="inst-alert" role="alert">
            <div className="inst-alert-icon" aria-hidden><AlertIcon /></div>
            <div className="inst-alert-text">
              <strong>Couldn't load installments.</strong>
              <span>{error}</span>
            </div>
            <button type="button" className="retry" onClick={loadInstallments}>Retry</button>
          </div>
        ) : null}

        {loading ? (
          <div className="inst-state loading">
            <div className="inst-state-spinner" />
            <p>Loading installments…</p>
            <small>Fetching the latest schedule from your portfolio</small>
          </div>
        ) : (
          <InstallmentsTable
            items={items}
            onView={handleViewDetails}
            onCollect={(item) => openCollect(item)}
            onPay={(item) => openModal('pay', item)}
            onEdit={(item) => openModal('edit', item)}
            onDelete={(item) => setConfirmDelete(item)}
          />
        )}

        <div className="inst-pagination" role="navigation" aria-label="Installments pagination">
          <div className="inst-pagination-info">
            Showing
            <strong>{items.length === 0 ? 0 : (meta.page - 1) * meta.per_page + 1}</strong>
            –
            <strong>{Math.min(meta.page * meta.per_page, meta.total)}</strong>
            of <strong>{meta.total}</strong>
          </div>
          <div className="inst-pagination-controls">
            <label className="inst-pagination-size">
              <span>Rows</span>
              <select
                value={perPage}
                onChange={(event) => {
                  setPage(1);
                  setPerPage(Number(event.target.value));
                }}
                disabled={loading}
              >
                <option value={10}>10</option>
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
              </select>
            </label>
            <button
              type="button"
              className="inst-page-btn"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={loading || meta.page <= 1}
            >
              ‹ Prev
            </button>
            <span className="inst-page-current">
              Page <strong>{meta.page}</strong> of <strong>{meta.last_page}</strong>
            </span>
            <button
              type="button"
              className="inst-page-btn"
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={loading || meta.page >= meta.last_page}
            >
              Next ›
            </button>
          </div>
        </div>
      </section>

      <InstallmentForm
        open={modalOpen}
        mode={modalMode}
        installment={activeInstallment}
        busy={busy}
        error={modalError}
        onSubmit={handleSubmit}
        onClose={closeModal}
      />

      <CollectPaymentForm
        open={collectOpen}
        installment={collectInstallment}
        busy={busy}
        error={collectError}
        collector={user}
        onSubmit={handleCollectSubmit}
        onClose={closeCollect}
      />

      {confirmDelete ? (
        <div className="inst-backdrop" role="dialog" aria-modal="true" aria-labelledby="confirm-delete-title">
          <div className="inst-modal danger">
            <header className="inst-modal-head">
              <div className="inst-modal-icon danger" aria-hidden><TrashIcon /></div>
              <div className="inst-modal-title-wrap">
                <h3 id="confirm-delete-title">Delete installment?</h3>
                <p>
                  {confirmDelete.member?.name} · Loan {confirmDelete.loan?.code || `#${confirmDelete.loanId}`} · Installment #{confirmDelete.installmentNo}
                </p>
              </div>
              <button type="button" className="inst-modal-close" onClick={() => setConfirmDelete(null)} aria-label="Close">
                <CloseIcon />
              </button>
            </header>
            <div className="inst-modal-body">
              <p style={{ margin: 0, color: 'var(--muted)', fontSize: 13, lineHeight: 1.5 }}>
                This action cannot be undone. Only installments with no payments recorded can be deleted.
              </p>
              {error ? (
                <div className="inst-modal-error" role="alert">
                  <AlertIcon />
                  <span>{error}</span>
                </div>
              ) : null}
              <div className="inst-modal-actions">
                <button type="button" className="inst-btn-cancel" onClick={() => setConfirmDelete(null)} disabled={busy}>Cancel</button>
                <button type="button" className="inst-btn-danger" onClick={handleDelete} disabled={busy}>
                  <TrashIcon /> {busy ? 'Deleting…' : 'Delete installment'}
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {detailsOpen ? (
        <div className="inst-backdrop" role="dialog" aria-modal="true" aria-labelledby="installment-details-title">
          <div className="inst-modal inst-modal-fintech">
            <header className="inst-modal-head inst-modal-head-fintech is-view">
              <div className="inst-modal-head-left">
                <div className="inst-modal-icon primary" aria-hidden><ReceiptIcon /></div>
                <div className="inst-modal-title-wrap">
                  <span className="inst-modal-eyebrow">View Payment</span>
                  <h3 id="installment-details-title">Installment details</h3>
                  <p>
                    {detailsInstallment?.member?.name || 'Unknown member'}
                    <span className="inst-modal-sep">·</span>
                    Loan {detailsInstallment?.loan?.code || `#${detailsInstallment?.loanId}`}
                    <span className="inst-modal-sep">·</span>
                    Installment <strong>#{detailsInstallment?.installmentNo}</strong>
                  </p>
                </div>
              </div>
              <button type="button" className="inst-modal-close" onClick={closeDetails} aria-label="Close">
                <CloseIcon />
              </button>
            </header>
            <div className="inst-modal-body">
              {detailsLoading ? (
                <div className="inst-state loading"><div className="inst-state-spinner" /><p>Loading installment…</p></div>
              ) : detailsError ? (
                <div className="inst-modal-error" role="alert">
                  <AlertIcon />
                  <span>{detailsError}</span>
                </div>
              ) : detailsInstallment ? (
                <>
                  <div className="inst-balance-card">
                    <div className="inst-balance-card-top">
                      <span className="inst-balance-label">Outstanding balance</span>
                      <span className={`inst-balance-pill ${(Number(detailsInstallment.balance) || 0) > 0 ? 'warn' : 'ok'}`}>
                        {detailsInstallment.status === 'paid' ? 'Settled' : (Number(detailsInstallment.balance) || 0) > 0 ? 'Pending' : 'Ready to settle'}
                      </span>
                    </div>
                    <div className="inst-balance-amount">
                      <span className="inst-balance-currency" aria-hidden>৳</span>
                      <span className="inst-balance-num">{(Number(detailsInstallment.balance) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="inst-balance-meta">
                      <span><strong>{formatMoney(detailsInstallment.dueAmount)}</strong> due</span>
                      <span className="inst-balance-dot" aria-hidden>•</span>
                      <span><strong>{formatMoney(detailsInstallment.paidAmount)}</strong> paid</span>
                      <span className="inst-balance-dot" aria-hidden>•</span>
                      <span>Installment <strong>#{detailsInstallment.installmentNo}</strong></span>
                    </div>
                    <div className="inst-meter-bar inst-meter-bar-lg">
                      <span style={{
                        width: `${Number(detailsInstallment.dueAmount) > 0
                          ? Math.min(100, Math.round(((Number(detailsInstallment.paidAmount) || 0) / Number(detailsInstallment.dueAmount)) * 100))
                          : 0}%`,
                      }} />
                    </div>
                    <div className="inst-meter-progress-row">
                      <span>Settlement progress</span>
                      <span>
                        <strong>
                          {Number(detailsInstallment.dueAmount) > 0
                            ? Math.min(100, Math.round(((Number(detailsInstallment.paidAmount) || 0) / Number(detailsInstallment.dueAmount)) * 100))
                            : 0}
                          %
                        </strong>
                      </span>
                    </div>
                  </div>

                  <div className="inst-form-section">
                    <div className="inst-form-section-title">Parties</div>
                    <div className="inst-context-row">
                      <div className="inst-context-item">
                        <span className="inst-context-icon" aria-hidden><UserIcon /></span>
                        <div>
                          <span className="inst-context-label">Member</span>
                          <span className="inst-context-value">
                            {detailsInstallment.member?.name || '—'}
                            {detailsInstallment.member?.code ? ` (${detailsInstallment.member.code})` : ''}
                          </span>
                        </div>
                      </div>
                      <div className="inst-context-item">
                        <span className="inst-context-icon" aria-hidden><LoanIcon /></span>
                        <div>
                          <span className="inst-context-label">Loan</span>
                          <span className="inst-context-value">
                            {detailsInstallment.loan?.code || `Loan #${detailsInstallment.loanId}`}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="inst-form-section">
                    <div className="inst-form-section-title">Payment summary</div>
                    <div className="inst-summary-card">
                      <div className="inst-summary-row">
                        <span>Due amount</span>
                        <strong>{formatMoney(detailsInstallment.dueAmount)}</strong>
                      </div>
                      <div className="inst-summary-row">
                        <span>Paid amount</span>
                        <strong className="ok">{formatMoney(detailsInstallment.paidAmount)}</strong>
                      </div>
                      <div className="inst-summary-row inst-summary-divider">
                        <span>Balance</span>
                        <strong className={(Number(detailsInstallment.balance) || 0) > 0 ? 'warn' : 'ok'}>
                          {formatMoney(detailsInstallment.balance)}
                        </strong>
                      </div>
                    </div>
                  </div>

                  <div className="inst-form-section">
                    <div className="inst-form-section-title">Identifiers & status</div>
                    <dl className="inst-details-grid">
                      <div><dt>Installment ID</dt><dd>#{detailsInstallment.id}</dd></div>
                      <div><dt>Loan ID</dt><dd>#{detailsInstallment.loanId}</dd></div>
                      <div><dt>Installment #</dt><dd>{detailsInstallment.installmentNo}</dd></div>
                      <div>
                        <dt>Status</dt>
                        <dd>
                          <span className={`inst-badge ${detailsInstallment.status === 'paid' ? 'ok' : 'partial'}`}>
                            {detailsInstallment.status === 'paid' ? 'Paid' : 'Pending'}
                          </span>
                        </dd>
                      </div>
                    </dl>
                  </div>

                  <div className="inst-form-section">
                    <div className="inst-form-section-title">Timeline</div>
                    <div className="inst-context-row">
                      <div className="inst-context-item">
                        <span className="inst-context-icon" aria-hidden><CalIcon /></span>
                        <div>
                          <span className="inst-context-label">Paid date</span>
                          <span className="inst-context-value">{detailsInstallment.paidDate || 'Not paid yet'}</span>
                        </div>
                      </div>
                      <div className="inst-context-item">
                        <span className="inst-context-icon" aria-hidden><ReceiptIcon /></span>
                        <div>
                          <span className="inst-context-label">Status</span>
                          <span className="inst-context-value">
                            <span className={`inst-badge ${detailsInstallment.status === 'paid' ? 'ok' : 'partial'}`}>
                              {detailsInstallment.status === 'paid' ? 'Settled' : 'Pending settlement'}
                            </span>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </>
              ) : null}
              <div className="inst-modal-actions">
                <button type="button" className="inst-btn-cancel" onClick={closeDetails}>Close</button>
                {detailsInstallment && detailsInstallment.status !== 'paid' ? (
                  <button
                    type="button"
                    className="inst-btn-confirm"
                    onClick={() => {
                      const inst = detailsInstallment;
                      closeDetails();
                      openModal('pay', inst);
                    }}
                  >
                    <PayIcon /> Collect payment
                  </button>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}