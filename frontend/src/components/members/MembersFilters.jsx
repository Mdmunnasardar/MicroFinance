// MembersFilters — mirrors the row of filters rendered by members/index.php
// lines 137-167 (search / branch / status / submit button). Controlled inputs.
export default function MembersFilters({ value, branches, onChange, onSubmit, onReset }) {
  const update = (patch) => onChange({ ...value, ...patch });
  const hasFilters = Boolean(value.search.trim() || value.branch || value.status);

  const handleSubmit = (event) => {
    event.preventDefault();
    onSubmit();
  };

  return (
    <form className="members-filters" onSubmit={handleSubmit}>
      <div className="members-filter-row">
        <div className="members-filter-col members-filter-col-search">
          <input
            type="text"
            className="form-control"
            placeholder="Search by name, code, phone or NID..."
            value={value.search}
            onChange={(event) => update({ search: event.target.value })}
            aria-label="Search members"
          />
        </div>
        <div className="members-filter-col members-filter-col-branch">
          <select
            className="form-select"
            value={value.branch}
            onChange={(event) => update({ branch: event.target.value })}
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
        <div className="members-filter-col members-filter-col-status">
          <select
            className="form-select"
            value={value.status}
            onChange={(event) => update({ status: event.target.value })}
            aria-label="Filter by status"
          >
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
        <div className="members-filter-col members-filter-col-submit">
          <button type="submit" className="btn btn-primary w-100">
            <i className="fa-solid fa-filter"></i> Filter
          </button>
        </div>
        {hasFilters ? (
          <div className="members-filter-col members-filter-col-reset">
            <button
              type="button"
              className="btn btn-secondary w-100"
              onClick={onReset}
            >
              <i className="fa-solid fa-rotate"></i> Reset
            </button>
          </div>
        ) : null}
      </div>
    </form>
  );
}
