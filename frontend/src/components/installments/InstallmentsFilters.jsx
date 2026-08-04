export default function InstallmentsFilters({ filters, onChange, onReset }) {
  const update = (patch) => onChange({ ...filters, ...patch });
  return (
    <div className="installments-filters">
      <div className="field" style={{ marginBottom: 0 }}>
        <label htmlFor="installments-q">Search</label>
        <input
          id="installments-q"
          type="search"
          placeholder="Member, member code, or loan code"
          value={filters.q}
          onChange={(event) => update({ q: event.target.value })}
        />
      </div>
      <div className="field" style={{ marginBottom: 0 }}>
        <label htmlFor="installments-status">Status</label>
        <select
          id="installments-status"
          value={filters.status}
          onChange={(event) => update({ status: event.target.value })}
        >
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
        </select>
      </div>
      <button type="button" className="btn-quick btn-quick-info" onClick={onReset}>
        Reset
      </button>
    </div>
  );
}