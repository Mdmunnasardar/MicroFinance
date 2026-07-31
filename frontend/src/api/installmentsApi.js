import { apiClient } from './client';

export const installmentsApi = {
  list: (params) => apiClient.get('/installments', { params }),
  get: (id) => apiClient.get(`/installments/${id}`),
  update: (id, payload) => apiClient.put(`/installments/${id}`, payload),
  remove: (id) => apiClient.delete(`/installments/${id}`),
};
