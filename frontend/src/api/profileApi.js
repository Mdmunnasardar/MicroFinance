import { apiClient } from './client';

export const profileApi = {
  show() {
    return apiClient.get('/profile');
  },
  update(payload) {
    return apiClient.put('/profile', payload);
  },
  changePassword(payload) {
    return apiClient.put('/profile/password', payload);
  },
  uploadAvatar(file) {
    const fd = new FormData();
    fd.append('avatar', file);
    return apiClient.post('/profile/avatar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};