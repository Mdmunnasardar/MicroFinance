import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/MicroFinance/backend/public/api';

export const apiClient = axios.create({
  baseURL,
  withCredentials: true,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

let unauthorizedHandler = null;

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler;
}

apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    const status = error.response?.status;
    const payload = error.response?.data;

    if (status === 401 && typeof unauthorizedHandler === 'function') {
      unauthorizedHandler();
    }

    const message = payload?.error?.message || error.message || 'Request failed';
    const wrapped = new Error(message);
    wrapped.status = status;
    wrapped.code = payload?.error?.code;
    return Promise.reject(wrapped);
  },
);
