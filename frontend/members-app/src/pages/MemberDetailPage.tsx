import { Link, useParams } from 'react-router-dom';
import { useMember } from '../hooks/useMembers';

export default function MemberDetailPage() {
  const { id } = useParams();
  const memberId = Number(id);
  const { data, isLoading, isError } = useMember(memberId);

  if (isLoading) return <p>Loading…</p>;
  if (isError || !data) return <p>Member not found.</p>;

  return (
    <div className="card space-y-2">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">{data.name}</h1>
        <Link to={`${memberId}/edit`} className="btn-ghost">Edit</Link>
      </div>
      <p className="text-sm text-slate-500">{data.code} · joined {new Date(data.joinedAt).toDateString()}</p>
      <p>Phone: {data.phone}</p>
      {data.email && <p>Email: {data.email}</p>}
      {data.committeeName && <p>Committee: {data.committeeName}</p>}
    </div>
  );
}