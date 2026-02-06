import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { format, addDays, startOfWeek, isSameDay, isToday } from 'date-fns';
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

interface Session {
  id: number;
  session_date: string;
  time_start: string;
  time_end: string;
  class_name: string;
  facility_name: string;
  instructor_name: string;
  child_id: number;
  child_first_name: string;
  child_last_name: string;
  status: 'scheduled' | 'completed' | 'cancelled';
  attendance_status?: 'present' | 'absent' | 'late' | 'excused';
}

interface DayInfo {
  date: Date;
  sessions: Session[];
}

const CHILD_COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];

export default function ScheduleScreen() {
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();
  const dateLocale = language === 'pl' ? pl : enUS;

  const [sessions, setSessions] = useState<Session[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [currentWeekStart, setCurrentWeekStart] = useState(
    startOfWeek(new Date(), { weekStartsOn: 1 })
  );
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [viewMode, setViewMode] = useState<'week' | 'list'>('week');

  const fetchSchedule = async () => {
    try {
      const data = await api.getParentSchedule();
      const sessionsArray = Array.isArray(data) ? data : (data?.sessions || data?.schedule || []);
      setSessions(sessionsArray);
    } catch (error) {
      console.error('Error fetching schedule:', error);
      setSessions([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchSchedule();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchSchedule();
    setRefreshing(false);
  };

  const getWeekDays = (): DayInfo[] => {
    const days: DayInfo[] = [];
    for (let i = 0; i < 7; i++) {
      const date = addDays(currentWeekStart, i);
      const daySessions = sessions.filter((s) =>
        isSameDay(new Date(s.session_date), date)
      );
      days.push({ date, sessions: daySessions });
    }
    return days;
  };

  const goToPreviousWeek = () => {
    setCurrentWeekStart(addDays(currentWeekStart, -7));
  };

  const goToNextWeek = () => {
    setCurrentWeekStart(addDays(currentWeekStart, 7));
  };

  const goToToday = () => {
    const today = new Date();
    setCurrentWeekStart(startOfWeek(today, { weekStartsOn: 1 }));
    setSelectedDate(today);
  };

  const getChildColor = (childId: number) => {
    return CHILD_COLORS[childId % CHILD_COLORS.length];
  };

  const getSessionsForDate = (date: Date) => {
    return sessions.filter((s) => isSameDay(new Date(s.session_date), date));
  };

  const weekDays = getWeekDays();
  const selectedDateSessions = getSessionsForDate(selectedDate);

  const upcomingSessions = sessions
    .filter((s) => new Date(s.session_date) >= new Date())
    .sort((a, b) => new Date(a.session_date).getTime() - new Date(b.session_date).getTime())
    .slice(0, 20);

  const styles = createStyles(colors, isDark);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header with View Toggle */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>{t.schedule.title}</Text>
        <View style={styles.viewToggle}>
          <TouchableOpacity
            style={[styles.toggleButton, viewMode === 'week' && styles.toggleButtonActive]}
            onPress={() => setViewMode('week')}
          >
            <Ionicons
              name="calendar"
              size={18}
              color={viewMode === 'week' ? '#fff' : colors.textSecondary}
            />
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.toggleButton, viewMode === 'list' && styles.toggleButtonActive]}
            onPress={() => setViewMode('list')}
          >
            <Ionicons
              name="list"
              size={18}
              color={viewMode === 'list' ? '#fff' : colors.textSecondary}
            />
          </TouchableOpacity>
        </View>
      </View>

      {viewMode === 'week' ? (
        <ScrollView
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
          }
        >
          {/* Week Navigation */}
          <View style={styles.weekNavigation}>
            <TouchableOpacity onPress={goToPreviousWeek} style={styles.navButton}>
              <Ionicons name="chevron-back" size={24} color={colors.text} />
            </TouchableOpacity>

            <TouchableOpacity onPress={goToToday} style={styles.weekLabel}>
              <Text style={styles.weekLabelText}>
                {format(currentWeekStart, 'd MMM', { locale: dateLocale })} -{' '}
                {format(addDays(currentWeekStart, 6), 'd MMM yyyy', { locale: dateLocale })}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity onPress={goToNextWeek} style={styles.navButton}>
              <Ionicons name="chevron-forward" size={24} color={colors.text} />
            </TouchableOpacity>
          </View>

          {/* Day Selector */}
          <View style={styles.daySelector}>
            {weekDays.map((day) => {
              const isSelected = isSameDay(day.date, selectedDate);
              const isTodayDate = isToday(day.date);
              const hasEvents = day.sessions.length > 0;

              return (
                <TouchableOpacity
                  key={day.date.toISOString()}
                  style={[
                    styles.dayButton,
                    isSelected && styles.dayButtonSelected,
                    isTodayDate && !isSelected && styles.dayButtonToday,
                  ]}
                  onPress={() => setSelectedDate(day.date)}
                >
                  <Text
                    style={[
                      styles.dayName,
                      isSelected && styles.dayNameSelected,
                    ]}
                  >
                    {format(day.date, 'EEE', { locale: dateLocale })}
                  </Text>
                  <Text
                    style={[
                      styles.dayNumber,
                      isSelected && styles.dayNumberSelected,
                    ]}
                  >
                    {format(day.date, 'd')}
                  </Text>
                  {hasEvents && (
                    <View
                      style={[
                        styles.eventDot,
                        isSelected && styles.eventDotSelected,
                      ]}
                    />
                  )}
                </TouchableOpacity>
              );
            })}
          </View>

          {/* Selected Day Sessions */}
          <View style={styles.sessionsContainer}>
            <Text style={styles.dateHeader}>
              {format(selectedDate, 'EEEE, d MMMM', { locale: dateLocale })}
            </Text>

            {selectedDateSessions.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="calendar-outline" size={48} color={colors.textTertiary} />
                <Text style={styles.emptyText}>{t.schedule.noLessons}</Text>
              </View>
            ) : (
              selectedDateSessions.map((session) => (
                <View
                  key={session.id}
                  style={[
                    styles.sessionCard,
                    { borderLeftColor: getChildColor(session.child_id) },
                  ]}
                >
                  <View style={styles.sessionTime}>
                    <Text style={styles.timeText}>
                      {session.time_start.substring(0, 5)}
                    </Text>
                    <Text style={styles.timeDivider}>-</Text>
                    <Text style={styles.timeText}>
                      {session.time_end.substring(0, 5)}
                    </Text>
                  </View>

                  <View style={styles.sessionDetails}>
                    <Text style={styles.sessionTitle}>{session.class_name}</Text>
                    <View style={styles.sessionMeta}>
                      <View style={styles.metaItem}>
                        <Ionicons name="person" size={12} color={colors.textSecondary} />
                        <Text style={styles.metaText}>{session.child_first_name}</Text>
                      </View>
                      <View style={styles.metaItem}>
                        <Ionicons name="location" size={12} color={colors.textSecondary} />
                        <Text style={styles.metaText}>{session.facility_name}</Text>
                      </View>
                    </View>
                    <Text style={styles.instructorText}>
                      {t.schedule.instructor}: {session.instructor_name}
                    </Text>
                  </View>

                  {session.attendance_status && (
                    <View
                      style={[
                        styles.attendanceBadge,
                        session.attendance_status === 'present' && styles.attendancePresent,
                        session.attendance_status === 'absent' && styles.attendanceAbsent,
                      ]}
                    >
                      <Ionicons
                        name={session.attendance_status === 'present' ? 'checkmark' : 'close'}
                        size={14}
                        color="#fff"
                      />
                    </View>
                  )}
                </View>
              ))
            )}
          </View>
        </ScrollView>
      ) : (
        // List View
        <ScrollView
          style={styles.listView}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
          }
        >
          <Text style={styles.listHeader}>{t.dashboard.upcomingLessons}</Text>

          {upcomingSessions.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="calendar-outline" size={48} color={colors.textTertiary} />
              <Text style={styles.emptyText}>{t.schedule.noLessons}</Text>
            </View>
          ) : (
            upcomingSessions.map((session, index) => {
              const sessionDate = new Date(session.session_date);
              const showDateHeader =
                index === 0 ||
                !isSameDay(sessionDate, new Date(upcomingSessions[index - 1].session_date));

              return (
                <View key={session.id}>
                  {showDateHeader && (
                    <View style={styles.listDateHeader}>
                      <Text style={styles.listDateText}>
                        {isToday(sessionDate)
                          ? t.common.today
                          : format(sessionDate, 'EEEE, d MMMM', { locale: dateLocale })}
                      </Text>
                    </View>
                  )}

                  <View
                    style={[
                      styles.listSessionCard,
                      { borderLeftColor: getChildColor(session.child_id) },
                    ]}
                  >
                    <View style={styles.listSessionTime}>
                      <Text style={styles.listTimeText}>
                        {session.time_start.substring(0, 5)}
                      </Text>
                    </View>

                    <View style={styles.listSessionDetails}>
                      <Text style={styles.listSessionTitle}>{session.class_name}</Text>
                      <View style={styles.listSessionMeta}>
                        <View
                          style={[
                            styles.childBadge,
                            { backgroundColor: getChildColor(session.child_id) + '20' },
                          ]}
                        >
                          <Text
                            style={[
                              styles.childBadgeText,
                              { color: getChildColor(session.child_id) },
                            ]}
                          >
                            {session.child_first_name}
                          </Text>
                        </View>
                        <Text style={styles.listMetaText}>
                          {session.facility_name}
                        </Text>
                      </View>
                    </View>
                  </View>
                </View>
              );
            })
          )}

          <View style={{ height: 24 }} />
        </ScrollView>
      )}

      {/* Legend */}
      {sessions.length > 0 && (
        <View style={styles.legend}>
          {[...new Map(sessions.map((s) => [s.child_id, s])).values()].map((s) => (
            <View key={s.child_id} style={styles.legendItem}>
              <View
                style={[styles.legendDot, { backgroundColor: getChildColor(s.child_id) }]}
              />
              <Text style={styles.legendText}>{s.child_first_name}</Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>, isDark: boolean) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    loadingContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: colors.background,
    },
    header: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      padding: 16,
      backgroundColor: colors.surface,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    headerTitle: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.text,
    },
    viewToggle: {
      flexDirection: 'row',
      backgroundColor: colors.surfaceSecondary,
      borderRadius: 10,
      padding: 4,
    },
    toggleButton: {
      paddingHorizontal: 12,
      paddingVertical: 8,
      borderRadius: 8,
    },
    toggleButtonActive: {
      backgroundColor: colors.primary,
    },
    weekNavigation: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: 16,
      paddingVertical: 12,
      backgroundColor: colors.surface,
    },
    navButton: {
      width: 40,
      height: 40,
      borderRadius: 20,
      backgroundColor: colors.surfaceSecondary,
      justifyContent: 'center',
      alignItems: 'center',
    },
    weekLabel: {
      paddingHorizontal: 16,
      paddingVertical: 8,
    },
    weekLabelText: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    daySelector: {
      flexDirection: 'row',
      paddingHorizontal: 8,
      paddingVertical: 12,
      backgroundColor: colors.surface,
      gap: 4,
    },
    dayButton: {
      flex: 1,
      alignItems: 'center',
      paddingVertical: 12,
      borderRadius: 12,
    },
    dayButtonSelected: {
      backgroundColor: colors.primary,
    },
    dayButtonToday: {
      backgroundColor: colors.primaryLight,
    },
    dayName: {
      fontSize: 11,
      color: colors.textSecondary,
      textTransform: 'uppercase',
      marginBottom: 4,
    },
    dayNameSelected: {
      color: 'rgba(255,255,255,0.8)',
    },
    dayNumber: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    dayNumberSelected: {
      color: '#fff',
    },
    eventDot: {
      width: 6,
      height: 6,
      borderRadius: 3,
      backgroundColor: colors.primary,
      marginTop: 6,
    },
    eventDotSelected: {
      backgroundColor: '#fff',
    },
    sessionsContainer: {
      padding: 16,
    },
    dateHeader: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 16,
      textTransform: 'capitalize',
    },
    emptyState: {
      alignItems: 'center',
      padding: 32,
      backgroundColor: colors.surface,
      borderRadius: 16,
    },
    emptyText: {
      fontSize: 15,
      color: colors.textSecondary,
      marginTop: 12,
    },
    sessionCard: {
      flexDirection: 'row',
      backgroundColor: colors.surface,
      borderRadius: 16,
      padding: 16,
      marginBottom: 12,
      borderLeftWidth: 4,
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: isDark ? 0.3 : 0.05,
      shadowRadius: 2,
      elevation: 1,
    },
    sessionTime: {
      width: 56,
      alignItems: 'center',
    },
    timeText: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.text,
    },
    timeDivider: {
      fontSize: 12,
      color: colors.textTertiary,
      marginVertical: 2,
    },
    sessionDetails: {
      flex: 1,
      marginLeft: 12,
    },
    sessionTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 6,
    },
    sessionMeta: {
      flexDirection: 'row',
      gap: 12,
      marginBottom: 4,
    },
    metaItem: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 4,
    },
    metaText: {
      fontSize: 13,
      color: colors.textSecondary,
    },
    instructorText: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 4,
    },
    attendanceBadge: {
      width: 28,
      height: 28,
      borderRadius: 14,
      justifyContent: 'center',
      alignItems: 'center',
      alignSelf: 'center',
    },
    attendancePresent: {
      backgroundColor: '#16a34a',
    },
    attendanceAbsent: {
      backgroundColor: '#dc2626',
    },
    // List View Styles
    listView: {
      flex: 1,
    },
    listHeader: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.text,
      padding: 16,
    },
    listDateHeader: {
      paddingHorizontal: 16,
      paddingVertical: 8,
      backgroundColor: colors.surfaceSecondary,
    },
    listDateText: {
      fontSize: 13,
      fontWeight: '600',
      color: colors.textSecondary,
      textTransform: 'capitalize',
    },
    listSessionCard: {
      flexDirection: 'row',
      backgroundColor: colors.surface,
      padding: 16,
      borderLeftWidth: 4,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    listSessionTime: {
      width: 50,
    },
    listTimeText: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    listSessionDetails: {
      flex: 1,
      marginLeft: 12,
    },
    listSessionTitle: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 6,
    },
    listSessionMeta: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
    },
    childBadge: {
      paddingHorizontal: 8,
      paddingVertical: 3,
      borderRadius: 8,
    },
    childBadgeText: {
      fontSize: 11,
      fontWeight: '600',
    },
    listMetaText: {
      fontSize: 12,
      color: colors.textSecondary,
    },
    // Legend
    legend: {
      flexDirection: 'row',
      justifyContent: 'center',
      gap: 16,
      paddingVertical: 12,
      backgroundColor: colors.surface,
      borderTopWidth: 1,
      borderTopColor: colors.border,
    },
    legendItem: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
    },
    legendDot: {
      width: 10,
      height: 10,
      borderRadius: 5,
    },
    legendText: {
      fontSize: 12,
      color: colors.textSecondary,
    },
  });
