import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { format, addDays, startOfWeek, isSameDay, parseISO } from 'date-fns';
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

// Safe date parsing helper
const safeParseDateISO = (dateStr: string | undefined): Date | null => {
  if (!dateStr) return null;
  try {
    const date = parseISO(dateStr);
    if (isNaN(date.getTime())) return null;
    return date;
  } catch {
    return null;
  }
};

interface Session {
  id: number;
  session_date: string;
  time_start: string;
  time_end: string;
  class_name: string;
  level: string;
  facility_name: string;
  facility_address: string;
  enrolled_count: number;
  max_participants: number;
  attendance_marked: number;
  status: string;
}

export default function InstructorScheduleScreen() {
  const navigation = useNavigation<any>();
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');

  const [selectedDate, setSelectedDate] = useState(new Date());
  const [weekStart, setWeekStart] = useState(startOfWeek(new Date(), { weekStartsOn: 1 }));
  const [sessions, setSessions] = useState<Session[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const dateLocale = language === 'pl' ? pl : enUS;
  const styles = createStyles(colors);

  const fetchSessions = useCallback(async () => {
    try {
      const dateFrom = format(weekStart, 'yyyy-MM-dd');
      const dateTo = format(addDays(weekStart, 13), 'yyyy-MM-dd');
      const data = await api.getInstructorSchedule(dateFrom, dateTo);
      // Handle both array and object API responses
      const sessionsArray = Array.isArray(data) ? data : (data?.sessions || []);
      setSessions(sessionsArray);
    } catch (error) {
      console.error('Error fetching schedule:', error);
    } finally {
      setIsLoading(false);
    }
  }, [weekStart]);

  useEffect(() => {
    fetchSessions();
  }, [fetchSessions]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchSessions();
    setRefreshing(false);
  };

  const goToPreviousWeek = () => {
    setWeekStart(addDays(weekStart, -7));
  };

  const goToNextWeek = () => {
    setWeekStart(addDays(weekStart, 7));
  };

  const goToToday = () => {
    const today = new Date();
    setWeekStart(startOfWeek(today, { weekStartsOn: 1 }));
    setSelectedDate(today);
  };

  const getSessionsForDate = (date: Date) => {
    return sessions.filter((s) => {
      const sessionDate = safeParseDateISO(s.session_date);
      return sessionDate && isSameDay(sessionDate, date);
    });
  };

  const formatTime = (time: string) => time.substring(0, 5);
  const getDayName = (date: Date) => format(date, 'EEE', { locale: dateLocale });
  const getDayNumber = (date: Date) => format(date, 'd');

  const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));
  const selectedSessions = getSessionsForDate(selectedDate);

  const getStatusColor = (session: Session) => {
    if (session.attendance_marked > 0) return colors.secondary;
    const sessionDate = safeParseDateISO(session.session_date);
    if (sessionDate && isSameDay(sessionDate, new Date())) return colors.primary;
    return colors.textSecondary;
  };

  return (
    <View style={styles.container}>
      {/* Week Navigation */}
      <View style={styles.weekNav}>
        <TouchableOpacity onPress={goToPreviousWeek} style={styles.navButton}>
          <Ionicons name="chevron-back" size={24} color={colors.text} />
        </TouchableOpacity>
        <TouchableOpacity onPress={goToToday} style={styles.weekTitle}>
          <Text style={styles.weekTitleText}>
            {format(weekStart, 'd MMM', { locale: dateLocale })} - {format(addDays(weekStart, 6), 'd MMM yyyy', { locale: dateLocale })}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={goToNextWeek} style={styles.navButton}>
          <Ionicons name="chevron-forward" size={24} color={colors.text} />
        </TouchableOpacity>
      </View>

      {/* Day Selector */}
      <View style={styles.daySelector}>
        {weekDays.map((day) => {
          const isSelected = isSameDay(day, selectedDate);
          const isToday = isSameDay(day, new Date());
          const sessionsCount = getSessionsForDate(day).length;

          return (
            <TouchableOpacity
              key={day.toISOString()}
              style={[
                styles.dayButton,
                isSelected && styles.dayButtonSelected,
                isToday && !isSelected && styles.dayButtonToday,
              ]}
              onPress={() => setSelectedDate(day)}
            >
              <Text style={[styles.dayName, isSelected && styles.dayNameSelected]}>
                {getDayName(day)}
              </Text>
              <Text style={[styles.dayNumber, isSelected && styles.dayNumberSelected]}>
                {getDayNumber(day)}
              </Text>
              {sessionsCount > 0 && (
                <View style={[styles.dayDot, isSelected && styles.dayDotSelected]} />
              )}
            </TouchableOpacity>
          );
        })}
      </View>

      {/* Sessions List */}
      <ScrollView
        style={styles.sessionsList}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.secondary} />}
      >
        {isLoading ? (
          <ActivityIndicator size="large" color={colors.secondary} style={{ marginTop: 40 }} />
        ) : selectedSessions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="calendar-outline" size={64} color={colors.border} />
            <Text style={styles.emptyStateTitle}>{t.schedule.noLessons}</Text>
            <Text style={styles.emptyStateText}>
              {format(selectedDate, 'EEEE, d MMMM', { locale: dateLocale })}
            </Text>
          </View>
        ) : (
          <>
            <Text style={styles.dateHeader}>
              {format(selectedDate, 'EEEE, d MMMM yyyy', { locale: dateLocale })}
            </Text>
            {selectedSessions.map((session) => (
              <TouchableOpacity
                key={session.id}
                style={styles.sessionCard}
                onPress={() => navigation.navigate('Attendance', { sessionId: session.id })}
              >
                <View style={[styles.sessionTimeBar, { backgroundColor: getStatusColor(session) }]} />
                <View style={styles.sessionTime}>
                  <Text style={styles.sessionTimeStart}>{formatTime(session.time_start)}</Text>
                  <Text style={styles.sessionTimeEnd}>{formatTime(session.time_end)}</Text>
                </View>
                <View style={styles.sessionContent}>
                  <View style={styles.sessionHeader}>
                    <Text style={styles.sessionTitle}>{session.class_name}</Text>
                    {session.level && (
                      <View style={styles.levelBadge}>
                        <Text style={styles.levelBadgeText}>{session.level}</Text>
                      </View>
                    )}
                  </View>
                  <View style={styles.sessionMeta}>
                    <Ionicons name="location-outline" size={14} color={colors.textSecondary} />
                    <Text style={styles.sessionMetaText}>{session.facility_name}</Text>
                  </View>
                  <View style={styles.sessionFooter}>
                    <View style={styles.sessionStat}>
                      <Ionicons name="people-outline" size={14} color={colors.textSecondary} />
                      <Text style={styles.sessionStatText}>
                        {session.enrolled_count}/{session.max_participants}
                      </Text>
                    </View>
                    {session.attendance_marked > 0 ? (
                      <View style={styles.attendanceBadge}>
                        <Ionicons name="checkmark-circle" size={14} color={colors.secondary} />
                        <Text style={styles.attendanceBadgeText}>{t.schedule.attendanceChecked}</Text>
                      </View>
                    ) : (
                      <View style={styles.attendancePending}>
                        <Ionicons name="time-outline" size={14} color={colors.warning} />
                        <Text style={styles.attendancePendingText}>{t.schedule.checkAttendance}</Text>
                      </View>
                    )}
                  </View>
                </View>
                <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
              </TouchableOpacity>
            ))}
          </>
        )}
        <View style={{ height: 24 }} />
      </ScrollView>
    </View>
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    weekNav: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: 16,
      paddingVertical: 12,
      backgroundColor: colors.surface,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    navButton: {
      width: 40,
      height: 40,
      borderRadius: 20,
      backgroundColor: colors.surfaceSecondary,
      justifyContent: 'center',
      alignItems: 'center',
    },
    weekTitle: {
      flex: 1,
      alignItems: 'center',
    },
    weekTitleText: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    daySelector: {
      flexDirection: 'row',
      backgroundColor: colors.surface,
      paddingVertical: 12,
      paddingHorizontal: 8,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    dayButton: {
      flex: 1,
      alignItems: 'center',
      paddingVertical: 8,
      borderRadius: 12,
      marginHorizontal: 2,
    },
    dayButtonSelected: {
      backgroundColor: colors.secondary,
    },
    dayButtonToday: {
      backgroundColor: colors.secondaryLight,
    },
    dayName: {
      fontSize: 11,
      color: colors.textSecondary,
      marginBottom: 4,
      textTransform: 'uppercase',
    },
    dayNameSelected: {
      color: '#fff',
    },
    dayNumber: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
    },
    dayNumberSelected: {
      color: '#fff',
    },
    dayDot: {
      width: 6,
      height: 6,
      borderRadius: 3,
      backgroundColor: colors.secondary,
      marginTop: 4,
    },
    dayDotSelected: {
      backgroundColor: '#fff',
    },
    sessionsList: {
      flex: 1,
      padding: 16,
    },
    dateHeader: {
      fontSize: 14,
      fontWeight: '500',
      color: colors.textSecondary,
      marginBottom: 12,
      textTransform: 'capitalize',
    },
    emptyState: {
      alignItems: 'center',
      paddingVertical: 60,
    },
    emptyStateTitle: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
      marginTop: 16,
    },
    emptyStateText: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
      textTransform: 'capitalize',
    },
    sessionCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 16,
      marginBottom: 12,
      overflow: 'hidden',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
      elevation: 1,
    },
    sessionTimeBar: {
      width: 4,
      alignSelf: 'stretch',
    },
    sessionTime: {
      width: 60,
      paddingVertical: 16,
      paddingHorizontal: 12,
      alignItems: 'center',
    },
    sessionTimeStart: {
      fontSize: 16,
      fontWeight: '700',
      color: colors.text,
    },
    sessionTimeEnd: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
    },
    sessionContent: {
      flex: 1,
      paddingVertical: 12,
      paddingRight: 8,
    },
    sessionHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
    },
    sessionTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    levelBadge: {
      backgroundColor: colors.primaryLight,
      paddingHorizontal: 8,
      paddingVertical: 2,
      borderRadius: 6,
    },
    levelBadgeText: {
      fontSize: 12,
      fontWeight: '500',
      color: colors.primary,
    },
    sessionMeta: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 4,
      gap: 4,
    },
    sessionMetaText: {
      fontSize: 13,
      color: colors.textSecondary,
    },
    sessionFooter: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 8,
      gap: 12,
    },
    sessionStat: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 4,
    },
    sessionStatText: {
      fontSize: 12,
      color: colors.textSecondary,
    },
    attendanceBadge: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.secondaryLight,
      paddingHorizontal: 8,
      paddingVertical: 4,
      borderRadius: 6,
      gap: 4,
    },
    attendanceBadgeText: {
      fontSize: 12,
      color: colors.secondary,
      fontWeight: '500',
    },
    attendancePending: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.warningLight,
      paddingHorizontal: 8,
      paddingVertical: 4,
      borderRadius: 6,
      gap: 4,
    },
    attendancePendingText: {
      fontSize: 12,
      color: colors.warning,
      fontWeight: '500',
    },
  });
