import { createContext, useContext, useState, useCallback } from 'react';
import client from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('tt_user');
    return stored ? JSON.parse(stored) : null;
  });

  const persist = useCallback((userData, token) => {
    localStorage.setItem('tt_token', token);
    localStorage.setItem('tt_user', JSON.stringify(userData));
    setUser(userData);
  }, []);

  const login = useCallback(async (email, password) => {
    const { data } = await client.post('/auth/login', { email, password });
    persist(data.data.user, data.data.token);
    return data.data.user;
  }, [persist]);

  const register = useCallback(async (payload) => {
    const { data } = await client.post('/auth/register', payload);
    persist(data.data.user, data.data.token);
    return data.data.user;
  }, [persist]);

  const logout = useCallback(() => {
    localStorage.removeItem('tt_token');
    localStorage.removeItem('tt_user');
    setUser(null);
  }, []);

  const value = { user, login, register, logout, isAuthenticated: !!user };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth doit être utilisé dans un AuthProvider');
  return ctx;
}
