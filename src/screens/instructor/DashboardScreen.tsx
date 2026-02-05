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

interface Session {
  id: number;
  session_date: string;
  time_start: string;
  time_end: string;
  class_name: string;
  facility_name: string;
  enrolled_count: number;
  attendance_marked: number;
}

interface Substitution {
  id: number;
  session_date: string;
  time_start: string;
  class_name: string;
  instructor_name: string;
}

export default function InstructorDashboard() {
  const navigation = useNavigation<any>();
  const user = useAuthStore((state) => state.user);
  const { unreadCount, fetchUnreadCount } = useNotificationStore();

  const [refreshing, setRefreshing] = useState(false);
  const [todaySessions, setTodaySessions] = useState<Session[]>([]);
  const [availableSubstitutions, setAvailableSubstitutions] = useState<Substitution[]>([]);
  const [monthStats, setMonthStats] = useState({ sessions: 0, hours: 0, salary: 0 });
  const [isLoading, setIsLoading] = useState(true);

  const fetchData = async () => {
    try {
      const today = format(new Date(), 'yyyy-MM-dd');

      const [scheduleData, substitutionsData, salaryData] = await Promise.all([
        api.getInstructorSchedule(today, today),
        api.getSubstitutions('available'),
        api.getSalary(),
      ]);

      setTodaySessions(scheduleData);
      setAvailableSubstitutions(substitutionsData.slice(0, 3));
      setMonthStats({
        sessions: salaryData.sessions_count,
        hours: salaryData.total_hours,
        salary: salaryData.total_salary,
      });
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

  const formatTime = (timeStr: string) => timeStr.substring(0, 5);

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

      {/* Month Stats */}
      <View style={styles.statsContainer}>
        <View style={[styles.statCard, { backgroundColor: '#ecfdf5' }]}>
          <Ionicons name="calendar" size={28} color="#10b981" />
          <Text style={styles.statNumber}>{monthStats.sessions}</Text>
          <Text style={styles.statLabel}>Zajęcia</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#fef3c7' }]}>
          <Ionicons name="time" size={28} color="#f59e0b" />
          <Text style={styles.statNumber}>{monthStats.hours.toFixed(1)}</Text>
          <Text style={styles.statLabel}>Godziny</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#eff6ff' }]}>
          <Ionicons name="wallet" size={28} color="#3b82f6" />
          <Text style={styles.statNumber}>{monthStats.salary.toFixed(0)}</Text>
          <Text style={styles.statLabel}>PLN</Text>
        </View>
      </View>

      {/* Today's Sessions */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Dzisiejsze zajęcia</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Schedule')}>
            <Text style={styles.sectionLink}>Harmonogram</Text>
          </TouchableOpacity>
        </View>
        {todaySessions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="sunny-outline" size={40} color="#10b981" />
            <Text style={styles.emptyStateText}>Brak zajęć na dziś</Text>
          </View>
        ) : (
          todaySessions.map((session) => (
            <TouchableOpacity
              key={session.id}
              style={styles.sessionCard}
              onPress={() => navigation.navigate('Attendance', { sessionId: session.id })}
            >
              <View style={styles.sessionTime}>
                <Text style={styles.sessionTimeText}>{formatTime(session.time_start)}</Text>
                <Text style={styles.sessionTimeEnd}>{formatTime(session.time_end)}</Text>
              </View>
              <View style={styles.sessionInfo}>
                <Text style={styles.sessionTitle}>{session.class_name}</Text>
                <Text style={styles.sessionMeta}>{session.facility_name}</Text>
                <View style={styles.sessionStats}>
                  <Ionicons name="people-outline" size={14} color="#6b7280" />
                  <Text style={styles.sessionStatsText}>{session.enrolled_count} uczestników</Text>
                  {session.attendance_marked > 0 && (
                    <View style={styles.attendanceMarked}>
                      <Ionicons name="checkmark-circle" size={14} color="#10b981" />
                      <Text style={styles.attendanceMarkedText}>Obecność sprawdzona</Text>
                    </View>
                  )}
                </View>
              </View>
              <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
            </TouchableOpacity>
          ))
        )}
      </View>

      {/* Available Substitutions */}
      {availableSubstitutions.length > 0 && (
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Dostępne zastępstwa</Text>
            <TouchableOpacity onPress={() => navigation.navigate('Substitutions')}>
              <Text style={styles.sectionLink}>Zobacz wszystkie</Text>
            </TouchableOpacity>
          </View>
          {availableSubstitutions.map((sub) => (
            <View key={sub.id} style={styles.substitutionCard}>
              <View style={styles.substitutionIcon}>
                <Ionicons name="hand-left-outline" size={24} color="#f59e0b" />
              </View>
              <View style={styles.substitutionInfo}>
                <Text style={styles.substitutionTitle}>{sub.class_name}</Text>
                <Text style={styles.substitutionMeta}>
                  {format(new Date(sub.session_date), 'd MMM', { locale: pl })} o {formatTime(sub.time_start)}
                </Text>
                <Text style={styles.substitutionInstructor}>Za: {sub.instructor_name}</Text>
              </View>
              <TouchableOpacity
                style={styles.takeButton}
                onPress={() => {
                  // Handle take substitution
                }}
              >
                <Text style={styles.takeButtonText}>Weź</Text>
              </TouchableOpacity>
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
    color: '#10b981',
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
  sessionTime: {
    width: 56,
    alignItems: 'center',
  },
  sessionTimeText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#10b981',
  },
  sessionTimeEnd: {
    fontSize: 12,
    color: '#9ca3af',
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
  sessionStats: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
    gap: 4,
  },
  sessionStatsText: {
    fontSize: 12,
    color: '#6b7280',
  },
  attendanceMarked: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: 8,
    gap: 2,
  },
  attendanceMarkedText: {
    fontSize: 12,
    color: '#10b981',
  },
  substitutionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 8,
    borderLeftWidth: 4,
    borderLeftColor: '#f59e0b',
  },
  substitutionIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#fef3c7',
    justifyContent: 'center',
    alignItems: 'center',
  },
  substitutionInfo: {
    flex: 1,
    marginLeft: 12,
  },
  substitutionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
  },
  substitutionMeta: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 2,
  },
  substitutionInstructor: {
    fontSize: 12,
    color: '#9ca3af',
    marginTop: 2,
  },
  takeButton: {
    backgroundColor: '#10b981',
    borderRadius: 8,
    paddingVertical: 8,
    paddingHorizontal: 16,
  },
  takeButtonText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
});
