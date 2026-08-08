import InstallmentsTable from '../../components/installments/InstallmentsTable';
import InstallmentsFilters from '../../components/installments/InstallmentsFilters';

export default function ScheduleTab({
  items,
  meta,
  page,
  perPage,
  filters,
  loading,
  error,
  canManage,
  onUpdateFilters,
  onResetFilters,
  onChangePage,
  onChangePageSize,
  onView,
  onCollect,
  onEdit,
  onDelete,
}) {
  const counts = { total: meta?.total ?? items?.length ?? 0 };

  return (
    <div className="inst-schedule-tab">
      <InstallmentsFilters
        filters={filters}
        onChange={onUpdateFilters}
        onReset={onResetFilters}
        counts={counts}
      />

      <InstallmentsTable
        items={items}
        onView={onView}
        onCollect={canManage ? onCollect : undefined}
        onPay={canManage ? onCollect : undefined}
        onEdit={canManage ? onEdit : undefined}
        onDelete={canManage ? onDelete : undefined}
      />

      {loading ? (
        <div className="inst-pagination-loading">Loading more installments…</div>
      ) : null}

      {meta?.last_page > 1 ? (
        <div className="inst-pagination">
          <button type="button" className="inst-page-btn" onClick={() => onChangePage(Math.max(1, page - 1))} disabled={page <= 1 || loading}>
            Previous
          </button>
          <span className="inst-page-status">Page <strong>{page}</strong> of <strong>{meta.last_page}</strong></span>
          <button type="button" className="inst-page-btn" onClick={() => onChangePage(Math.min(meta.last_page, page + 1))} disabled={page >= meta.last_page || loading}>
            Next
          </button>
          <label className="inst-page-size">
            <span>Rows</span>
            <select value={perPage} onChange={(event) => onChangePageSize(Number(event.target.value) || 25)}>
              {[10, 25, 50, 100].map((size) => (
                <option key={size} value={size}>{size}</option>
              ))}
            </select>
          </label>
        </div>
      ) : null}

      {error ? (
        <div className="inst-pagination-error" role="alert">{error}</div>
      ) : null}
    </div>
  );
}