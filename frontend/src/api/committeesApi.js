import { apiClient } from './client';

export const committeesApi = {
  list: (params) => apiClient.get('/committees', { params }),
  get: (id) => apiClient.get(`/committees/${id}`),
  create: (payload) => apiClient.post('/committees', payload),
  update: (id, payload) => apiClient.put(`/committees/${id}`, payload),
  remove: (id) => apiClient.delete(`/committees/${id}`),
};
