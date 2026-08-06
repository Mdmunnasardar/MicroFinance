import { apiClient } from './client';

export const savingsApi = {
  list: (params) => apiClient.get('/savings', { params }),
  get: (id) => apiClient.get(`/savings/${id}`),
  create: (payload) => apiClient.post('/savings', payload),
  update: (id, payload) => apiClient.put(`/savings/${id}`, payload),
  remove: (id) => apiClient.delete(`/savings/${id}`),
  byMember: (memberId) => apiClient.get(`/savings/member/${memberId}`),
  deposit: (payload) => apiClient.post('/savings/deposits', payload),
  withdraw: (payload) => apiClient.post('/savings/withdrawals', payload),
  transactions: (params) => apiClient.get('/savings/transactions', { params }),
};
