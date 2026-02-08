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
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { useNotificationStore } from '../../store/notificationStore';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

// Safe date formatting helper
const safeFormatDate = (dateStr: string | undefined, formatStr: string, locale: any): string => {
  if (!dateStr) return '-';
  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    return format(date, formatStr, { locale });
  } catch {
    return '-';
  }
};

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
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');

  const [refreshing, setRefreshing] = useState(false);
  const [todaySessions, setTodaySessions] = useState<Session[]>([]);
  const [availableSubstitutions, setAvailableSubstitutions] = useState<Substitution[]>([]);
  const [monthStats, setMonthStats] = useState({ sessions: 0, hours: 0, salary: 0 });
  const [isLoading, setIsLoading] = useState(true);

  const dateLocale = language === 'pl' ? pl : enUS;
  const styles = createStyles(colors);

  const fetchData = async () => {
    try {
      const today = format(new Date(), 'yyyy-MM-dd');
      console.log('=== INSTRUCTOR DASHBOARD DEBUG ===');
      console.log('Fetching data for date:', today);

      const [scheduleData, substitutionsData, salaryData] = await Promise.all([
        api.getInstructorSchedule(today, today),
        api.getSubstitutions('available'),
        api.getSalary(),
      ]);

      console.log('Schedule API response:', JSON.stringify(scheduleData, null, 2));
      console.log('Substitutions API response:', JSON.stringify(substitutionsData, null, 2));
      console.log('Salary API response:', JSON.stringify(salaryData, null, 2));

      // Handle both array and object API responses
      const sessionsArray = Array.isArray(scheduleData) ? scheduleData : (scheduleData?.sessions || []);
      const substitutionsArray = Array.isArray(substitutionsData) ? substitutionsData : (substitutionsData?.substitutions || []);

      // Log debug info if present
      if (scheduleData?._debug) {
        console.log('Schedule debug:', scheduleData._debug);
      }
      if (salaryData?._debug) {
        console.log('Salary debug:', salaryData._debug);
      }

      console.log('Parsed sessions:', sessionsArray.length, 'items');
      console.log('Parsed substitutions:', substitutionsArray.length, 'items');
      console.log('Month stats from salary:', {
        sessions_count: salaryData?.sessions_count,
        total_hours: salaryData?.total_hours,
        total_salary: salaryData?.total_salary,
      });
      console.log('=== END INSTRUCTOR DASHBOARD DEBUG ===');

      setTodaySessions(sessionsArray);
      setAvailableSubstitutions(substitutionsArray.slice(0, 3));
      setMonthStats({
        sessions: salaryData?.sessions_count || 0,
        hours: salaryData?.total_hours || 0,
        salary: salaryData?.total_salary || 0,
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
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.secondary} />}
    >
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>{t.parentDashboard.greeting}, {user?.first_name}!</Text>
          <Text style={styles.date}>{format(new Date(), 'EEEE, d MMMM yyyy', { locale: dateLocale })}</Text>
        </View>
        <TouchableOpacity
          style={styles.notificationButton}
          onPress={() => navigation.navigate('Notifications')}
        >
          <Ionicons name="notifications-outline" size={24} color={colors.text} />
          {unreadCount > 0 && (
            <View style={styles.notificationBadge}>
              <Text style={styles.notificationBadgeText}>{unreadCount}</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      {/* Month Stats */}
      <View style={styles.statsContainer}>
        <View style={[styles.statCard, { backgroundColor: colors.secondaryLight }]}>
          <Ionicons name="calendar" size={28} color={colors.secondary} />
          <Text style={styles.statNumber}>{monthStats.sessions}</Text>
          <Text style={styles.statLabel}>{t.instructorDashboard.lessons}</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: colors.warningLight }]}>
          <Ionicons name="time" size={28} color={colors.warning} />
          <Text style={styles.statNumber}>{monthStats.hours.toFixed(1)}</Text>
          <Text style={styles.statLabel}>{t.instructorDashboard.hours}</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: colors.primaryLight }]}>
          <Ionicons name="wallet" size={28} color={colors.primary} />
          <Text style={styles.statNumber}>{monthStats.salary.toFixed(0)}</Text>
          <Text style={styles.statLabel}>PLN</Text>
        </View>
      </View>

      {/* Today's Sessions */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>{t.instructorDashboard.todaySessions}</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Schedule')}>
            <Text style={styles.sectionLink}>{t.nav.schedule}</Text>
          </TouchableOpacity>
        </View>
        {todaySessions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="sunny-outline" size={40} color={colors.secondary} />
            <Text style={styles.emptyStateText}>{t.instructorDashboard.noSessionsToday}</Text>
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
                  <Ionicons name="people-outline" size={14} color={colors.textSecondary} />
                  <Text style={styles.sessionStatsText}>{session.enrolled_count} {t.schedule.participants}</Text>
                  {session.attendance_marked > 0 && (
                    <View style={styles.attendanceMarked}>
                      <Ionicons name="checkmark-circle" size={14} color={colors.secondary} />
                      <Text style={styles.attendanceMarkedText}>{t.schedule.attendanceChecked}</Text>
                    </View>
                  )}
                </View>
              </View>
              <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
            </TouchableOpacity>
          ))
        )}
      </View>

      {/* Available Substitutions */}
      {availableSubstitutions.length > 0 && (
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>{t.instructorDashboard.availableSubstitutions}</Text>
            <TouchableOpacity onPress={() => navigation.navigate('Substitutions')}>
              <Text style={styles.sectionLink}>{t.instructorDashboard.viewAll}</Text>
            </TouchableOpacity>
          </View>
          {availableSubstitutions.map((sub) => (
            <View key={sub.id} style={styles.substitutionCard}>
              <View style={styles.substitutionIcon}>
                <Ionicons name="hand-left-outline" size={24} color={colors.warning} />
              </View>
              <View style={styles.substitutionInfo}>
                <Text style={styles.substitutionTitle}>{sub.class_name}</Text>
                <Text style={styles.substitutionMeta}>
                  {safeFormatDate(sub.session_date, 'd MMM', dateLocale)} o {sub.time_start?.substring(0, 5) || '-'}
                </Text>
                <Text style={styles.substitutionInstructor}>{t.instructorDashboard.forInstructor}: {sub.instructor_name}</Text>
              </View>
              <TouchableOpacity
                style={styles.takeButton}
                onPress={() => {
                  // Handle take substitution
                }}
              >
                <Text style={styles.takeButtonText}>{t.instructorDashboard.take}</Text>
              </TouchableOpacity>
            </View>
          ))}
        </View>
      )}

      <View style={{ height: 24 }} />
    </ScrollView>
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
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
      color: colors.text,
    },
    date: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    notificationButton: {
      width: 44,
      height: 44,
      borderRadius: 22,
      backgroundColor: colors.surface,
      justifyContent: 'center',
      alignItems: 'center',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.1,
      shadowRadius: 2,
      elevation: 2,
    },
    notificationBadge: {
      position: 'absolute',
      top: 8,
      right: 8,
      backgroundColor: colors.error,
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
      color: colors.text,
      marginTop: 8,
    },
    statLabel: {
      fontSize: 12,
      color: colors.textSecondary,
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
      color: colors.text,
    },
    sectionLink: {
      fontSize: 14,
      color: colors.secondary,
    },
    emptyState: {
      alignItems: 'center',
      padding: 32,
      backgroundColor: colors.surface,
      borderRadius: 12,
    },
    emptyStateText: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 8,
    },
    sessionCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 16,
      marginBottom: 8,
      shadowColor: colors.shadow,
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
      color: colors.secondary,
    },
    sessionTimeEnd: {
      fontSize: 12,
      color: colors.textTertiary,
    },
    sessionInfo: {
      flex: 1,
      marginLeft: 12,
    },
    sessionTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    sessionMeta: {
      fontSize: 14,
      color: colors.textSecondary,
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
      color: colors.textSecondary,
    },
    attendanceMarked: {
      flexDirection: 'row',
      alignItems: 'center',
      marginLeft: 8,
      gap: 2,
    },
    attendanceMarkedText: {
      fontSize: 12,
      color: colors.secondary,
    },
    substitutionCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 16,
      marginBottom: 8,
      borderLeftWidth: 4,
      borderLeftColor: colors.warning,
    },
    substitutionIcon: {
      width: 48,
      height: 48,
      borderRadius: 24,
      backgroundColor: colors.warningLight,
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
      color: colors.text,
    },
    substitutionMeta: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 2,
    },
    substitutionInstructor: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
    },
    takeButton: {
      backgroundColor: colors.secondary,
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
