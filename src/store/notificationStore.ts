import { create } from 'zustand';
import api from '../api/client';

export interface Notification {
  id: number;
  type: string;
  title: string;
  message: string;
  icon: string;
  color: string;
  is_read: boolean;
  action_url?: string;
  created_at: string;
  time_ago: string;
  data?: Record<string, any>;
}

interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  isLoading: boolean;

  // Actions
  fetchNotifications: (limit?: number, offset?: number) => Promise<void>;
  fetchUnreadCount: () => Promise<void>;
  markAsRead: (id: number) => Promise<void>;
  markAllAsRead: () => Promise<void>;
}

export const useNotificationStore = create<NotificationState>((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,

  fetchNotifications: async (limit = 20, offset = 0) => {
    set({ isLoading: true });
    try {
      const data = await api.getNotifications(limit, offset);
      // Handle both array and object with notifications key
      const notificationsArray = Array.isArray(data) ? data : (data?.notifications || []);
      if (offset === 0) {
        set({ notifications: notificationsArray });
      } else {
        set({ notifications: [...get().notifications, ...notificationsArray] });
      }
    } catch (error) {
      console.error('Error fetching notifications:', error);
      set({ notifications: [] });
    } finally {
      set({ isLoading: false });
    }
  },

  fetchUnreadCount: async () => {
    try {
      const { unread_count } = await api.getUnreadCount();
      set({ unreadCount: unread_count });
    } catch (error) {
      console.error('Error fetching unread count:', error);
    }
  },

  markAsRead: async (id: number) => {
    try {
      await api.markNotificationRead(id);
      set({
        notifications: get().notifications.map((n) =>
          n.id === id ? { ...n, is_read: true } : n
        ),
        unreadCount: Math.max(0, get().unreadCount - 1),
      });
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  },

  markAllAsRead: async () => {
    try {
      await api.markAllNotificationsRead();
      set({
        notifications: get().notifications.map((n) => ({ ...n, is_read: true })),
        unreadCount: 0,
      });
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    }
  },
}));
