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
import { pl } from 'date-fns/locale';
import api from '../../api/client';

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

  // Group sessions by child for the list view
  const upcomingSessions = sessions
    .filter((s) => new Date(s.session_date) >= new Date())
    .sort((a, b) => new Date(a.session_date).getTime() - new Date(b.session_date).getTime())
    .slice(0, 20);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header with View Toggle */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Harmonogram</Text>
        <View style={styles.viewToggle}>
          <TouchableOpacity
            style={[styles.toggleButton, viewMode === 'week' && styles.toggleButtonActive]}
            onPress={() => setViewMode('week')}
          >
            <Ionicons
              name="calendar"
              size={18}
              color={viewMode === 'week' ? '#fff' : '#6b7280'}
            />
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.toggleButton, viewMode === 'list' && styles.toggleButtonActive]}
            onPress={() => setViewMode('list')}
          >
            <Ionicons
              name="list"
              size={18}
              color={viewMode === 'list' ? '#fff' : '#6b7280'}
            />
          </TouchableOpacity>
        </View>
      </View>

      {viewMode === 'week' ? (
        <ScrollView
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        >
          {/* Week Navigation */}
          <View style={styles.weekNavigation}>
            <TouchableOpacity onPress={goToPreviousWeek} style={styles.navButton}>
              <Ionicons name="chevron-back" size={24} color="#374151" />
            </TouchableOpacity>

            <TouchableOpacity onPress={goToToday} style={styles.weekLabel}>
              <Text style={styles.weekLabelText}>
                {format(currentWeekStart, 'd MMM', { locale: pl })} -{' '}
                {format(addDays(currentWeekStart, 6), 'd MMM yyyy', { locale: pl })}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity onPress={goToNextWeek} style={styles.navButton}>
              <Ionicons name="chevron-forward" size={24} color="#374151" />
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
                    {format(day.date, 'EEE', { locale: pl })}
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
              {format(selectedDate, 'EEEE, d MMMM', { locale: pl })}
            </Text>

            {selectedDateSessions.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="calendar-outline" size={48} color="#d1d5db" />
                <Text style={styles.emptyText}>Brak zajęć w tym dniu</Text>
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
                        <Ionicons name="person" size={12} color="#6b7280" />
                        <Text style={styles.metaText}>{session.child_first_name}</Text>
                      </View>
                      <View style={styles.metaItem}>
                        <Ionicons name="location" size={12} color="#6b7280" />
                        <Text style={styles.metaText}>{session.facility_name}</Text>
                      </View>
                    </View>
                    <Text style={styles.instructorText}>
                      Instruktor: {session.instructor_name}
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
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        >
          <Text style={styles.listHeader}>Nadchodzące zajęcia</Text>

          {upcomingSessions.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="calendar-outline" size={48} color="#d1d5db" />
              <Text style={styles.emptyText}>Brak nadchodzących zajęć</Text>
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
                          ? 'Dzisiaj'
                          : format(sessionDate, 'EEEE, d MMMM', { locale: pl })}
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

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#f3f4f6',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#111827',
  },
  viewToggle: {
    flexDirection: 'row',
    backgroundColor: '#f3f4f6',
    borderRadius: 10,
    padding: 4,
  },
  toggleButton: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  toggleButtonActive: {
    backgroundColor: '#3b82f6',
  },
  weekNavigation: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#fff',
  },
  navButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#f3f4f6',
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
    color: '#374151',
  },
  daySelector: {
    flexDirection: 'row',
    paddingHorizontal: 8,
    paddingVertical: 12,
    backgroundColor: '#fff',
    gap: 4,
  },
  dayButton: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 12,
    borderRadius: 12,
  },
  dayButtonSelected: {
    backgroundColor: '#3b82f6',
  },
  dayButtonToday: {
    backgroundColor: '#eff6ff',
  },
  dayName: {
    fontSize: 11,
    color: '#6b7280',
    textTransform: 'uppercase',
    marginBottom: 4,
  },
  dayNameSelected: {
    color: 'rgba(255,255,255,0.8)',
  },
  dayNumber: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
  },
  dayNumberSelected: {
    color: '#fff',
  },
  eventDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#3b82f6',
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
    color: '#374151',
    marginBottom: 16,
    textTransform: 'capitalize',
  },
  emptyState: {
    alignItems: 'center',
    padding: 32,
    backgroundColor: '#fff',
    borderRadius: 16,
  },
  emptyText: {
    fontSize: 15,
    color: '#6b7280',
    marginTop: 12,
  },
  sessionCard: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderLeftWidth: 4,
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
  timeText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
  },
  timeDivider: {
    fontSize: 12,
    color: '#9ca3af',
    marginVertical: 2,
  },
  sessionDetails: {
    flex: 1,
    marginLeft: 12,
  },
  sessionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
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
    color: '#6b7280',
  },
  instructorText: {
    fontSize: 12,
    color: '#9ca3af',
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
    color: '#111827',
    padding: 16,
  },
  listDateHeader: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: '#f3f4f6',
  },
  listDateText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#6b7280',
    textTransform: 'capitalize',
  },
  listSessionCard: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    padding: 16,
    borderLeftWidth: 4,
    borderBottomWidth: 1,
    borderBottomColor: '#f3f4f6',
  },
  listSessionTime: {
    width: 50,
  },
  listTimeText: {
    fontSize: 15,
    fontWeight: '600',
    color: '#374151',
  },
  listSessionDetails: {
    flex: 1,
    marginLeft: 12,
  },
  listSessionTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#111827',
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
    color: '#6b7280',
  },
  // Legend
  legend: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 16,
    paddingVertical: 12,
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#f3f4f6',
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
    color: '#6b7280',
  },
});
