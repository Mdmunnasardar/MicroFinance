import '../assets/css/installments.css';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { installmentsApi } from '../api/installmentsApi';
import { membersApi } from '../api/membersApi';
import { useAuth } from '../hooks/useAuth';

/* ------------------------------ Helpers ------------------------------ */

const BDT_FORMATTER = new Intl.NumberFormat('en-US', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

function formatBDT(value) {
  const n = Number(value) || 0;
  return '\u09F3' + BDT_FORMATTER.format(n);
}

function todayISO() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function formatDate(iso) {
  if (!iso) return '—';
  const s = String(iso).slice(0, 10);
  const parts = s.split('-');
  if (parts.length !== 3) return s;
  const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

function initials(name) {
  if (!name) return '??';
  const parts = String(name).trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function safeTrim(value) {
  if (value === null || value === undefined) return '';
  return String(value).trim();
}

// Maps a raw role string to a human-readable label. Single source of truth
// so we never hardcode "Field Officer" against an admin's name.
function roleLabel(role) {
  if (!role) return 'User';
  const map = {
    admin: 'Admin',
    branch_manager: 'Branch Manager',
    field_officer: 'Field Officer',
    member: 'Member',
  };
  if (map[role]) return map[role];
  return String(role).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function normalizeStatus(installment) {
  const raw = String(installment?.status || '').toLowerCase();
  const paid = Number(installment?.paidAmount) || 0;
  const due = Number(installment?.dueAmount) || 0;
  if (raw === 'paid' || (due > 0 && paid >= due)) return 'paid';
  if (paid > 0) return 'partial';
  if (raw === 'overdue') return 'overdue';
  return 'pending';
}

/* ------------------------------- Icons ------------------------------- */

const SearchIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="11" cy="11" r="7" />
    <line x1="20" y1="20" x2="16.65" y2="16.65" />
  </svg>
);

const WalletIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="6" width="20" height="14" rx="2" />
    <path d="M16 14h2" />
    <path d="M2 10h20" />
  </svg>
);

const LoanIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="2" y="5" width="20" height="14" rx="2" />
    <line x1="2" y1="10" x2="22" y2="10" />
  </svg>
);

const CalIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <line x1="16" y1="2" x2="16" y2="6" />
    <line x1="8" y1="2" x2="8" y2="6" />
    <line x1="3" y1="10" x2="21" y2="10" />
  </svg>
);

const NoteIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <polyline points="14 2 14 8 20 8" />
    <line x1="9" y1="13" x2="15" y2="13" />
    <line x1="9" y1="17" x2="13" y2="17" />
  </svg>
);

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="10" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

const CheckIcon = () => (
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="20 6 9 17 4 12" />
  </svg>
);

const ResetIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="1 4 1 10 7 10" />
    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
  </svg>
);

const ArrowLeftIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="19" y1="12" x2="5" y2="12" />
    <polyline points="12 19 5 12 12 5" />
  </svg>
);

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

const PhoneIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.86 19.86 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
  </svg>
);

const ScheduleIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="8" y1="6" x2="21" y2="6" />
    <line x1="8" y1="12" x2="21" y2="12" />
    <line x1="8" y1="18" x2="21" y2="18" />
    <line x1="3" y1="6" x2="3.01" y2="6" />
    <line x1="3" y1="12" x2="3.01" y2="12" />
    <line x1="3" y1="18" x2="3.01" y2="18" />
  </svg>
);

/* --------------------------- Member Picker ---------------------------- */

