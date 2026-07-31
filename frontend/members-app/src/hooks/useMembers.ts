import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createMember, deleteMember, fetchMember, fetchMembers, updateMember } from '../api/members';
import type { Member, MemberFilters } from '../types';

export function useMembers(filters: MemberFilters) {
  return useQuery({ queryKey: ['members', filters], queryFn: () => fetchMembers(filters) });
}

export function useMember(id: number) {
  return useQuery({ queryKey: ['members', id], queryFn: () => fetchMember(id), enabled: !!id });
}

export function useCreateMember() {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (input: Omit<Member, 'id' | 'code' | 'joinedAt'>) => createMember(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['members'] }) });
}

export function useUpdateMember(id: number) {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (patch: Partial<Member>) => updateMember(id, patch),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['members'] }) });
}

export function useDeleteMember() {
  const qc = useQueryClient();
  return useMutation({ mutationFn: (id: number) => deleteMember(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['members'] }) });
}