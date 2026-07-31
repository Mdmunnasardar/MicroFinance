import { useNavigate, useParams } from 'react-router-dom';
import { useCreateLoan, useLoan, useUpdateLoan } from '../hooks/useLoans';
import LoanForm from '../components/loans/LoanForm';

export default function LoanFormPage() {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEdit = !!id;
  const loanId = Number(id);

  const loan = useLoan(loanId);
  const create = useCreateLoan();
  const update = useUpdateLoan(loanId);

  if (isEdit && loan.isLoading) return <p>Loading…</p>;
  if (isEdit && loan.isError) return <p>Failed to load loan.</p>;

  return (
    <LoanForm
      initial={loan.data}
      submitting={create.isPending || update.isPending}
      onSubmit={async (values) => {
        if (isEdit) await update.mutateAsync(values);
        else await create.mutateAsync(values);
        navigate('/');
      }}
    />
  );
}