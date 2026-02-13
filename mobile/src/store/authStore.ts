import { create } from 'zustand';
import api from '../api/client';
// TODO: Re-enable push notifications when SDK compatibility is resolved
// import { initializePushNotifications } from '../services/pushNotifications';

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

// Helper to get all user roles from various API formats
const getUserRoles = (user: any): UserType[] => {
  const roles: UserType[] = [];

  // Check roles array first (preferred source)
  if (Array.isArray(user.roles) && user.roles.length > 0) {
    for (const r of user.roles) {
      const roleStr = String(r).toLowerCase();
      if (roleStr === 'instructor' || roleStr === 'teacher' || roleStr === 'instruktor') {
        if (!roles.includes('instructor')) roles.push('instructor');
      } else if (roleStr === 'parent' || roleStr === 'client' || roleStr === 'rodzic') {
        if (!roles.includes('parent')) roles.push('parent');
      }
    }
  }

  // Check common field names for type/role as fallback
  if (roles.length === 0) {
    const typeValue = user.type || user.role || user.user_type || user.userType || user.account_type || '';
    const typeStr = String(typeValue).toLowerCase();
    if (typeStr === 'instructor' || typeStr === 'teacher' || typeStr === 'coach' || typeStr === 'instruktor') {
      roles.push('instructor');
    } else {
      roles.push('parent');
    }
  }

  // Check additional flags for multi-role users
  if (user.is_instructor || user.isInstructor || user.can_instruct) {
    if (!roles.includes('instructor')) roles.push('instructor');
  }
  if (user.is_parent || user.isParent || user.has_children || user.children_count > 0) {
    if (!roles.includes('parent')) roles.push('parent');
  }

  return roles.length > 0 ? roles : ['parent'];
};

// Helper to normalize user roles
const normalizeUserRoles = (user: any): User => {
  const roles = getUserRoles(user);

  // For multi-role users, default to 'parent' view
  // For single-role users, use their only role
  const defaultType: UserType = roles.length > 1 ? 'parent' : roles[0];

  const normalized: User = {
    id: user.id || user.ID || 0,
    type: defaultType,
    roles,
    email: user.email || user.user_email || '',
    first_name: user.first_name || user.firstName || user.display_name?.split(' ')[0] || '',
    last_name: user.last_name || user.lastName || user.display_name?.split(' ')[1] || '',
    phone: user.phone || user.phone_number || user.billing_phone || undefined,
    photo: user.photo || user.avatar || user.avatar_url || user.profile_image || undefined,
    children_count: user.children_count ?? user.childrenCount ?? undefined,
    address: user.address || user.billing_address || undefined,
    specialization: user.specialization || undefined,
    bio: user.bio || user.description || undefined,
    hourly_rate: user.hourly_rate ?? user.hourlyRate ?? undefined,
  };

  console.log('User data from API:', user);
  console.log('Normalized user:', normalized);
  console.log('User roles:', roles, 'Default type:', defaultType);

  return normalized;
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
      const response = await api.login(email, password);
      console.log('=== LOGIN DEBUG ===');
      console.log('Full API response:', JSON.stringify(response, null, 2));

      const { user: rawUser, debug } = response as any;
      console.log('Raw user from API:', JSON.stringify(rawUser, null, 2));
      console.log('Debug info from API:', JSON.stringify(debug, null, 2));

      const user = normalizeUserRoles(rawUser);
      console.log('Normalized user:', JSON.stringify(user, null, 2));

      // Set activeRole to user's primary type or first available role
      const activeRole = user.type || user.roles[0] || 'parent';
      console.log('Active role set to:', activeRole);
      console.log('=== END LOGIN DEBUG ===');

      set({
        user,
        activeRole,
        isAuthenticated: true,
        isLoading: false,
      });
      // TODO: Re-enable push notifications when SDK compatibility is resolved
      // initializePushNotifications().catch(console.error);
    } catch (error: any) {
      console.log('Login error:', error);
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
      // TODO: Re-enable push notifications when SDK compatibility is resolved
      // initializePushNotifications().catch(console.error);
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
      console.log('Switching role from', get().activeRole, 'to', role);
      set({ activeRole: role });
    }
  },

  clearError: () => set({ error: null }),
}));
