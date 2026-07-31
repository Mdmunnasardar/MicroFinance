import { useForm } from 'react-hook-form';
import { z } from 'zod';
import type { Member } from '../../types';

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  phone: z.string().min(7, 'Phone is required'),
  email: z.string().email().optional().or(z.literal('')),
  address: z.string().optional(),
  committeeId: z.coerce.number().optional(),
  status: z.enum(['active', 'inactive']),
});

type FormValues = z.infer<typeof schema>;

interface Props {
  initial?: Member;
  submitting?: boolean;
  onSubmit: (values: Omit<Member, 'id' | 'code' | 'joinedAt'>) => Promise<void> | void;
}

export default function MemberForm({ initial, submitting, onSubmit }: Props) {
  const { register, handleSubmit, formState: { errors } } = useForm<FormValues>({
    defaultValues: {
      name: initial?.name ?? '',
      phone: initial?.phone ?? '',
      email: initial?.email ?? '',
      address: initial?.address ?? '',
      committeeId: initial?.committeeId ?? undefined,
      status: initial?.status ?? 'active',
    },
  });

  return (
    <form className="card grid grid-cols-1 gap-4 md:grid-cols-2" onSubmit={handleSubmit(async (raw) => {
      const parsed = schema.safeParse(raw);
      if (!parsed.success) return;
      await onSubmit(parsed.data as Omit<Member, 'id' | 'code' | 'joinedAt'>);
    })}>
      <div className="md:col-span-2">
        <h1 className="text-xl font-semibold">{initial ? 'Edit Member' : 'Add Member'}</h1>
      </div>
      <div>
        <label className="label">Name</label>
        <input className="input" {...register('name')} />
        {errors.name && <p className="mt-1 text-xs text-rose-600">{errors.name.message}</p>}
      </div>
      <div>
        <label className="label">Phone</label>
        <input className="input" {...register('phone')} />
        {errors.phone && <p className="mt-1 text-xs text-rose-600">{errors.phone.message}</p>}
      </div>
      <div>
        <label className="label">Email</label>
        <input className="input" type="email" {...register('email')} />
      </div>
      <div>
        <label className="label">Status</label>
        <select className="input" {...register('status')}>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div className="md:col-span-2">
        <label className="label">Address</label>
        <textarea className="input" rows={2} {...register('address')} />
      </div>
      <div className="md:col-span-2 flex justify-end gap-2">
        <button type="submit" disabled={submitting} className="btn-primary">
          {submitting ? 'Saving…' : initial ? 'Save Changes' : 'Create Member'}
        </button>
      </div>
    </form>
  );
}