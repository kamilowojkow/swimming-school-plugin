import { create } from 'zustand';
import api from '../api/client';
import { initializePushNotifications } from '../services/pushNotifications';

export type UserType = 'parent' | 'instructor';

export interface User {
  id: number;
  type: UserType; // Primary role from server
  roles: UserType[]; // All available roles for this user
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
  activeRole: UserType | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;

  // Actions
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  updateUser: (data: Partial<User>) => void;
  switchRole: (role: UserType) => void;
  clearError: () => void;
}

// Helper to normalize user roles
const normalizeUserRoles = (user: any): User => {
  // Ensure roles array exists
  let roles: UserType[] = [];
  if (Array.isArray(user.roles) && user.roles.length > 0) {
    roles = user.roles;
  } else if (user.type) {
    roles = [user.type];
  }
  return {
    ...user,
    roles,
  };
};

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  activeRole: null,
  isLoading: true,
  isAuthenticated: false,
  error: null,

  login: async (email: string, password: string) => {
    set({ isLoading: true, error: null });
    try {
      const { user: rawUser } = await api.login(email, password);
      const user = normalizeUserRoles(rawUser);
      // Set activeRole to user's primary type or first available role
      const activeRole = user.type || user.roles[0] || 'parent';
      set({
        user,
        activeRole,
        isAuthenticated: true,
        isLoading: false,
      });
      // Register for push notifications after successful login
      initializePushNotifications().catch(console.error);
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
        activeRole: null,
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
        set({ isLoading: false, isAuthenticated: false, activeRole: null });
        return;
      }

      const rawUser = await api.getCurrentUser();
      const user = normalizeUserRoles(rawUser);
      const activeRole = user.type || user.roles[0] || 'parent';
      set({
        user,
        activeRole,
        isAuthenticated: true,
        isLoading: false,
      });
      // Register for push notifications
      initializePushNotifications().catch(console.error);
    } catch (error) {
      await api.clearTokens();
      set({
        user: null,
        activeRole: null,
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

  switchRole: (role: UserType) => {
    const currentUser = get().user;
    if (currentUser && currentUser.roles.includes(role)) {
      set({ activeRole: role });
    }
  },

  clearError: () => set({ error: null }),
}));
