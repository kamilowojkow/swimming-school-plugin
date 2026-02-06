import axios, { AxiosInstance, AxiosError } from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Adres WordPressa
const API_BASE_URL = 'https://woykow.pl/wp-json/ssm/v1';

// Klucze do przechowywania tokenów
const TOKEN_KEY = 'ssm_access_token';
const REFRESH_TOKEN_KEY = 'ssm_refresh_token';

class ApiClient {
  private client: AxiosInstance;
  private isRefreshing = false;
  private refreshSubscribers: ((token: string) => void)[] = [];

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Interceptor - dodaj token do każdego żądania
    this.client.interceptors.request.use(
      async (config) => {
        const token = await AsyncStorage.getItem(TOKEN_KEY);
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Interceptor - obsługa błędów i odświeżanie tokena
    this.client.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as any;

        if (error.response?.status === 401 && !originalRequest._retry) {
          if (this.isRefreshing) {
            return new Promise((resolve) => {
              this.refreshSubscribers.push((token: string) => {
                originalRequest.headers.Authorization = `Bearer ${token}`;
                resolve(this.client(originalRequest));
              });
            });
          }

          originalRequest._retry = true;
          this.isRefreshing = true;

          try {
            const newToken = await this.refreshToken();
            this.refreshSubscribers.forEach((callback) => callback(newToken));
            this.refreshSubscribers = [];
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            return this.client(originalRequest);
          } catch (refreshError) {
            // Token refresh failed - wyloguj użytkownika
            await this.clearTokens();
            throw refreshError;
          } finally {
            this.isRefreshing = false;
          }
        }

        return Promise.reject(error);
      }
    );
  }

  // ============================================
  // TOKEN MANAGEMENT
  // ============================================

  async saveTokens(accessToken: string, refreshToken: string) {
    await AsyncStorage.setItem(TOKEN_KEY, accessToken);
    await AsyncStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
  }

  async clearTokens() {
    await AsyncStorage.removeItem(TOKEN_KEY);
    await AsyncStorage.removeItem(REFRESH_TOKEN_KEY);
  }

  async getAccessToken() {
    return AsyncStorage.getItem(TOKEN_KEY);
  }

  async hasValidToken() {
    const token = await this.getAccessToken();
    return !!token;
  }

  private async refreshToken(): Promise<string> {
    const refreshToken = await AsyncStorage.getItem(REFRESH_TOKEN_KEY);
    if (!refreshToken) {
      throw new Error('No refresh token');
    }

    const response = await axios.post(`${API_BASE_URL}/auth/refresh`, {
      refresh_token: refreshToken,
    });

    const { access_token, refresh_token } = response.data.tokens;
    await this.saveTokens(access_token, refresh_token);
    return access_token;
  }

  // ============================================
  // AUTH ENDPOINTS
  // ============================================

  async login(email: string, password: string) {
    const response = await this.client.post('/auth/login', { username: email, password });
    const { token, user } = response.data;
    await this.saveTokens(token, token);
    return { user, token };
  }

  async logout() {
    try {
      await this.client.post('/auth/logout');
    } finally {
      await this.clearTokens();
    }
  }

  async requestPasswordReset(email: string) {
    const response = await this.client.post('/auth/password-reset', { email });
    return response.data;
  }

  // ============================================
  // USER ENDPOINTS
  // ============================================

  async getCurrentUser() {
    const response = await this.client.get('/user/me');
    return response.data;
  }

  async getProfile() {
    const response = await this.client.get('/user/profile');
    return response.data;
  }

  async updateProfile(data: Partial<{
    first_name: string;
    last_name: string;
    phone: string;
    address: string;
    bio: string;
  }>) {
    const response = await this.client.put('/user/profile', data);
    return response.data;
  }

  async changePassword(currentPassword: string, newPassword: string) {
    const response = await this.client.put('/user/password', {
      current_password: currentPassword,
      new_password: newPassword,
    });
    return response.data;
  }

  async registerPushToken(token: string, platform: 'ios' | 'android') {
    const response = await this.client.post('/user/push-token', { token, platform });
    return response.data;
  }

  // ============================================
  // PARENT ENDPOINTS
  // ============================================

  async getChildren() {
    const response = await this.client.get('/parent/children');
    return response.data;
  }

  async getChildDetails(childId: number) {
    const response = await this.client.get(`/parent/children/${childId}`);
    return response.data;
  }

  async getEnrollments() {
    const response = await this.client.get('/parent/enrollments');
    return response.data;
  }

  async getParentSchedule(dateFrom?: string, dateTo?: string) {
    const params: any = {};
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;
    const response = await this.client.get('/parent/schedule', { params });
    return response.data;
  }

  async getPayments(status?: 'all' | 'pending' | 'paid' | 'overdue') {
    const params = status ? { status } : {};
    const response = await this.client.get('/parent/payments', { params });
    return response.data;
  }

  async getPaymentDetails(paymentId: number) {
    const response = await this.client.get(`/parent/payments/${paymentId}`);
    return response.data;
  }

  async getPaymentHistory() {
    const response = await this.client.get('/parent/payment-history');
    return response.data;
  }

  async getAbsences() {
    const response = await this.client.get('/parent/absences');
    return response.data;
  }

  async getUpcomingSessions() {
    const response = await this.client.get('/parent/upcoming-sessions');
    return response.data;
  }

  async reportAbsence(sessionId: number, childId: number, reason?: string) {
    const response = await this.client.post('/parent/absences', {
      session_id: sessionId,
      child_id: childId,
      reason,
    });
    return response.data;
  }

  async getMakeupOptions() {
    const response = await this.client.get('/parent/makeups');
    return response.data;
  }

  async getMakeupSlots() {
    const response = await this.client.get('/parent/makeup-slots');
    return response.data;
  }

  async scheduleMakeup(absenceId: number, slotId: number) {
    const response = await this.client.post('/parent/makeups', {
      absence_id: absenceId,
      slot_id: slotId,
    });
    return response.data;
  }

  async bookMakeup(absenceId: number, sessionId: number) {
    const response = await this.client.post('/parent/makeups', {
      absence_id: absenceId,
      session_id: sessionId,
    });
    return response.data;
  }

  async getChildAchievements(childId: number) {
    const response = await this.client.get(`/parent/children/${childId}/achievements`);
    return response.data;
  }

  async getChildProgress(childId: number) {
    const response = await this.client.get(`/parent/children/${childId}/progress`);
    return response.data;
  }

  // ============================================
  // INSTRUCTOR ENDPOINTS
  // ============================================

  async getInstructorSchedule(dateFrom?: string, dateTo?: string) {
    const params: any = {};
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;
    const response = await this.client.get('/instructor/schedule', { params });
    return response.data;
  }

  async getSessionDetails(sessionId: number) {
    const response = await this.client.get(`/instructor/sessions/${sessionId}`);
    return response.data;
  }

  async getSessionAttendance(sessionId: number) {
    const response = await this.client.get(`/instructor/sessions/${sessionId}/attendance`);
    return response.data;
  }

  async saveAttendance(sessionId: number, attendance: Array<{
    child_id: number;
    enrollment_id?: number; // kept for backwards compatibility
    status: 'present' | 'absent' | 'late' | 'excused';
    notes?: string;
  }>) {
    const response = await this.client.post(`/instructor/sessions/${sessionId}/attendance`, {
      attendance,
    });
    return response.data;
  }

  async getSubstitutions(type?: 'available' | 'my_requests' | 'my_taken') {
    const params = type ? { type } : {};
    const response = await this.client.get('/instructor/substitutions', { params });
    return response.data;
  }

  async requestSubstitution(sessionId: number, reason?: string) {
    const response = await this.client.post('/instructor/substitutions', {
      session_id: sessionId,
      reason,
    });
    return response.data;
  }

  async takeSubstitution(substitutionId: number) {
    const response = await this.client.post(`/instructor/substitutions/${substitutionId}/take`);
    return response.data;
  }

  async getSalary(month?: number, year?: number) {
    const params: any = {};
    if (month) params.month = month;
    if (year) params.year = year;
    const response = await this.client.get('/instructor/salary', { params });
    return response.data;
  }

  // ============================================
  // NOTIFICATIONS ENDPOINTS
  // ============================================

  async getNotifications(limit = 20, offset = 0, unreadOnly = false) {
    const response = await this.client.get('/notifications', {
      params: { limit, offset, unread_only: unreadOnly },
    });
    return response.data;
  }

  async getUnreadCount() {
    const response = await this.client.get('/notifications/unread-count');
    return response.data;
  }

  async markNotificationRead(notificationId: number) {
    const response = await this.client.post(`/notifications/${notificationId}/read`);
    return response.data;
  }

  async markAllNotificationsRead() {
    const response = await this.client.post('/notifications/read-all');
    return response.data;
  }

  // ============================================
  // PUBLIC ENDPOINTS
  // ============================================

  async getFacilities() {
    const response = await this.client.get('/facilities');
    return response.data;
  }

  async getClasses() {
    const response = await this.client.get('/classes');
    return response.data;
  }
}

export const api = new ApiClient();
export default api;
