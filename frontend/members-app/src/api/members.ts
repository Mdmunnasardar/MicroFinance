import { apiGet, apiSend } from './client';
import type { Member, MemberFilters, MemberListResponse } from '../types';

function qs(filters: MemberFilters): string {
  const p = new URLSearchParams();
  if (filters.search) p.set('search', filters.search);
  if (filters.committeeId) p.set('committee_id', String(filters.committeeId));
  if (filters.status) p.set('status', filters.status);
  if (filters.page) p.set('page', String(filters.page));
  if (filters.pageSize) p.set('page_size', String(filters.pageSize));
  const s = p.toString();
  return s ? `?${s}` : '';
}

export function fetchMembers(filters: MemberFilters = {}): Promise<MemberListResponse> {
  return apiGet<MemberListResponse>(`/members${qs(filters)}`);
}
export function fetchMember(id: number): Promise<Member> {
  return apiGet<Member>(`/members/${id}`);
}
export function createMember(input: Omit<Member, 'id' | 'code' | 'joinedAt'>): Promise<Member> {
  return apiSend<Member>('POST', '/members', input);
}
export function updateMember(id: number, patch: Partial<Member>): Promise<Member> {
  return apiSend<Member>('PUT', `/members/${id}`, patch);
}
export function deleteMember(id: number): Promise<void> {
  return apiSend<void>('DELETE', `/members/${id}`);
}