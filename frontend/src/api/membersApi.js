import { apiClient } from './client';

export const membersApi = {
  list: (params) => apiClient.get('/members', { params }),
  get: (id) => apiClient.get(`/members/${id}`),
  create: (payload) => apiClient.post('/members', payload),
  update: (id, payload) => apiClient.put(`/members/${id}`, payload),
  remove: (id) => apiClient.delete(`/members/${id}`),
  search: (params) => apiClient.get('/members/search', { params }),
};