function MemberPicker({ value, onPick, onClear, disabled }) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [open, setOpen] = useState(false);
  const [highlight, setHighlight] = useState(0);
  const debounceRef = useRef(null);
  const containerRef = useRef(null);

  useEffect(() => {
    function handler(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const searchMembers = useCallback(async (term) => {
    const t = safeTrim(term);
    if (t.length < 2) {
      setResults([]);
      setError('');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const response = await membersApi.search({ q: t, limit: 12 });
      const items = Array.isArray(response?.data) ? response.data : [];
      setResults(items);
    } catch (err) {
      setError(err.message || 'Failed to search members.');
      setResults([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      searchMembers(query);
    }, 280);
    return () => debounceRef.current && clearTimeout(debounceRef.current);
  }, [query, searchMembers]);

  useEffect(() => {
    setHighlight(0);
  }, [results]);

  const handleSelect = (member) => {
    onPick(member);
    setOpen(false);
    setQuery('');
    setResults([]);
  };

  const handleKeyDown = (event) => {
    if (!open || results.length === 0) return;
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setHighlight((h) => Math.min(results.length - 1, h + 1));
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      setHighlight((h) => Math.max(0, h - 1));
    } else if (event.key === 'Enter') {
      event.preventDefault();
      const chosen = results[highlight];
      if (chosen) handleSelect(chosen);
    } else if (event.key === 'Escape') {
      setOpen(false);
    }
  };

  if (value) {
    return (
      <div className="cp-picker-selected">
        <div className="cp-picker-avatar" aria-hidden>{initials(value.name)}</div>
        <div className="cp-picker-selected-text">
          <strong>{value.name}</strong>
          <span>
            {value.code ? `Member code: ${value.code}` : `Member ID: ${value.id}`}
            {value.phone ? ` · ${value.phone}` : ''}
          </span>
        </div>
        <button
          type="button"
          className="cp-picker-change"
          onClick={onClear}
          disabled={disabled}
          aria-label="Change member"
        >
          <ResetIcon />
          <span>Change</span>
        </button>
      </div>
    );
  }

  return (
    <div className="cp-picker" ref={containerRef}>
      <div className="cp-picker-input-wrap">
        <span className="cp-picker-input-icon" aria-hidden><SearchIcon /></span>
        <input
          type="text"
          className="cp-picker-input"
          placeholder="Search member by name, member ID, phone, or loan code…"
          value={query}
          onChange={(event) => {
            setQuery(event.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          onKeyDown={handleKeyDown}
          disabled={disabled}
          aria-label="Search member"
          aria-autocomplete="list"
          aria-expanded={open}
          aria-controls="cp-member-listbox"
          autoComplete="off"
          spellCheck="false"
        />
        {query ? (
          <button
            type="button"
            className="cp-picker-clear"
            onClick={() => { setQuery(''); setResults([]); setOpen(false); }}
            aria-label="Clear search"
            tabIndex={-1}
          >
            <CloseIcon />
          </button>
        ) : null}
      </div>

      {open ? (
        <div className="cp-picker-dropdown" role="listbox" id="cp-member-listbox">
          {loading ? (
            <div className="cp-picker-state">
              <div className="cp-spinner" />
              <span>Searching members…</span>
            </div>
          ) : error ? (
            <div className="cp-picker-state error">
              <AlertIcon />
              <span>{error}</span>
            </div>
          ) : results.length === 0 ? (
            <div className="cp-picker-state muted">
              {query.trim().length < 2
                ? <>Type at least 2 characters to search.</>
                : <>No members found for &ldquo;{query}&rdquo;.</>}
            </div>
          ) : (
            <ul className="cp-picker-list">
              {results.map((member, idx) => {
                const matchedLoan = member.matchedLoan;
                return (
                  <li key={member.id}>
                    <button
                      type="button"
                      className={`cp-picker-item ${idx === highlight ? 'active' : ''}`}
                      onClick={() => handleSelect(member)}
                      onMouseEnter={() => setHighlight(idx)}
                      role="option"
                      aria-selected={idx === highlight}
                    >
                      <div className="cp-picker-avatar small" aria-hidden>{initials(member.name)}</div>
                      <div className="cp-picker-item-text">
                        <strong>{member.name}</strong>
                        <span className="cp-picker-meta">
                          <span>{member.code ? `Code: ${member.code}` : `ID: ${member.id}`}</span>
                          {member.phone ? (
                            <span className="cp-picker-meta-tag"><PhoneIcon />{member.phone}</span>
                          ) : null}
                          {matchedLoan ? (
                            <span className="cp-picker-meta-tag loan">Loan: {matchedLoan.code}</span>
                          ) : null}
                          {!member.isActive ? (
                            <span className="cp-picker-meta-tag muted">Inactive</span>
                          ) : null}
                        </span>
                      </div>
                    </button>
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      ) : null}
    </div>
  );
}

/* ----------------------------- Loan Picker --------------------------- */

function LoanPicker({ loans, value, onPick, loading, error }) {
  if (loading) {
    return (
      <div className="cp-state">
        <div className="cp-spinner" />
        <span>Loading member loans…</span>
      </div>
    );
  }
  if (error) {
    return (
      <div className="cp-state error">
        <AlertIcon />
        <span>{error}</span>
      </div>
    );
  }
  if (loans.length === 0) {
    return <div className="cp-state muted">No active loans found for this member.</div>;
  }

  return (
    <div className="cp-loan-list">
      {loans.map((loan) => {
        const isSelected = value?.id === loan.id;
        return (
          <button
            type="button"
            key={loan.id}
            className={`cp-loan-card ${isSelected ? 'selected' : ''}`}
            onClick={() => onPick(loan)}
          >
            <div className="cp-loan-card-head">
              <span className="cp-loan-code">{loan.code || `Loan #${loan.id}`}</span>
              <span className={`cp-loan-status ${loan.outstanding > 0 ? 'pending' : 'settled'}`}>
                {loan.outstanding > 0 ? 'Outstanding' : 'Settled'}
              </span>
            </div>
            <div className="cp-loan-card-stats">
              <div>
                <span>Total due</span>
                <strong>{formatBDT(loan.totalDue)}</strong>
              </div>
              <div>
                <span>Paid</span>
                <strong className="ok">{formatBDT(loan.totalPaid)}</strong>
              </div>
              <div>
                <span>Outstanding</span>
                <strong className={loan.outstanding > 0 ? 'warn' : 'ok'}>
                  {formatBDT(loan.outstanding)}
                </strong>
              </div>
              <div>
                <span>Pending installments</span>
                <strong>{loan.pendingCount}</strong>
              </div>
            </div>
          </button>
        );
      })}
    </div>
  );
}

/* ----------------------- Installment Schedule ----------------------- */
/*
 * NOTE: The old "Collect" action button (per-row) has been removed.
 * Rows that aren't already settled are now clickable directly -
 * clicking anywhere on a pending/partial/overdue row selects it for
 * collection. Settled rows just show a checkmark and are not clickable.
 */

function InstallmentSchedule({ installments, selectedId, onSelect, loading, error, loan }) {
  if (loading) {
    return (
      <div className="cp-state">
        <div className="cp-spinner" />
        <span>Loading installment schedule…</span>
      </div>
    );
  }
  if (error) {
    return (
      <div className="cp-state error">
        <AlertIcon />
        <span>{error}</span>
      </div>
    );
  }
  if (!installments || installments.length === 0) {
    return <div className="cp-state muted">No installments found for this loan.</div>;
  }

  return (
    <div className="cp-schedule-wrap">
      <div className="cp-schedule-head">
        <ScheduleIcon />
        <span>Installment schedule{loan ? ` · ${loan.code}` : ''}</span>
      </div>
      <div className="cp-schedule-table-wrap">
        <table className="cp-schedule-table" role="grid">
          <thead>
            <tr>
              <th>#</th>
              <th>Due date</th>
              <th>Due amount</th>
              <th>Paid</th>
              <th>Remaining</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {installments.map((inst) => {
              const due = Number(inst.dueAmount) || 0;
              const paid = Number(inst.paidAmount) || 0;
              const remaining = Math.max(0, due - paid);
              const status = normalizeStatus(inst);
              const isSelected = selectedId === inst.id;
              const isCollectable = status !== 'paid';
              return (
                <tr
                  key={inst.id}
                  className={`${isSelected ? 'selected' : ''} ${status} ${isCollectable ? 'cp-row-clickable' : ''}`}
                  onClick={isCollectable ? () => onSelect(inst) : undefined}
                  role={isCollectable ? 'button' : undefined}
                  tabIndex={isCollectable ? 0 : undefined}
                  onKeyDown={
                    isCollectable
                      ? (event) => {
                          if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            onSelect(inst);
                          }
                        }
                      : undefined
                  }
                >
                  <td className="cp-schedule-no">#{inst.installmentNo}</td>
                  <td>{formatDate(inst.dueDate)}</td>
                  <td className="cp-num">{formatBDT(due)}</td>
                  <td className="cp-num ok">{formatBDT(paid)}</td>
                  <td className={`cp-num ${remaining > 0 ? 'warn' : 'ok'}`}>{formatBDT(remaining)}</td>
                  <td>
                    <span className={`cp-schedule-status cp-status-${status}`}>
                      {status === 'paid' && 'Settled'}
                      {status === 'partial' && 'Partial'}
                      {status === 'overdue' && 'Overdue'}
                      {status === 'pending' && 'Pending'}
                      {isSelected ? ' · Selected' : ''}
                    </span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

/* ----------------------- Member / Loan Summary ----------------------- */

function MemberSummary({ member, loan }) {
  return (
    <div className="cp-summary">
      <div className="cp-summary-member">
        <div className="cp-picker-avatar large" aria-hidden>{initials(member?.name)}</div>
        <div>
          <span className="cp-summary-eyebrow">Member</span>
          <strong>{member?.name}</strong>
          <span>{member?.code ? `Code: ${member.code}` : `ID: ${member.id}`}</span>
        </div>
      </div>
      {loan ? (
        <div className="cp-summary-loan">
          <span className="cp-summary-eyebrow">Loan</span>
          <strong>{loan.code || `Loan #${loan.id}`}</strong>
          <span>{loan.totalDue ? `${formatBDT(loan.totalPaid)} paid of ${formatBDT(loan.totalDue)}` : ''}</span>
        </div>
      ) : null}
    </div>
  );
}

/* ============================== Page =============================== */

export default function CollectPaymentPage() {
  const { user } = useAuth();

  // Step state
  const [selectedMember, setSelectedMember] = useState(null);
  const [memberLoans, setMemberLoans] = useState([]);
  const [loansLoading, setLoansLoading] = useState(false);
  const [loansError, setLoansError] = useState('');
  const [selectedLoan, setSelectedLoan] = useState(null);
  const [installments, setInstallments] = useState([]);
  const [installmentsLoading, setInstallmentsLoading] = useState(false);
  const [installmentsError, setInstallmentsError] = useState('');
  const [selectedInstallment, setSelectedInstallment] = useState(null);
  const [installmentLoading, setInstallmentLoading] = useState(false);
  const [installmentError, setInstallmentError] = useState('');

  // Form state
  const [amount, setAmount] = useState('');
  const [paymentDate, setPaymentDate] = useState(todayISO());
  const [note, setNote] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState('');
  const [success, setSuccess] = useState(null);

  const advanceTimerRef = useRef(null);

  // Toast
  const [toast, setToast] = useState(null);
  useEffect(() => {
    if (!toast) return undefined;
    const timer = setTimeout(() => setToast(null), 4500);
    return () => clearTimeout(timer);
  }, [toast]);

  useEffect(() => { document.title = 'Collect Payment · MicroFinance'; }, []);

  useEffect(() => () => {
    if (advanceTimerRef.current) {
      clearTimeout(advanceTimerRef.current);
      advanceTimerRef.current = null;
    }
  }, []);

  const summarizeLoans = (items) => {
    const loanMap = new Map();
    for (const it of items) {
      const lid = it.loanId;
      if (!loanMap.has(lid)) {
        loanMap.set(lid, {
          id: lid,
          code: it.loan?.code || '',
          totalDue: 0,
          totalPaid: 0,
          outstanding: 0,
          pendingCount: 0,
          installmentCount: 0,
          installments: [],
        });
      }
      const entry = loanMap.get(lid);
      entry.totalDue += Number(it.dueAmount) || 0;
      entry.totalPaid += Number(it.paidAmount) || 0;
      entry.outstanding += Math.max(0, (Number(it.dueAmount) || 0) - (Number(it.paidAmount) || 0));
      entry.installments.push(it);
      entry.installmentCount += 1;
      if (normalizeStatus(it) !== 'paid') entry.pendingCount += 1;
    }
    return Array.from(loanMap.values()).sort((a, b) => b.id - a.id);
  };

  /* ----------- Load member's loans + installments ----------- */

  const loadMemberData = useCallback(async (memberId) => {
    if (!memberId) return;
    setLoansLoading(true);
    setInstallmentsLoading(true);
    setLoansError('');
    setInstallmentsError('');
    setMemberLoans([]);
    setSelectedLoan(null);
    setInstallments([]);
    setSelectedInstallment(null);
    setInstallmentError('');
    try {
      const response = await installmentsApi.list({ member_id: memberId, per_page: 200 });
      const items = Array.isArray(response.data) ? response.data : [];
      const summaries = summarizeLoans(items);
      setMemberLoans(summaries);
      if (summaries.length === 0) {
        setLoansError('This member has no loans yet.');
      }
    } catch (err) {
      setLoansError(err.message || 'Failed to load member loans.');
    } finally {
      setLoansLoading(false);
      setInstallmentsLoading(false);
    }
  }, []);

  /* ---- When a loan is picked, show its full installment schedule ---- */

  const loadLoanSchedule = useCallback(async (loan) => {
    setInstallmentsLoading(true);
    setInstallmentsError('');
    setInstallments([]);
    setSelectedInstallment(null);
    setInstallmentError('');
    setSubmitError('');
    setSuccess(null);
    setAmount('');
    setNote('');
    try {
      const response = await installmentsApi.list({ loan_id: loan.id, per_page: 200 });
      const items = Array.isArray(response.data) ? response.data : [];
      const sorted = [...items].sort(
        (a, b) => (Number(a.installmentNo) || 0) - (Number(b.installmentNo) || 0),
      );
      setInstallments(sorted);
      const nextPending = sorted.find((it) => normalizeStatus(it) !== 'paid');
      if (nextPending) {
        await selectInstallment(nextPending);
      } else if (sorted.length > 0) {
        setInstallmentError('All installments for this loan are settled.');
      } else {
        setInstallmentError('No installments found for this loan.');
      }
    } catch (err) {
      setInstallmentsError(err.message || 'Failed to load installment schedule.');
    } finally {
      setInstallmentsLoading(false);
    }
  }, []);

  const selectInstallment = useCallback(async (installment) => {
    setInstallmentLoading(true);
    setInstallmentError('');
    setSubmitError('');
    setSuccess(null);
    try {
      // Re-fetch so paid amounts reflect the freshest server state.
      let fresh = installment;
      try {
        const single = await installmentsApi.get(installment.id);
        if (single?.data) fresh = single.data;
      } catch {
        // Fall back to the list payload if single-fetch fails.
      }
      setSelectedInstallment(fresh);
      const remaining = Math.max(
        0,
        (Number(fresh.dueAmount) || 0) - (Number(fresh.paidAmount) || 0),
      );
      setAmount(remaining > 0 ? String(remaining) : '');
      setNote('');
    } catch (err) {
      setInstallmentError(err.message || 'Failed to load installment.');
    } finally {
      setInstallmentLoading(false);
    }
  }, []);

  /* ---------------------------- Handlers ---------------------------- */

  const handlePickMember = (member) => {
    setSelectedMember(member);
    setSuccess(null);
    setSubmitError('');
    if (member?.id) loadMemberData(member.id);
  };

  const handleClearMember = () => {
    setSelectedMember(null);
    setMemberLoans([]);
    setSelectedLoan(null);
    setInstallments([]);
    setSelectedInstallment(null);
    setAmount('');
    setNote('');
    setPaymentDate(todayISO());
    setSubmitError('');
    setSuccess(null);
  };

  const handlePickLoan = (loan) => {
    setSelectedLoan(loan);
    setSuccess(null);
    setSubmitError('');
    loadLoanSchedule(loan);
  };

  const handleResetForm = () => {
    setAmount('');
    setNote('');
    setPaymentDate(todayISO());
    setSubmitError('');
    setSuccess(null);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!selectedInstallment) {
      setSubmitError('Please select an installment to collect.');
      return;
    }
    const value = Number(amount);
    if (!value || value <= 0) {
      setSubmitError('Please enter a valid amount greater than zero.');
      return;
    }
    const due = Number(selectedInstallment.dueAmount) || 0;
    const alreadyPaid = Number(selectedInstallment.paidAmount) || 0;
    const maxAddable = Math.max(0, due - alreadyPaid);
    if (value > maxAddable) {
      setSubmitError(`Amount exceeds the remaining balance of ${formatBDT(maxAddable)}.`);
      return;
    }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(paymentDate)) {
      setSubmitError('Please pick a valid payment date.');
      return;
    }

    setSubmitting(true);
    setSubmitError('');
    try {
      const newPaidTotal = alreadyPaid + value;
      // PUT returns the updated installment (server calls show() after update).
      const putResponse = await installmentsApi.pay(selectedInstallment.id, {
        paidAmount: newPaidTotal,
        paidDate: paymentDate,
        notes: safeTrim(note) === '' ? null : safeTrim(note),
        collectedBy: user?.id ?? null,
      });
      const fresh = putResponse?.data ?? null;

      const settled = !fresh
        || String(fresh.status).toLowerCase() === 'paid'
        || (Number(fresh.balance) || 0) <= 0;

      setToast({
        kind: 'success',
        message: settled
          ? `Payment of ${formatBDT(value)} recorded. Installment settled.`
          : `Partial payment of ${formatBDT(value)} recorded for installment #${selectedInstallment.installmentNo}.`,
      });
      setSuccess({
        amount: value,
        date: paymentDate,
        note: safeTrim(note) || null,
        settled,
        installmentNo: selectedInstallment.installmentNo,
      });

      // Re-list so the schedule mirrors the new totals for every other
      // installment that was waiting on this payment.
      let refreshedList = installments;
      if (selectedLoan) {
        try {
          const response = await installmentsApi.list({ loan_id: selectedLoan.id, per_page: 100 });
          const items = Array.isArray(response.data) ? response.data : [];
          refreshedList = [...items].sort(
            (a, b) => (Number(a.installmentNo) || 0) - (Number(b.installmentNo) || 0),
          );
          setInstallments(refreshedList);
          setMemberLoans((prev) => prev.map((loan) => {
            if (loan.id !== selectedLoan.id) return loan;
            const totals = refreshedList.reduce((acc, it) => {
              const due = Number(it.dueAmount) || 0;
              const paid = Number(it.paidAmount) || 0;
              acc.due += due;
              acc.paid += paid;
              if (normalizeStatus(it) !== 'paid') acc.pending += 1;
              return acc;
            }, { due: 0, paid: 0, pending: 0 });
            return {
              ...loan,
              totalDue: totals.due,
              totalPaid: totals.paid,
              outstanding: Math.max(0, totals.due - totals.paid),
              pendingCount: totals.pending,
            };
          }));
        } catch {
          // Best-effort: keep the prior schedule if the refresh fails.
        }
      }

      if (settled) {
        const nextPending = refreshedList.find((it) => normalizeStatus(it) !== 'paid');
        if (nextPending) {
          advanceTimerRef.current = setTimeout(() => {
            advanceTimerRef.current = null;
            selectInstallment(nextPending);
          }, 400);
        } else {
          setSelectedInstallment(null);
          setInstallmentError('All installments for this loan are settled. Great job!');
        }
      } else if (fresh) {
        setSelectedInstallment(fresh);
        setAmount('');
        setNote('');
      }
    } catch (err) {
      setSubmitError(err.message || 'Failed to record payment.');
    } finally {
      setSubmitting(false);
    }
  };

  /* ---------------------------- Derived ---------------------------- */

  const remainingBalance = useMemo(() => {
    if (!selectedInstallment) return 0;
    const due = Number(selectedInstallment.dueAmount) || 0;
    const paid = Number(selectedInstallment.paidAmount) || 0;
    return Math.max(0, due - paid);
  }, [selectedInstallment]);

  const progressPct = useMemo(() => {
    if (!selectedInstallment) return 0;
    const due = Number(selectedInstallment.dueAmount) || 0;
    const paid = Number(selectedInstallment.paidAmount) || 0;
    if (due <= 0) return 0;
    return Math.min(100, Math.round((paid / due) * 100));
  }, [selectedInstallment]);

  const installmentStatus = useMemo(
    () => (selectedInstallment ? normalizeStatus(selectedInstallment) : null),
    [selectedInstallment],
  );

  /* ---------------------------- Render ----------------------------- */

  return (
    <div className="cp-page">
      {toast ? (
        <div className={`cp-toast ${toast.kind}`} role="status">
          <span className="cp-toast-icon" aria-hidden>
            <CheckIcon />
          </span>
          <span>{toast.message}</span>
        </div>
      ) : null}

      <header className="cp-header">
        <div className="cp-header-text">
          <a href="./" className="cp-back-link">
            <ArrowLeftIcon />
            <span>Back to Installments</span>
          </a>
          <span className="cp-eyebrow">
            <span className="dot" aria-hidden />
            {roleLabel(user?.role)} · Daily Collection
          </span>
          <h1>
            Collect <span className="accent">Payment</span>
          </h1>
          <p>Search a member, pick their active loan, choose an installment, and record today&rsquo;s collection in seconds.</p>
        </div>
        <div className="cp-header-meta">
          <div className="cp-header-meta-item">
            <span>Today</span>
            <strong>{new Date().toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })}</strong>
          </div>
          <div className="cp-header-meta-item">
            <span>Collector</span>
            <strong>{user?.name || user?.username || roleLabel(user?.role) || 'User'}</strong>
          </div>
        </div>
      </header>

      <main className="cp-layout">
        <section className="cp-card cp-card-main">
          <ol className="cp-steps">
            <li className={`cp-step ${selectedMember ? 'done' : 'active'}`}>
              <div className="cp-step-num">1</div>
              <div className="cp-step-body">
                <h3>Select member</h3>
                <p>Search by name, member ID, phone, or loan code. Live suggestions as you type.</p>
                <MemberPicker
                  value={selectedMember}
                  onPick={handlePickMember}
                  onClear={handleClearMember}
                  disabled={submitting}
                />
              </div>
            </li>

            <li className={`cp-step ${selectedLoan ? 'done' : selectedMember ? 'active' : ''}`}>
              <div className="cp-step-num">2</div>
              <div className="cp-step-body">
                <h3>Pick their active loan</h3>
                <p>Loans are grouped with their installment totals.</p>
                <div className="cp-loan-picker">
                  <div className="cp-section-label">
                    <LoanIcon />
                    <span>Active loans</span>
                  </div>
                  <LoanPicker
                    loans={memberLoans}
                    value={selectedLoan}
                    onPick={handlePickLoan}
                    loading={loansLoading}
                    error={loansError}
                  />
                </div>
              </div>
            </li>

            <li className={`cp-step ${selectedLoan ? (selectedInstallment ? 'active' : '') : ''}`}>
              <div className="cp-step-num">3</div>
              <div className="cp-step-body">
                <h3>Installment schedule</h3>
                <p>Review the schedule and click a row to choose the installment you&rsquo;re collecting for.</p>
                <InstallmentSchedule
                  installments={installments}
                  selectedId={selectedInstallment?.id}
                  onSelect={selectInstallment}
                  loading={installmentsLoading}
                  error={installmentsError}
                  loan={selectedLoan}
                />
              </div>
            </li>

            <li className={`cp-step ${selectedInstallment ? 'active' : ''}`}>
              <div className="cp-step-num">4</div>
              <div className="cp-step-body">
                <h3>Record today&rsquo;s collection</h3>
                <p>Confirm the amount and add an optional note. Notes can be left blank.</p>

                {installmentLoading ? (
                  <div className="cp-state">
                    <div className="cp-spinner" />
                    <span>Loading installment…</span>
                  </div>
                ) : installmentError && !selectedInstallment ? (
                  <div className="cp-state error">
                    <AlertIcon />
                    <span>{installmentError}</span>
                  </div>
                ) : selectedInstallment ? (
                  <form className="cp-form" onSubmit={handleSubmit}>
                    <div className="cp-info-card">
                      <div className="cp-info-card-head">
                        <div>
                          <span className="cp-info-eyebrow">Selected installment</span>
                          <h3>Installment #{selectedInstallment.installmentNo}</h3>
                        </div>
                        <span className={`cp-info-pill cp-status-${installmentStatus}`}>
                          {installmentStatus === 'paid' && 'Settled'}
                          {installmentStatus === 'partial' && 'Partially paid'}
                          {installmentStatus === 'overdue' && 'Overdue'}
                          {installmentStatus === 'pending' && 'Pending'}
                        </span>
                      </div>

                      <div className="cp-info-progress">
                        <div className="cp-info-progress-bar">
                          <span style={{ width: `${progressPct}%` }} />
                        </div>
                        <span className="cp-info-progress-num">{progressPct}%</span>
                      </div>

                      <div className="cp-info-grid">
                        <div>
                          <span>Due date</span>
                          <strong>{formatDate(selectedInstallment.dueDate)}</strong>
                        </div>
                        <div>
                          <span>Due amount</span>
                          <strong>{formatBDT(selectedInstallment.dueAmount)}</strong>
                        </div>
                        <div>
                          <span>Already paid</span>
                          <strong className="ok">{formatBDT(selectedInstallment.paidAmount)}</strong>
                        </div>
                        <div>
                          <span>Remaining</span>
                          <strong className={remainingBalance > 0 ? 'warn' : 'ok'}>
                            {formatBDT(remainingBalance)}
                          </strong>
                        </div>
                        <div>
                          <span>Loan</span>
                          <strong>{selectedInstallment.loan?.code || `#${selectedInstallment.loanId}`}</strong>
                        </div>
                        <div>
                          <span>Member</span>
                          <strong>{selectedMember?.name || selectedInstallment.member?.name || '—'}</strong>
                        </div>
                      </div>
                    </div>

                    {success ? (
                      <div className={`cp-success-banner ${success.settled ? 'settled' : 'partial'}`}>
                        <div className="cp-success-icon" aria-hidden><CheckIcon /></div>
                        <div>
                          <strong>
                            {success.settled
                              ? 'Installment settled successfully'
                              : 'Partial payment recorded'}
                          </strong>
                          <span>
                            {formatBDT(success.amount)} on {success.date}
                            {success.note ? ` · Note: ${success.note}` : ''}
                          </span>
                        </div>
                      </div>
                    ) : null}

                    <div className="cp-form-grid">
                      <div className="cp-field">
                        <label htmlFor="cp-amount">
                          <WalletIcon />
                          <span>Amount collected today</span>
                        </label>
                        <div className="cp-amount-wrap">
                          <span className="cp-amount-prefix" aria-hidden>৳</span>
                          <input
                            id="cp-amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            max={remainingBalance || undefined}
                            value={amount}
                            onChange={(event) => {
                              setAmount(event.target.value);
                              setSubmitError('');
                            }}
                            placeholder="0.00"
                            required
                            disabled={submitting}
                          />
                          <button
                            type="button"
                            className="cp-amount-max"
                            onClick={() => setAmount(String(remainingBalance))}
                            disabled={submitting || remainingBalance <= 0}
                            tabIndex={-1}
                          >
                            Max
                          </button>
                        </div>
                        <small className="cp-field-hint">
                          Remaining balance: <strong>{formatBDT(remainingBalance)}</strong>
                        </small>
                      </div>

                      <div className="cp-field">
                        <label htmlFor="cp-date">
                          <CalIcon />
                          <span>Payment date</span>
                        </label>
                        <input
                          id="cp-date"
                          type="date"
                          value={paymentDate}
                          onChange={(event) => {
                            setPaymentDate(event.target.value);
                            setSubmitError('');
                          }}
                          required
                          disabled={submitting}
                        />
                      </div>

                      <div className="cp-field cp-field-full">
                        <label htmlFor="cp-note">
                          <NoteIcon />
                          <span>Note (optional)</span>
                        </label>
                        <textarea
                          id="cp-note"
                          rows={3}
                          value={note}
                          onChange={(event) => setNote(event.target.value)}
                          placeholder="Anything worth noting for this collection…"
                          disabled={submitting}
                          maxLength={500}
                        />
                        <small className="cp-field-hint">
                          Notes are optional. Leave blank if there&rsquo;s nothing to add.
                        </small>
                      </div>
                    </div>

                    {submitError ? (
                      <div className="cp-form-error" role="alert">
                        <AlertIcon />
                        <span>{submitError}</span>
                      </div>
                    ) : null}

                    <div className="cp-form-actions">
                      <button
                        type="button"
                        className="cp-btn-ghost"
                        onClick={handleResetForm}
                        disabled={submitting}
                      >
                        <ResetIcon />
                        <span>Reset form</span>
                      </button>
                      <button
                        type="submit"
                        className="cp-btn-primary"
                        disabled={submitting || remainingBalance <= 0}
                      >
                        {submitting ? (
                          <>
                            <span className="cp-spinner inline" />
                            <span>Saving…</span>
                          </>
                        ) : (
                          <>
                            <CheckIcon />
                            <span>Save payment</span>
                          </>
                        )}
                      </button>
                    </div>
                  </form>
                ) : (
                  <div className="cp-state muted">
                    {selectedLoan
                      ? 'Choose an installment above to record a collection.'
                      : 'Pick a loan to see its installment schedule.'}
                  </div>
                )}
              </div>
            </li>
          </ol>
        </section>

        <aside className="cp-aside">
          <div className="cp-card cp-aside-card">
            <span className="cp-aside-eyebrow">Summary</span>
            {selectedMember || selectedLoan || selectedInstallment ? (
              <MemberSummary member={selectedMember} loan={selectedLoan} />
            ) : (
              <p className="cp-aside-empty">
                Your selections and the active installment will appear here.
              </p>
            )}
          </div>

          {selectedInstallment ? (
            <div className="cp-card cp-aside-card">
              <span className="cp-aside-eyebrow">Installment snapshot</span>
              <div className="cp-aside-stats">
                <div>
                  <span>Installment #</span>
                  <strong>{selectedInstallment.installmentNo}</strong>
                </div>
                <div>
                  <span>Due date</span>
                  <strong>{formatDate(selectedInstallment.dueDate)}</strong>
                </div>
                <div>
                  <span>Due amount</span>
                  <strong>{formatBDT(selectedInstallment.dueAmount)}</strong>
                </div>
                <div>
                  <span>Already paid</span>
                  <strong className="ok">{formatBDT(selectedInstallment.paidAmount)}</strong>
                </div>
                <div>
                  <span>Remaining</span>
                  <strong className={remainingBalance > 0 ? 'warn' : 'ok'}>
                    {formatBDT(remainingBalance)}
                  </strong>
                </div>
                <div>
                  <span>Today&rsquo;s collection</span>
                  <strong>{amount ? formatBDT(Number(amount)) : '—'}</strong>
                </div>
                <div>
                  <span>Payment date</span>
                  <strong>{paymentDate || '—'}</strong>
                </div>
                <div className="cp-aside-full">
                  <span>Note</span>
                  <strong>{safeTrim(note) || '—'}</strong>
                </div>
              </div>

              <div className="cp-aside-progress">
                <span>Settlement progress</span>
                <div className="cp-aside-progress-bar">
                  <span style={{ width: `${progressPct}%` }} />
                </div>
                <strong>{progressPct}%</strong>
              </div>
            </div>
          ) : null}

          <div className="cp-card cp-aside-card cp-tips">
            <span className="cp-aside-eyebrow">Quick tips</span>
            <ul>
              <li>Search by name, member ID, phone, or loan code &mdash; live suggestions appear as you type.</li>
              <li>The installment schedule auto-loads when you pick a loan. Click a row to choose the correct installment.</li>
              <li>The note field is optional &mdash; you can save without adding anything.</li>
              <li>If today&rsquo;s collection settles the installment, the next pending one loads automatically.</li>
            </ul>
          </div>
        </aside>
      </main>
    </div>
  );
}
