import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { authApi } from '../api/authApi';
import { setUnauthorizedHandler } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [status, setStatus] = useState('loading'); // loading | unauthenticated | authenticated

  const refresh = useCallback(async () => {
    setStatus('loading');
    try {
      const response = await authApi.session();
      const payload = response.data ?? response;
      if (payload?.authenticated) {
        setUser(payload.user);
        setStatus('authenticated');
        return payload.user;
      }
      setUser(null);
      setStatus('unauthenticated');
      return null;
    } catch (error) {
      setUser(null);
      setStatus('unauthenticated');
      return null;
    }
  }, []);

  const login = useCallback(async (username, password) => {
    const response = await authApi.login({ username, password });
    const payload = response.data ?? response;
    setUser(payload.user);
    setStatus('authenticated');
    return payload.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch (error) {
      // swallow logout errors; we still drop local state
    }
    setUser(null);
    setStatus('unauthenticated');
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setUser(null);
      setStatus('unauthenticated');
    });
    refresh();
  }, [refresh]);

  const value = useMemo(() => ({ user, status, login, logout, refresh, setUser }), [user, status, login, logout, refresh]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}
