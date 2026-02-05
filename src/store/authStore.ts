import { create } from 'zustand';
import api from '../api/client';

export type UserType = 'parent' | 'instructor';

export interface User {
  id: number;
  type: UserType;
  email: string;
  first_name: string;
  last_name: string;
  phone?: string;
  photo?: string;
  // Parent specific
  children_count?: number;
  address?: string;
  // Instructor specific
  specialization?: string;
  bio?: string;
  hourly_rate?: number;
}

interface AuthState {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;

  // Actions
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  updateUser: (data: Partial<User>) => void;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  isLoading: true,
  isAuthenticated: false,
  error: null,

  login: async (email: string, password: string) => {
    set({ isLoading: true, error: null });
    try {
      const { user } = await api.login(email, password);
      set({
        user,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch (error: any) {
      const message = error.response?.data?.message || 'Błąd logowania';
      set({ error: message, isLoading: false });
      throw error;
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await api.logout();
    } finally {
      set({
        user: null,
        isAuthenticated: false,
        isLoading: false,
      });
    }
  },

  checkAuth: async () => {
    set({ isLoading: true });
    try {
      const hasToken = await api.hasValidToken();
      if (!hasToken) {
        set({ isLoading: false, isAuthenticated: false });
        return;
      }

      const user = await api.getCurrentUser();
      set({
        user,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch (error) {
      await api.clearTokens();
      set({
        user: null,
        isAuthenticated: false,
        isLoading: false,
      });
    }
  },

  updateUser: (data) => {
    const currentUser = get().user;
    if (currentUser) {
      set({ user: { ...currentUser, ...data } });
    }
  },

  clearError: () => set({ error: null }),
}));
