import { apiClient } from './client';

export const branchesApi = {
  list() {
    return apiClient.get('/branches');
  },
};
