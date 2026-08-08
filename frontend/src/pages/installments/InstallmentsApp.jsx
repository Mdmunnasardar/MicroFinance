import { useCallback, useEffect, useMemo, useState } from 'react';
import { useAuth } from '../../hooks/useAuth';
import useInstallmentsList from '../../hooks/useInstallmentsList';
import useCollector from '../../hooks/useCollector';
import { installmentsApi } from '../../api/installmentsApi';
import { isAdminLike } from '../../utils/roleLabel';
import { deriveStatus } from '../../utils/installment';
import OverviewTab from './OverviewTab';
import ScheduleTab from './ScheduleTab';
import CollectTab from './CollectTab';
import HistoryTab from './HistoryTab';
import CollectPaymentModal from '../../components/installments/CollectPaymentModal';
import FeedbackToast from '../../components/installments/FeedbackToast';
import '../../assets/css/installments.css';
import './tabs.css';

const TABS = [
  { id: 'overview', label: 'Overview', eyebrow: 'Dashboard' },
  { id: 'schedule', label: 'Schedule', eyebrow: 'All installments' },
  { id: 'collect', label: 'Collect', eyebrow: 'Field officer' },
  { id: 'history', label: 'History', eyebrow: 'Payments log' },
];

function defaultTabForRole(role, fallback) {
  if (fallback && TABS.some((t) => t.id === fallback)) return fallback;
  if (isAdminLike(role)) return 'schedule';
  return 'collect';
}

export default function InstallmentsApp({ defaultTab }) {
  const { user } = useAuth();
  const role = user?.role || null;
  const viewerName = user?.name || user?.full_name || user?.username || '';
  const { collector } = useCollector();
  const canManage = isAdminLike(role);

  const [tab, setTab] = useState(() => defaultTabForRole(role, defaultTab));
  const [feedback, setFeedback] = useState(null);

  const list = useInstallmentsList({ per_page: 25 });

  const [collectTarget, setCollectTarget] = useState(null);
  const [collectBusy, setCollectBusy] = useState(false);
  const [collectError, setCollectError] = useState('');

  useEffect(() => {
    setFeedback(null);
  }, [tab]);

  const openCollect = useCallback((item) => {
    setFeedback(null);
    setCollectError('');
    setCollectTarget(item);
  }, []);

  const closeCollect = useCallback(() => {
    if (collectBusy) return;
    setCollectTarget(null);
    setCollectError('');
  }, [collectBusy]);

  const handleCollectSubmit = useCallback(async (payload) => {
    if (!collectTarget?.id) return;
    setCollectBusy(true);
    setCollectError('');
    try {
      const updated = await installmentsApi.pay(collectTarget.id, payload);
      const merged = { ...collectTarget, ...(updated || {}) };
      list.upsertItem(merged);
      const memberName = collectTarget.member?.name || 'member';
      const wasFull = Number(payload.paidAmount || 0) >= Number(collectTarget.dueAmount || 0);
      setFeedback({
        kind: 'success',
        title: wasFull ? 'Installment settled' : 'Partial payment recorded',
        message: `${formatAmount(payload.paidAmount)} recorded for ${memberName}.`,
      });
      setCollectTarget(null);
    } catch (err) {
      setCollectError(err?.message || 'Failed to record payment.');
    } finally {
      setCollectBusy(false);
    }
  }, [collectTarget, list]);

  const tabItems = useMemo(() => {
    const items = TABS.map((t) => ({ ...t }));
    if (!canManage) {
      const collect = items.find((t) => t.id === 'collect');
      if (collect) collect.label = 'Collect payment';
    }
    return items;
  }, [canManage]);

  const renderTab = () => {
    switch (tab) {
      case 'overview':
        return (
          <OverviewTab
            items={list.items}
            loading={list.loading}
            error={list.error}
            role={role}
            viewerName={viewerName}
            onCollect={openCollect}
            onJumpToSchedule={() => setTab('schedule')}
            onJumpToHistory={() => setTab('history')}
          />
        );
      case 'schedule':
        return (
          <ScheduleTab
            items={list.items}
            meta={list.meta}
            page={list.page}
            perPage={list.perPage}
            filters={list.filters}
            loading={list.loading}
            error={list.error}
            canManage={canManage}
            onUpdateFilters={list.updateFilters}
            onResetFilters={list.resetFilters}
            onChangePage={list.setPage}
            onChangePageSize={list.setPageSize}
            onView={(item) => openCollect(item)}
            onCollect={openCollect}
          />
        );
      case 'collect':
        return <CollectTab onCollect={openCollect} />;
      case 'history':
        return <HistoryTab items={list.items} loading={list.loading} error={list.error} onView={openCollect} />;
      default:
        return null;
    }
  };

  return (
    <div className="inst-app">
      <header className="inst-app-header">
        <div className="inst-app-header-titles">
          <span className="inst-app-eyebrow">Installments</span>
          <h1>Collection workspace</h1>
          <p>Schedule, collect and review loan installments in one place.</p>
        </div>
        <div className="inst-app-header-meta">
          <span className="inst-app-meta-pill">{canManage ? 'Admin view' : 'Field officer view'}</span>
          {viewerName ? <span className="inst-app-meta-user">{viewerName}</span> : null}
        </div>
      </header>

      <div className="inst-app-layout">
        <nav className="inst-app-rail" aria-label="Installments sections">
          {tabItems.map((t) => (
            <button
              key={t.id}
              type="button"
              className={`inst-app-rail-btn${tab === t.id ? ' is-active' : ''}`}
              onClick={() => setTab(t.id)}
              aria-pressed={tab === t.id}
            >
              <span className="inst-app-rail-eyebrow">{t.eyebrow}</span>
              <strong>{t.label}</strong>
            </button>
          ))}
        </nav>

        <main className="inst-app-main" aria-live="polite">
          {renderTab()}
        </main>
      </div>

      <FeedbackToast feedback={feedback} onClose={() => setFeedback(null)} />
      <CollectPaymentModal
        open={!!collectTarget}
        installment={collectTarget}
        busy={collectBusy}
        error={collectError}
        collector={collector}
        onSubmit={handleCollectSubmit}
        onClose={closeCollect}
      />
    </div>
  );
}

function formatAmount(value) {
  const n = Number(value) || 0;
  return `৳ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
