import { apiClient } from './client';

export const savingsApi = {
  list: (params) => apiClient.get('/savings', { params }),
  byMember: (memberId) => apiClient.get(`/savings/member/${memberId}`),
  deposit: (payload) => apiClient.post('/savings/deposits', payload),
  withdraw: (payload) => apiClient.post('/savings/withdrawals', payload),
  transactions: (params) => apiClient.get('/savings/transactions', { params }),
};
