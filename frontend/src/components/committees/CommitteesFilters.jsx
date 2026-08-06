// CommitteesFilters — mirrors the row of filters rendered by Committees/index.php
// lines 162-211. Controlled inputs. Uses the .committees-page scoped classes.
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export default function CommitteesFilters({ value, branches, onChange, onSubmit, onReset }) {
  const update = (patch) => onChange({ ...value, ...patch });
  const hasFilters = Boolean(
    value.search.trim() || value.branch_id || value.status !== '' || value.meeting_day
  );

  const handleSubmit = (event) => {
    event.preventDefault();
    onSubmit();
  };

  return (
    <form className="filter-grid" onSubmit={handleSubmit}>
      <div className="filter-group">
        <label className="filter-label">
          <i className="fa-solid fa-search"></i> Search
        </label>
        <div className="filter-input-icon">
          <i className="fa-solid fa-search icon"></i>
          <input
            type="text"
            className="filter-input"
            placeholder="Committee name..."
            value={value.search}
            onChange={(event) => update({ search: event.target.value })}
            aria-label="Search committees"
          />
        </div>
      </div>

      <div className="filter-group">
        <label className="filter-label">
          <i className="fa-solid fa-store"></i> Branch
        </label>
        <select
          className="filter-select"
          value={value.branch_id}
          onChange={(event) => update({ branch_id: event.target.value })}
          aria-label="Filter by branch"
        >
          <option value="">All Branches</option>
          {(branches || []).map((branch) => (
            <option key={branch.branch_id} value={String(branch.branch_id)}>
              {branch.branch_name}
            </option>
          ))}
        </select>
      </div>

      <div className="filter-group">
        <label className="filter-label">
          <i className="fa-solid fa-circle"></i> Status
        </label>
        <select
          className="filter-select"
          value={value.status}
          onChange={(event) => update({ status: event.target.value })}
          aria-label="Filter by status"
        >
          <option value="">All</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>

      <div className="filter-group">
        <label className="filter-label">
          <i className="fa-solid fa-calendar-day"></i> Meeting Day
        </label>
        <select
          className="filter-select"
          value={value.meeting_day}
          onChange={(event) => update({ meeting_day: event.target.value })}
          aria-label="Filter by meeting day"
        >
          <option value="">All Days</option>
          {DAYS.map((day) => (
            <option key={day} value={day}>{day}</option>
          ))}
        </select>
      </div>

      <div className="filter-group">
        <button type="submit" className="btn btn-primary btn-block">
          <i className="fa-solid fa-filter"></i> Apply Filter
        </button>
      </div>

      {hasFilters ? (
        <div className="filter-group">
          <button type="button" className="btn btn-secondary btn-block" onClick={onReset}>
            <i className="fa-solid fa-rotate"></i> Reset
          </button>
        </div>
      ) : null}
    </form>
  );
}