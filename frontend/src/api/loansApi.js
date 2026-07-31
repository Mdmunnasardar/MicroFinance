import { apiClient } from './client';

export const loansApi = {
  list: (params) => apiClient.get('/loans', { params }),
  get: (id) => apiClient.get(`/loans/${id}`),
  create: (payload) => apiClient.post('/loans', payload),
  update: (id, payload) => apiClient.put(`/loans/${id}`, payload),
  remove: (id) => apiClient.delete(`/loans/${id}`),
};
