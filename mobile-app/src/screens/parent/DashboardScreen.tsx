import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { format } from 'date-fns';
import { pl } from 'date-fns/locale';
import api from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { useNotificationStore } from '../../store/notificationStore';

interface Child {
  id: number;
  first_name: string;
  last_name: string;
  active_courses: number;
  total_points: number;
}

interface UpcomingSession {
  id: number;
  session_date: string;
  time_start: string;
  class_name: string;
  facility_name: string;
  child_first_name: string;
}

interface Payment {
  id: number;
  title: string;
  remaining_amount: number;
  due_date: string;
  status: string;
}

export default function ParentDashboard() {
  const navigation = useNavigation<any>();
  const user = useAuthStore((state) => state.user);
  const { unreadCount, fetchUnreadCount } = useNotificationStore();

  const [refreshing, setRefreshing] = useState(false);
  const [children, setChildren] = useState<Child[]>([]);
  const [upcomingSessions, setUpcomingSessions] = useState<UpcomingSession[]>([]);
  const [pendingPayments, setPendingPayments] = useState<Payment[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchData = async () => {
    try {
      const [childrenData, scheduleData, paymentsData] = await Promise.all([
        api.getChildren(),
        api.getParentSchedule(),
        api.getPayments('pending'),
      ]);

      setChildren(childrenData);
      setUpcomingSessions(scheduleData.slice(0, 3));
      setPendingPayments(paymentsData.filter((p: Payment) => p.status !== 'paid').slice(0, 3));
      await fetchUnreadCount();
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return format(date, 'EEEE, d MMMM', { locale: pl });
  };

  const formatTime = (timeStr: string) => {
    return timeStr.substring(0, 5);
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Cześć, {user?.first_name}!</Text>
          <Text style={styles.date}>{format(new Date(), 'EEEE, d MMMM yyyy', { locale: pl })}</Text>
        </View>
        <TouchableOpacity
          style={styles.notificationButton}
          onPress={() => navigation.navigate('Notifications')}
        >
          <Ionicons name="notifications-outline" size={24} color="#374151" />
          {unreadCount > 0 && (
            <View style={styles.notificationBadge}>
              <Text style={styles.notificationBadgeText}>{unreadCount}</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      {/* Quick Stats */}
      <View style={styles.statsContainer}>
        <View style={[styles.statCard, { backgroundColor: '#eff6ff' }]}>
          <Ionicons name="people" size={28} color="#3b82f6" />
          <Text style={styles.statNumber}>{children.length}</Text>
          <Text style={styles.statLabel}>Dzieci</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#f0fdf4' }]}>
          <Ionicons name="calendar" size={28} color="#10b981" />
          <Text style={styles.statNumber}>{upcomingSessions.length}</Text>
          <Text style={styles.statLabel}>Zajęcia</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#fef3c7' }]}>
          <Ionicons name="card" size={28} color="#f59e0b" />
          <Text style={styles.statNumber}>{pendingPayments.length}</Text>
          <Text style={styles.statLabel}>Płatności</Text>
        </View>
      </View>

      {/* Children Section */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Twoje dzieci</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Children')}>
            <Text style={styles.sectionLink}>Zobacz wszystkie</Text>
          </TouchableOpacity>
        </View>
        {children.map((child) => (
          <TouchableOpacity
            key={child.id}
            style={styles.childCard}
            onPress={() => navigation.navigate('ChildDetails', { childId: child.id })}
          >
            <View style={styles.childAvatar}>
              <Text style={styles.childAvatarText}>
                {child.first_name[0]}{child.last_name[0]}
              </Text>
            </View>
            <View style={styles.childInfo}>
              <Text style={styles.childName}>{child.first_name} {child.last_name}</Text>
              <Text style={styles.childMeta}>
                {child.active_courses} {child.active_courses === 1 ? 'kurs' : 'kursy'} • {child.total_points} pkt
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>
        ))}
      </View>

      {/* Upcoming Sessions */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Najbliższe zajęcia</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Schedule')}>
            <Text style={styles.sectionLink}>Zobacz harmonogram</Text>
          </TouchableOpacity>
        </View>
        {upcomingSessions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="calendar-outline" size={40} color="#9ca3af" />
            <Text style={styles.emptyStateText}>Brak nadchodzących zajęć</Text>
          </View>
        ) : (
          upcomingSessions.map((session) => (
            <View key={session.id} style={styles.sessionCard}>
              <View style={styles.sessionTime}>
                <Text style={styles.sessionTimeText}>{formatTime(session.time_start)}</Text>
              </View>
              <View style={styles.sessionInfo}>
                <Text style={styles.sessionTitle}>{session.class_name}</Text>
                <Text style={styles.sessionMeta}>
                  {session.child_first_name} • {session.facility_name}
                </Text>
                <Text style={styles.sessionDate}>{formatDate(session.session_date)}</Text>
              </View>
            </View>
          ))
        )}
      </View>

      {/* Pending Payments */}
      {pendingPayments.length > 0 && (
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Oczekujące płatności</Text>
            <TouchableOpacity onPress={() => navigation.navigate('Payments')}>
              <Text style={styles.sectionLink}>Zobacz wszystkie</Text>
            </TouchableOpacity>
          </View>
          {pendingPayments.map((payment) => (
            <View key={payment.id} style={styles.paymentCard}>
              <View style={styles.paymentInfo}>
                <Text style={styles.paymentTitle}>{payment.title}</Text>
                <Text style={styles.paymentDue}>
                  Termin: {format(new Date(payment.due_date), 'd MMM yyyy', { locale: pl })}
                </Text>
              </View>
              <Text style={styles.paymentAmount}>
                {payment.remaining_amount.toFixed(2)} zł
              </Text>
            </View>
          ))}
        </View>
      )}

      <View style={{ height: 24 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 8,
  },
  greeting: {
    fontSize: 24,
    fontWeight: '700',
    color: '#111827',
  },
  date: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 4,
  },
  notificationButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  notificationBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    backgroundColor: '#ef4444',
    borderRadius: 8,
    minWidth: 16,
    height: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  notificationBadgeText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: '600',
  },
  statsContainer: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    paddingVertical: 16,
    gap: 12,
  },
  statCard: {
    flex: 1,
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
  },
  statNumber: {
    fontSize: 24,
    fontWeight: '700',
    color: '#111827',
    marginTop: 8,
  },
  statLabel: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 2,
  },
  section: {
    paddingHorizontal: 20,
    paddingVertical: 8,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#111827',
  },
  sectionLink: {
    fontSize: 14,
    color: '#3b82f6',
  },
  childCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  childAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#3b82f6',
    justifyContent: 'center',
    alignItems: 'center',
  },
  childAvatarText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  childInfo: {
    flex: 1,
    marginLeft: 12,
  },
  childName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
  },
  childMeta: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 2,
  },
  emptyState: {
    alignItems: 'center',
    padding: 32,
    backgroundColor: '#fff',
    borderRadius: 12,
  },
  emptyStateText: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 8,
  },
  sessionCard: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  sessionTime: {
    width: 56,
    height: 56,
    borderRadius: 12,
    backgroundColor: '#eff6ff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  sessionTimeText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#3b82f6',
  },
  sessionInfo: {
    flex: 1,
    marginLeft: 12,
  },
  sessionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
  },
  sessionMeta: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 2,
  },
  sessionDate: {
    fontSize: 12,
    color: '#9ca3af',
    marginTop: 4,
  },
  paymentCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 8,
    borderLeftWidth: 4,
    borderLeftColor: '#f59e0b',
  },
  paymentInfo: {
    flex: 1,
  },
  paymentTitle: {
    fontSize: 16,
    fontWeight: '500',
    color: '#111827',
  },
  paymentDue: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 2,
  },
  paymentAmount: {
    fontSize: 18,
    fontWeight: '700',
    color: '#f59e0b',
  },
});
