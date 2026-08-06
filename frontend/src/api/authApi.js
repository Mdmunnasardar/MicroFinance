import { apiClient } from './client';

export const authApi = {
  login(credentials) {
    return apiClient.post('/auth/login', credentials);
  },
  logout() {
    return apiClient.post('/auth/logout');
  },
  session() {
    return apiClient.get('/auth/session');
  },
};
