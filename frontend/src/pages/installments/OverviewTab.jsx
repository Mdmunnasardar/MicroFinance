import { useMemo } from 'react';
import { buildStats, collectRate, deriveStatus, formatDateShort, isToday } from '../../utils/installment';
import { formatMoney } from '../../utils/formatMoney';
import { initials, isAdminLike } from '../../utils/roleLabel';

const CashIcon = () => (
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="12" rx="2" />
    <circle cx="12" cy="12" r="2.5" />
    <path d="M6 12h.01M18 12h.01" />
  </svg>
);

const ScheduleIcon = () => (
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <line x1="16" y1="2" x2="16" y2="6" />
    <line x1="8" y1="2" x2="8" y2="6" />
    <line x1="3" y1="10" x2="21" y2="10" />
  </svg>
);

const DueIcon = () => (
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="9" />
    <polyline points="12 7 12 12 15 14" />
  </svg>
);

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
    <line x1="12" y1="9" x2="12" y2="13" />
    <line x1="12" y1="17" x2="12.01" y2="17" />
  </svg>
);

const CheckIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="20 6 9 17 4 12" />
  </svg>
);

function groupByMember(items) {
  const map = new Map();
  for (const item of items || []) {
    const member = item.member || {};
    const memberId = member.id ?? member.member_id ?? item.memberId ?? null;
    if (memberId == null) continue;
    const bucket = map.get(memberId) || {
      id: memberId,
      name: member.name || 'Unknown member',
      code: member.code || member.member_code || '',
      items: [],
    };
    bucket.items.push(item);
    map.set(memberId, bucket);
  }
  return Array.from(map.values()).map((b) => {
    const due = b.items.reduce((s, i) => s + (Number(i.dueAmount) || 0), 0);
    const paid = b.items.reduce((s, i) => s + (Number(i.paidAmount) || 0), 0);
    const balance = Math.max(0, due - paid);
    const overdue = b.items.filter((i) => deriveStatus(i) === 'overdue').length;
    const pending = b.items.filter((i) => {
      const s = deriveStatus(i);
      return s === 'pending' || s === 'partial';
    }).length;
    const next = [...b.items].sort((a, c) => {
      const ad = a.dueDate ? new Date(a.dueDate).getTime() : Infinity;
      const cd = c.dueDate ? new Date(c.dueDate).getTime() : Infinity;
      return ad - cd;
    }).find((i) => deriveStatus(i) !== 'paid');
    return { ...b, due, paid, balance, overdue, pending, next };
  });
}

function dueTodayItems(items) {
  return (items || []).filter((i) => isToday(i.dueDate) && deriveStatus(i) !== 'paid');
}

function StatCard({ tone, icon, label, value, sub, onClick }) {
  const className = `inst-overview-card tone-${tone}${onClick ? ' is-clickable' : ''}`;
  return (
    <button type="button" className={className} onClick={onClick} disabled={!onClick}>
      <span className="inst-overview-card-icon" aria-hidden>{icon}</span>
      <span className="inst-overview-card-label">{label}</span>
      <strong className="inst-overview-card-value">{value}</strong>
      {sub ? <span className="inst-overview-card-sub">{sub}</span> : null}
    </button>
  );
}

