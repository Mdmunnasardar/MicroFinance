import { apiClient } from './client';

export const fieldOfficersApi = {
  list() {
    return apiClient.get('/field-officers');
  },
};