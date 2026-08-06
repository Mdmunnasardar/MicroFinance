const SearchIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="11" cy="11" r="7" />
    <path d="m20 20-3.5-3.5" />
  </svg>
);

const RotateIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d="M21 12a9 9 0 1 1-3-6.7" />
    <polyline points="21 4 21 10 15 10" />
  </svg>
);

export default function InstallmentsFilters({ filters, onChange, onReset, counts = {} }) {
  const update = (patch) => onChange({ ...filters, ...patch });
  const hasFilters = Boolean(filters.q.trim() || filters.status);

  return (
    <div className="inst-toolbar">
      <label className="inst-search">
        <span className="inst-search-icon" aria-hidden><SearchIcon /></span>
        <input
          type="search"
          placeholder="Search by member, member code, or loan code…"
          value={filters.q}
          onChange={(event) => update({ q: event.target.value })}
          aria-label="Search installments"
        />
        {filters.q ? (
          <button
            type="button"
            className="inst-search-clear"
            aria-label="Clear search"
            onClick={() => update({ q: '' })}
          >
            ×
          </button>
        ) : null}
      </label>

      <div className="inst-chip-group" role="tablist" aria-label="Filter by status">
        <button
          type="button"
          role="tab"
          aria-selected={filters.status === ''}
          className={`inst-chip${filters.status === '' ? ' active' : ''}`}
          onClick={() => update({ status: '' })}
        >
          All
          {typeof counts.total === 'number' ? <span className="chip-count">{counts.total}</span> : null}
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={filters.status === 'pending'}
          className={`inst-chip chip-pending${filters.status === 'pending' ? ' active' : ''}`}
          onClick={() => update({ status: 'pending' })}
        >
          Pending
          {typeof counts.pending === 'number' ? <span className="chip-count">{counts.pending}</span> : null}
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={filters.status === 'paid'}
          className={`inst-chip chip-paid${filters.status === 'paid' ? ' active' : ''}`}
          onClick={() => update({ status: 'paid' })}
        >
          Paid
          {typeof counts.paid === 'number' ? <span className="chip-count">{counts.paid}</span> : null}
        </button>
      </div>

      <button
        type="button"
        className="inst-reset"
        onClick={onReset}
        disabled={!hasFilters}
      >
        <RotateIcon /> Reset
      </button>
    </div>
  );
}