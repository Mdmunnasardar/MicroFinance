const STATUS_BADGE = {
  paid: 'status-badge paid',
  pending: 'status-badge pending',
};

const formatMoney = (value) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(Number(value) || 0);

const formatDate = (value) => {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString();
};

export default function InstallmentsTable({ items, onPay, onEdit, onDelete }) {
  if (!items?.length) {
    return <div className="empty">No installments match your filters.</div>;
  }
  return (
    <div className="installments-table-wrap">
      <table className="installments-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Loan</th>
            <th>Due</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Paid Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => {
            const paid = Number(item.paidAmount) || 0;
            const isPaid = item.status === 'paid';
            return (
              <tr key={item.id}>
                <td>#{item.installmentNo}</td>
                <td>
                  <div className="who">{item.member?.name || 'Unknown member'}</div>
                  <div className="meta">{item.member?.code || '—'}</div>
                </td>
                <td>
                  <div className="who">{item.loan?.code || `#${item.loanId}`}</div>
                  <div className="meta">Loan #{item.loanId}</div>
                </td>
                <td>{formatMoney(item.dueAmount)}</td>
                <td>{formatMoney(paid)}</td>
                <td>{formatMoney(item.balance)}</td>
                <td>{formatDate(item.paidDate)}</td>
                <td>
                  <span className={STATUS_BADGE[item.status] || 'status-badge pending'}>
                    {item.status}
                  </span>
                </td>
                <td className="installments-actions">
                  <button type="button" className="row-btn primary" onClick={() => onPay(item)} disabled={isPaid}>
                    Pay
                  </button>
                  <button type="button" className="row-btn" onClick={() => onEdit(item)} disabled={isPaid}>
                    Edit
                  </button>
                  <button type="button" className="row-btn danger" onClick={() => onDelete(item)} disabled={paid > 0}>
                    Delete
                  </button>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}