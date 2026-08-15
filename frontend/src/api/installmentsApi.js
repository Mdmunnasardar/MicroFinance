import { apiClient } from './client';

export const installmentsApi = {
  // List all installments with filters (works with member_id query param)
  list: (params) => apiClient.get('/installments', { params }),
  
  // Get single installment
  get: (id) => apiClient.get(`/installments/${id}`),
  
  // Update installment (collect payment)
  update: (id, payload) => apiClient.put(`/installments/${id}`, payload),
  
  // Collect payment (alias for update)
  pay: (id, payload) => apiClient.put(`/installments/${id}`, payload),
  
  // Delete installment
  remove: (id) => apiClient.delete(`/installments/${id}`),
  
  // Get today's installments
  getToday: () => apiClient.get('/installments/today'),
  
  // Get overdue installments
  getOverdue: () => apiClient.get('/installments/overdue'),
  
  // Get installments by member - uses query parameter
  getByMember: (memberId) => apiClient.get('/installments', { 
    params: { member_id: memberId } 
  }),
};