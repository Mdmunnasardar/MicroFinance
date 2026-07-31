import { useNavigate, useParams } from 'react-router-dom';
import { useCreateMember, useMember, useUpdateMember } from '../hooks/useMembers';
import MemberForm from '../components/members/MemberForm';

export default function MemberFormPage() {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEdit = !!id;
  const memberId = Number(id);

  const member = useMember(memberId);
  const create = useCreateMember();
  const update = useUpdateMember(memberId);

  if (isEdit && member.isLoading) return <p>Loading…</p>;
  if (isEdit && member.isError) return <p>Failed to load member.</p>;

  return (
    <MemberForm
      initial={member.data}
      submitting={create.isPending || update.isPending}
      onSubmit={async (values) => {
        if (isEdit) await update.mutateAsync(values);
        else await create.mutateAsync(values);
        navigate('/');
      }}
    />
  );
}