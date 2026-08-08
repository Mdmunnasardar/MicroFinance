import { useCallback, useEffect, useMemo, useState } from 'react';
import MemberPicker from '../../components/installments/MemberPicker';
import LoanPicker from '../../components/installments/LoanPicker';
import InstallmentSchedule from '../../components/installments/InstallmentSchedule';
import { installmentsApi } from '../../api/installmentsApi';
import { buildStats, formatDateShort } from '../../utils/installment';
import { formatMoney } from '../../utils/formatMoney';

const UserIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
    <circle cx="12" cy="7" r="4" />
  </svg>
);

const StepDot = ({ state, label }) => (
  <div className={`inst-collect-step is-${state}`}>
    <span className="inst-collect-step-no" aria-hidden>●</span>
    <span className="inst-collect-step-label">{label}</span>
  </div>
);

export default function CollectTab({ onCollect }) {
  const [member, setMember] = useState(null);
  const [installments, setInstallments] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [selectedLoanId, setSelectedLoanId] = useState(null);
  const [selectedInstallmentId, setSelectedInstallmentId] = useState(null);

  useEffect(() => {
    if (!member) {
      setInstallments([]);
      setSelectedLoanId(null);
      setSelectedInstallmentId(null);
      return undefined;
    }
    let cancelled = false;
    async function load() {
      setLoading(true);
      setError('');
      try {
        const response = await installmentsApi.list({ member_id: member.id, per_page: 200 });
        const list = Array.isArray(response?.data) ? response.data : Array.isArray(response) ? response : [];
        if (!cancelled) {
          setInstallments(list);
          setSelectedLoanId(null);
          setSelectedInstallmentId(null);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err?.message || 'Failed to load installments.');
          setInstallments([]);
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    load();
    return () => { cancelled = true; };
  }, [member]);

  const stats = useMemo(() => buildStats(installments), [installments]);
  const filteredInstallments = useMemo(() => {
    if (!selectedLoanId) return [];
    return installments.filter((i) => (i.loanId || i.loan?.id) === selectedLoanId);
  }, [installments, selectedLoanId]);

  const handleSelectLoan = useCallback((loan) => {
    setSelectedLoanId(loan.id);
    setSelectedInstallmentId(null);
  }, []);

  const handleView = useCallback((item) => {
    setSelectedInstallmentId(item.id);
  }, []);

  const handleCollect = useCallback((item) => {
    if (onCollect) onCollect(item);
  }, [onCollect]);

  const stepState = (member ? 2 : 1) + (selectedLoanId ? 1 : 0);

  return (
    <div className="inst-collect-tab">
      <div className="inst-collect-stepper" role="list">
        <StepDot state={member ? 'done' : 'active'} label="1. Choose member" />
        <StepDot state={selectedLoanId ? 'done' : member ? 'active' : 'idle'} label="2. Pick a loan" />
        <StepDot state={selectedLoanId ? 'active' : 'idle'} label="3. Collect payment" />
      </div>

      <div className="inst-collect-grid">
        <section className="inst-collect-card">
          <header className="inst-collect-card-head">
            <div className="inst-collect-card-head-left">
              <span className="inst-collect-icon" aria-hidden><UserIcon /></span>
              <div>
                <h3>Step 1 — Pick a member</h3>
                <p>Search by name or member code to see their loans.</p>
              </div>
            </div>
          </header>
          <MemberPicker
            value={member}
            onChange={setMember}
          />
        </section>

        {member ? (
          <section className="inst-collect-card">
            <header className="inst-collect-card-head">
              <div className="inst-collect-card-head-left">
                <span className="inst-collect-icon" aria-hidden>৳</span>
                <div>
                  <h3>Step 2 — Pick a loan</h3>
                  <p>{member.name || member.full_name}'s loans, ordered by urgency.</p>
                </div>
              </div>
            </header>
            <LoanPicker
              installments={installments}
              loading={loading}
              error={error}
              selectedLoanId={selectedLoanId}
              onSelect={handleSelectLoan}
            />
          </section>
        ) : null}

        {member && selectedLoanId ? (
          <section className="inst-collect-card inst-collect-card-wide">
            <header className="inst-collect-card-head">
              <div className="inst-collect-card-head-left">
                <span className="inst-collect-icon" aria-hidden>↻</span>
                <div>
                  <h3>Step 3 — Collect payment</h3>
                  <p>Tap <strong>Collect</strong> on any installment to record a payment.</p>
                </div>
              </div>
              <div className="inst-collect-card-stats">
                <span><strong>{stats.total}</strong> installments</span>
                <span><strong>{formatMoney(stats.dueTotal)}</strong> due</span>
                <span><strong>{formatMoney(stats.balanceTotal)}</strong> outstanding</span>
              </div>
            </header>
            <InstallmentSchedule
              items={filteredInstallments}
              loading={loading}
              error={error}
              selectedId={selectedInstallmentId}
              onSelect={setSelectedInstallmentId}
              onCollect={handleCollect}
              canCollect
              emptyMessage="This loan has no installments yet."
            />
          </section>
        ) : null}
      </div>
    </div>
  );
}