export default function OverviewTab({ items, loading, error, onCollect, onJumpToSchedule, onJumpToHistory, role, viewerName }) {
  const stats = useMemo(() => buildStats(items), [items]);
  const todayItems = useMemo(() => dueTodayItems(items), [items]);
  const rate = useMemo(() => collectRate(items), [items]);

  const memberGroups = useMemo(() => groupByMember(items), [items]);
  const leaderboard = useMemo(() => {
    const withOverdue = memberGroups
      .filter((m) => m.overdue > 0)
      .sort((a, b) => b.overdue - a.overdue || b.balance - a.balance)
      .slice(0, 5);
    return withOverdue;
  }, [memberGroups]);

  const myQueue = useMemo(() => {
    const today = todayItems;
    const upcoming = (items || [])
      .filter((i) => deriveStatus(i) !== 'paid' && !isToday(i.dueDate))
      .sort((a, b) => {
        const ad = a.dueDate ? new Date(a.dueDate).getTime() : Infinity;
        const bd = b.dueDate ? new Date(b.dueDate).getTime() : Infinity;
        return ad - bd;
      })
      .slice(0, 8);
    return [...today, ...upcoming].slice(0, 10);
  }, [items, todayItems]);

  const admin = isAdminLike(role);

  return (
    <div className="inst-overview">
      <section className="inst-overview-stats">
        <StatCard
          tone="success"
          icon={<CashIcon />}
          label="Total collected"
          value={formatMoney(stats.paidTotal)}
          sub={`${stats.paid} settled · ${rate}% collection rate`}
          onClick={onJumpToHistory}
        />
        <StatCard
          tone="warning"
          icon={<ScheduleIcon />}
          label="Outstanding"
          value={formatMoney(stats.balanceTotal)}
          sub={`${stats.pending} pending · ${stats.partial} partial`}
          onClick={onJumpToSchedule}
        />
        <StatCard
          tone="primary"
          icon={<DueIcon />}
          label="Due today"
          value={todayItems.length}
          sub={todayItems.length === 0 ? 'No collections due today' : `Across ${new Set(todayItems.map((i) => i.member?.id ?? i.memberId)).size} member(s)`}
        />
        <StatCard
          tone="danger"
          icon={<AlertIcon />}
          label="Overdue"
          value={formatMoney(stats.overdueAmount)}
          sub={`${stats.overdue} installment(s) past due`}
          onClick={onJumpToSchedule}
        />
      </section>

      <section className="inst-overview-meter">
        <div className="inst-overview-meter-head">
          <div>
            <span className="inst-overview-eyebrow">Overall settlement</span>
            <strong>{formatMoney(stats.paidTotal)} <span>of {formatMoney(stats.dueTotal)}</span></strong>
          </div>
          <span className="inst-overview-meter-pct">{rate}%</span>
        </div>
        <div className="inst-meter-bar inst-meter-bar-lg">
          <span style={{ width: `${rate}%` }} />
        </div>
      </section>

      {admin ? (
        <section className="inst-overview-section">
          <div className="inst-overview-section-head">
            <h3>Top defaults</h3>
            <span>Members with the most overdue installments</span>
          </div>
          {loading ? (
            <div className="inst-overview-empty">Loading…</div>
          ) : error ? (
            <div className="inst-overview-empty inst-overview-error">{error}</div>
          ) : leaderboard.length === 0 ? (
            <div className="inst-overview-empty">No defaults — every member is on schedule.</div>
          ) : (
            <ul className="inst-overview-list">
              {leaderboard.map((m) => (
                <li key={m.id} className="inst-overview-list-item">
                  <div className="inst-avatar inst-avatar-md">{initials(m.name)}</div>
                  <div className="inst-overview-list-body">
                    <strong>{m.name}</strong>
                    <span>{m.code ? `ID ${m.code} · ` : ''}{m.overdue} overdue · {m.pending} pending</span>
                  </div>
                  <div className="inst-overview-list-meta">
                    <span className="is-due">{formatMoney(m.balance)}</span>
                    <span>Outstanding</span>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      ) : (
        <section className="inst-overview-section">
          <div className="inst-overview-section-head">
            <h3>Your next collections{viewerName ? `, ${viewerName.split(' ')[0]}` : ''}</h3>
            <span>Items that need attention first</span>
          </div>
          {loading ? (
            <div className="inst-overview-empty">Loading…</div>
          ) : error ? (
            <div className="inst-overview-empty inst-overview-error">{error}</div>
          ) : myQueue.length === 0 ? (
            <div className="inst-overview-empty">
              <CheckIcon />
              <strong>You're all caught up</strong>
              <span>No outstanding installments waiting.</span>
            </div>
          ) : (
            <ul className="inst-queue">
              {myQueue.map((item) => {
                const balance = Math.max(0, (Number(item.dueAmount) || 0) - (Number(item.paidAmount) || 0));
                return (
                  <li key={item.id} className={`inst-queue-row is-${deriveStatus(item)}`}>
                    <div className="inst-queue-row-head">
                      <strong>{item.member?.name || 'Unknown member'}</strong>
                      <span>{item.loan?.code || `Loan #${item.loanId}`}</span>
                    </div>
                    <div className="inst-queue-row-meta">
                      <span>Installment #{item.installmentNo}</span>
                      <span>·</span>
                      <span>{formatDateShort(item.dueDate)}</span>
                    </div>
                    <div className="inst-queue-row-foot">
                      <strong className="is-due">{formatMoney(balance)}</strong>
                      <button type="button" className="inst-btn inst-btn-primary inst-btn-sm" onClick={() => onCollect && onCollect(item)}>
                        Collect
                      </button>
                    </div>
                  </li>
                );
              })}
            </ul>
          )}
        </section>
      )}
    </div>
  );
}
