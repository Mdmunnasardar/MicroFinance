// LoansFilters — search/status filter row that mirrors the implicit filter
// row in loans/index.php. The PHP list doesn't ship a built-in filter form,
// so we offer the same controls the LoansController JSON API supports.

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'closed', label: 'Closed' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'written_off', label: 'Written Off' },
];

export default function LoansFilters({ value, onChange, onSubmit, onReset }) {
  const update = (patch) => onChange({ ...value, ...patch });
  const hasFilters = Boolean(value.search.trim() || value.status);

  const handleSubmit = (event) => {
    event.preventDefault();
    onSubmit();
  };

  return (
    <form className="loans-filter-grid" onSubmit={handleSubmit}>
      <div className="loans-filter-group">
        <label className="loans-filter-label">
          <i className="fa-solid fa-search"></i> Search
        </label>
        <input
          type="text"
          className="loans-filter-input"
          placeholder="Loan code, member, purpose..."
          value={value.search}
          onChange={(event) => update({ search: event.target.value })}
          aria-label="Search loans"
        />
      </div>

      <div className="loans-filter-group">
        <label className="loans-filter-label">
          <i className="fa-solid fa-circle-info"></i> Status
        </label>
        <select
          className="loans-filter-select"
          value={value.status}
          onChange={(event) => update({ status: event.target.value })}
          aria-label="Filter by status"
        >
          {STATUS_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
      </div>

      <div className="loans-filter-group">
        <button type="submit" className="btn btn-primary" style={{ width: '100%' }}>
          <i className="fa-solid fa-filter"></i> Apply Filter
        </button>
      </div>

      {hasFilters ? (
        <div className="loans-filter-group">
          <button type="button" className="btn btn-secondary" style={{ width: '100%' }} onClick={onReset}>
            <i className="fa-solid fa-rotate"></i> Reset
          </button>
        </div>
      ) : null}
    </form>
  );
}
