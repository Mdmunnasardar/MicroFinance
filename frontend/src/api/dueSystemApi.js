import { apiClient } from './client';

export const dueSystemApi = {
  list: (params) => apiClient.get('/due-system', { params }),
  get: (id) => apiClient.get(`/due-system/${id}`),
  collect: (id, payload) => apiClient.post(`/due-system/${id}/collect`, payload),
};
