import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { installmentsApi } from '../api/installmentsApi';
import { useAuth } from '../hooks/useAuth';

/* ------------------------------ Helpers ------------------------------ */

function formatMoney(value) {
  const n = Number(value) || 0;
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(n);
}

function todayISO() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function initials(name) {
  if (!name) return '??';
  const parts = String(name).trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
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

const UserIcon = () => (
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
    <circle cx="12" cy="7" r="4" />
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

/* --------------------------- Member Picker ---------------------------- */

function MemberPicker({ onPick, disabled, value, onClear }) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [open, setOpen] = useState(false);
  const debounceRef = useRef(null);
  const containerRef = useRef(null);

  // Close dropdown on outside click
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
    const t = (term || '').trim();
    if (t.length < 2) {
      setResults([]);
      setError('');
      return;
    }
    setLoading(true);
    setError('');
    try {
      // Search across all installments (any status) by member name/code or loan code
      const response = await installmentsApi.list({ q: t, per_page: 200 });
      const items = Array.isArray(response.data) ? response.data : [];
      // Dedupe by member id, preserve first occurrence
      const seen = new Map();
      for (const it of items) {
        const mid = it?.member?.id;
        if (!mid) continue;
        if (!seen.has(mid)) {
          seen.set(mid, {
            id: mid,
            name: it.member?.name || '',
            code: it.member?.code || '',
          });
        }
      }
      setResults(Array.from(seen.values()).slice(0, 12));
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
    }, 300);
    return () => debounceRef.current && clearTimeout(debounceRef.current);
  }, [query, searchMembers]);

  // If a value is selected, show it as the trigger
  if (value) {
    return (
      <div className="cp-picker-selected">
        <div className="cp-picker-avatar" aria-hidden>{initials(value.name)}</div>
        <div className="cp-picker-selected-text">
          <strong>{value.name}</strong>
          <span>{value.code ? `Member code: ${value.code}` : `Member ID: ${value.id}`}</span>
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
          placeholder="Search member by name, code, or loan code…"
          value={query}
          onChange={(event) => {
            setQuery(event.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          disabled={disabled}
          aria-label="Search member"
          autoComplete="off"
        />
        {query ? (
          <button
            type="button"
            className="cp-picker-clear"
            onClick={() => { setQuery(''); setResults([]); }}
            aria-label="Clear search"
            tabIndex={-1}
          >
            <CloseIcon />
          </button>
        ) : null}
      </div>

      {open ? (
        <div className="cp-picker-dropdown" role="listbox">
          {loading ? (
            <div className="cp-picker-state">
              <div className="cp-spinner" />
              <span>Searching…</span>
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
                : <>No members found for "{query}".</>}
            </div>
          ) : (
            <ul className="cp-picker-list">
              {results.map((member) => (
                <li key={member.id}>
                  <button
                    type="button"
                    className="cp-picker-item"
                    onClick={() => {
                      onPick(member);
                      setOpen(false);
                      setQuery('');
                    }}
                  >
                    <div className="cp-picker-avatar small" aria-hidden>{initials(member.name)}</div>
                    <div className="cp-picker-item-text">
                      <strong>{member.name}</strong>
                      <span>{member.code ? `Code: ${member.code}` : `ID: ${member.id}`}</span>
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      ) : null}
    </div>
  );
}

/* ----------------------------- Loan Picker --------------------------- */

function LoanPicker({ loans, value, onPick, loading, error, memberId }) {
  if (!memberId) return null;
  return (
    <div className="cp-loan-picker">
      <div className="cp-section-label">
        <LoanIcon />
        <span>Active loans</span>
      </div>

      {loading ? (
        <div className="cp-state">
          <div className="cp-spinner" />
          <span>Loading member loans…</span>
        </div>
      ) : error ? (
        <div className="cp-state error">
          <AlertIcon />
          <span>{error}</span>
        </div>
      ) : loans.length === 0 ? (
        <div className="cp-state muted">No active loans found for this member.</div>
      ) : (
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
                    <strong>{formatMoney(loan.totalDue)}</strong>
                  </div>
                  <div>
                    <span>Paid</span>
                    <strong className="ok">{formatMoney(loan.totalPaid)}</strong>
                  </div>
                  <div>
                    <span>Outstanding</span>
                    <strong className={loan.outstanding > 0 ? 'warn' : 'ok'}>
                      {formatMoney(loan.outstanding)}
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
      )}
    </div>
  );
}

/* --------------------- Installment Info Card ----------------------- */

function InstallmentInfo({ installment }) {
  if (!installment) return null;
  const due = Number(installment.dueAmount) || 0;
  const paid = Number(installment.paidAmount) || 0;
  const remaining = Math.max(0, due - paid);
  const progress = due > 0 ? Math.min(100, Math.round((paid / due) * 100)) : 0;
  return (
    <div className="cp-info-card">
      <div className="cp-info-card-head">
        <div>
          <span className="cp-info-eyebrow">Current installment</span>
          <h3>Installment #{installment.installmentNo}</h3>
        </div>
        <span className={`cp-info-pill ${installment.status === 'paid' ? 'ok' : (paid > 0 ? 'partial' : 'pending')}`}>
          {installment.status === 'paid' ? 'Settled' : paid > 0 ? 'Partially paid' : 'Pending'}
        </span>
      </div>

      <div className="cp-info-progress">
        <div className="cp-info-progress-bar">
          <span style={{ width: `${progress}%` }} />
        </div>
        <span className="cp-info-progress-num">{progress}%</span>
      </div>

      <div className="cp-info-grid">
        <div>
          <span>Due amount</span>
          <strong>{formatMoney(due)}</strong>
        </div>
        <div>
          <span>Already paid</span>
          <strong className="ok">{formatMoney(paid)}</strong>
        </div>
        <div>
          <span>Remaining</span>
          <strong className={remaining > 0 ? 'warn' : 'ok'}>{formatMoney(remaining)}</strong>
        </div>
        <div>
          <span>Loan</span>
          <strong>{installment.loan?.code || `#${installment.loanId}`}</strong>
        </div>
      </div>
    </div>
  );
}

/* --------------------- Member / Loan Summary ----------------------- */

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
          <span>{loan.totalDue ? `${formatMoney(loan.totalPaid)} paid of ${formatMoney(loan.totalDue)}` : ''}</span>
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
  const [currentInstallment, setCurrentInstallment] = useState(null);
  const [installmentLoading, setInstallmentLoading] = useState(false);
  const [installmentError, setInstallmentError] = useState('');

  // Form state
  const [amount, setAmount] = useState('');
  const [paymentDate, setPaymentDate] = useState(todayISO());
  const [note, setNote] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState('');
  const [success, setSuccess] = useState(null);

  // Feedback
  const [toast, setToast] = useState(null);
  useEffect(() => {
    if (!toast) return undefined;
    const timer = setTimeout(() => setToast(null), 4500);
    return () => clearTimeout(timer);
  }, [toast]);

  useEffect(() => { document.title = 'Collect Payment · MicroFinance'; }, []);

  /* ----------- Load member's active loans + installments ----------- */

  const loadMemberData = useCallback(async (memberId) => {
    setLoansLoading(true);
    setLoansError('');
    setMemberLoans([]);
    setSelectedLoan(null);
    setCurrentInstallment(null);
    setInstallmentError('');
    try {
      // Fetch ALL installments for this member (any status), then group by loan
      const response = await installmentsApi.list({ member_id: memberId, per_page: 200 });
      const items = Array.isArray(response.data) ? response.data : [];

      // Group by loan id
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
            installments: [],
          });
        }
        const entry = loanMap.get(lid);
        entry.totalDue += Number(it.dueAmount) || 0;
        entry.totalPaid += Number(it.paidAmount) || 0;
        entry.outstanding += Number(it.balance) || 0;
        entry.installments.push(it);
        if (it.status !== 'paid') entry.pendingCount += 1;
      }

      // Only keep loans with at least one pending installment (i.e., active collections)
      const active = Array.from(loanMap.values())
        .filter((l) => l.pendingCount > 0)
        .sort((a, b) => b.id - a.id);

      setMemberLoans(active);
      if (active.length === 0) {
        setLoansError('This member has no loans with pending installments.');
      }
    } catch (err) {
      setLoansError(err.message || 'Failed to load member loans.');
    } finally {
      setLoansLoading(false);
    }
  }, []);

  /* ---------- When a loan is picked, fetch latest installment -------- */

  const loadLoanInstallment = useCallback(async (loan) => {
    setInstallmentLoading(true);
    setInstallmentError('');
    setCurrentInstallment(null);
    setAmount('');
    setNote('');
    setSubmitError('');
    setSuccess(null);
    try {
      // Re-query installments for this loan and find the next pending one
      const response = await installmentsApi.list({ loan_id: loan.id, per_page: 200 });
      const items = Array.isArray(response.data) ? response.data : [];

      // Sort by installment number, take next pending
      const sorted = [...items].sort((a, b) => (a.installmentNo || 0) - (b.installmentNo || 0));
      const nextPending = sorted.find((it) => it.status !== 'paid');

      if (!nextPending) {
        setInstallmentError('No pending installments found for this loan.');
        return;
      }

      // Optionally re-fetch single installment for the freshest data
      let fresh = nextPending;
      try {
        const single = await installmentsApi.get(nextPending.id);
        if (single?.data) fresh = single.data;
      } catch {
        // Fall back to the list data
      }

      setCurrentInstallment(fresh);
      // Pre-fill amount with remaining balance
      const remaining = Math.max(0, (Number(fresh.dueAmount) || 0) - (Number(fresh.paidAmount) || 0));
      setAmount(remaining > 0 ? String(remaining) : '');
    } catch (err) {
      setInstallmentError(err.message || 'Failed to load installment details.');
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
    setCurrentInstallment(null);
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
    loadLoanInstallment(loan);
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
    if (!currentInstallment) return;
    const value = Number(amount);
    if (!value || value <= 0) {
      setSubmitError('Please enter a valid amount greater than zero.');
      return;
    }
    const due = Number(currentInstallment.dueAmount) || 0;
    const alreadyPaid = Number(currentInstallment.paidAmount) || 0;
    const maxAddable = Math.max(0, due - alreadyPaid);
    if (value > maxAddable) {
      setSubmitError(`Amount exceeds the remaining balance of ${formatMoney(maxAddable)}.`);
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
      // Reuse existing API + business logic — do NOT change calculations.
      await installmentsApi.pay(currentInstallment.id, {
        paidAmount: newPaidTotal,
        paidDate: paymentDate,
        notes: note || null,
        collectedBy: user?.id ?? null,
      });

      // Refresh this installment + (after settle) load the next one
      let fresh;
      try {
        const single = await installmentsApi.get(currentInstallment.id);
        fresh = single?.data ?? null;
      } catch {
        fresh = null;
      }

      const settled = !fresh
        || fresh.status === 'paid'
        || (Number(fresh.balance) || 0) <= 0;

      setToast({
        kind: 'success',
        message: settled
          ? `Payment of ${formatMoney(value)} recorded. Installment settled.`
          : `Partial payment of ${formatMoney(value)} recorded for installment #${currentInstallment.installmentNo}.`,
      });
      setSuccess({
        amount: value,
        date: paymentDate,
        note: note || null,
        settled,
        installmentNo: currentInstallment.installmentNo,
      });

      // If this installment is settled, look for the next pending one on the same loan.
      if (settled) {
        // Wait a moment so the toast is visible, then auto-advance
        setTimeout(async () => {
          await loadLoanInstallment(selectedLoan);
          await loadMemberData(selectedMember.id);
        }, 600);
      } else {
        // Update the current installment in place
        if (fresh) setCurrentInstallment(fresh);
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
    if (!currentInstallment) return 0;
    const due = Number(currentInstallment.dueAmount) || 0;
    const paid = Number(currentInstallment.paidAmount) || 0;
    return Math.max(0, due - paid);
  }, [currentInstallment]);

  const progressPct = useMemo(() => {
    if (!currentInstallment) return 0;
    const due = Number(currentInstallment.dueAmount) || 0;
    const paid = Number(currentInstallment.paidAmount) || 0;
    if (due <= 0) return 0;
    return Math.min(100, Math.round((paid / due) * 100));
  }, [currentInstallment]);

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
            Field Officer · Daily Collection
          </span>
          <h1>
            Collect <span className="accent">Payment</span>
          </h1>
          <p>Search a member, pick their active loan, and record today's collection in seconds.</p>
        </div>
        <div className="cp-header-meta">
          <div className="cp-header-meta-item">
            <span>Today</span>
            <strong>{new Date().toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })}</strong>
          </div>
          <div className="cp-header-meta-item">
            <span>Collector</span>
            <strong>{user?.name || user?.username || 'Field Officer'}</strong>
          </div>
        </div>
      </header>

      <main className="cp-layout">
        {/* LEFT — Workflow steps */}
        <section className="cp-card cp-card-main">
          <ol className="cp-steps">
            <li className={`cp-step ${selectedMember ? 'done' : 'active'}`}>
              <div className="cp-step-num">1</div>
              <div className="cp-step-body">
                <h3>Select member</h3>
                <p>Search by name, member code, or loan code.</p>
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
                <p>Loans with pending installments are shown.</p>
                <LoanPicker
                  loans={memberLoans}
                  value={selectedLoan}
                  onPick={handlePickLoan}
                  loading={loansLoading}
                  error={loansError}
                  memberId={selectedMember?.id}
                />
              </div>
            </li>

            <li className={`cp-step ${currentInstallment ? 'active' : ''}`}>
              <div className="cp-step-num">3</div>
              <div className="cp-step-body">
                <h3>Record today's collection</h3>
                <p>Confirm the amount and add an optional note.</p>

                {installmentLoading ? (
                  <div className="cp-state">
                    <div className="cp-spinner" />
                    <span>Loading installment…</span>
                  </div>
                ) : installmentError ? (
                  <div className="cp-state error">
                    <AlertIcon />
                    <span>{installmentError}</span>
                  </div>
                ) : currentInstallment ? (
                  <form className="cp-form" onSubmit={handleSubmit}>
                    <InstallmentInfo installment={currentInstallment} />

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
                            {formatMoney(success.amount)} on {success.date}
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
                          <span className="cp-amount-prefix" aria-hidden>$</span>
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
                          Remaining balance: <strong>{formatMoney(remainingBalance)}</strong>
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
                  <div className="cp-state muted">Pick a loan to see its current installment.</div>
                )}
              </div>
            </li>
          </ol>
        </section>

        {/* RIGHT — Summary sidebar (lightweight, no global Sidebar) */}
        <aside className="cp-aside">
          <div className="cp-card cp-aside-card">
            <span className="cp-aside-eyebrow">Summary</span>
            {selectedMember || selectedLoan || currentInstallment ? (
              <MemberSummary member={selectedMember} loan={selectedLoan} />
            ) : (
              <p className="cp-aside-empty">
                Your selections and the active installment will appear here.
              </p>
            )}
          </div>

          {currentInstallment ? (
            <div className="cp-card cp-aside-card">
              <span className="cp-aside-eyebrow">Installment snapshot</span>
              <div className="cp-aside-stats">
                <div>
                  <span>Installment #</span>
                  <strong>{currentInstallment.installmentNo}</strong>
                </div>
                <div>
                  <span>Due amount</span>
                  <strong>{formatMoney(currentInstallment.dueAmount)}</strong>
                </div>
                <div>
                  <span>Already paid</span>
                  <strong className="ok">{formatMoney(currentInstallment.paidAmount)}</strong>
                </div>
                <div>
                  <span>Remaining</span>
                  <strong className={remainingBalance > 0 ? 'warn' : 'ok'}>
                    {formatMoney(remainingBalance)}
                  </strong>
                </div>
                <div>
                  <span>Today's collection</span>
                  <strong>{amount ? formatMoney(Number(amount)) : '—'}</strong>
                </div>
                <div>
                  <span>Payment date</span>
                  <strong>{paymentDate || '—'}</strong>
                </div>
                <div className="cp-aside-full">
                  <span>Note</span>
                  <strong>{note ? note : '—'}</strong>
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
              <li>Search the member by full name or member code for faster lookup.</li>
              <li>The system auto-fills the remaining balance so you can confirm quickly.</li>
              <li>If today's collection settles the installment, the next one will load automatically.</li>
            </ul>
          </div>
        </aside>
      </main>
    </div>
  );
}
