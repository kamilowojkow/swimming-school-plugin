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
import { pl } from 'date-fns/locale';
import api from '../../api/client';

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
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [weekStart, setWeekStart] = useState(startOfWeek(new Date(), { weekStartsOn: 1 }));
  const [sessions, setSessions] = useState<Session[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchSessions = useCallback(async () => {
    try {
      const dateFrom = format(weekStart, 'yyyy-MM-dd');
      const dateTo = format(addDays(weekStart, 13), 'yyyy-MM-dd');
      const data = await api.getInstructorSchedule(dateFrom, dateTo);
      setSessions(data);
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
    return sessions.filter((s) => isSameDay(parseISO(s.session_date), date));
  };

  const formatTime = (time: string) => time.substring(0, 5);
  const getDayName = (date: Date) => format(date, 'EEE', { locale: pl });
  const getDayNumber = (date: Date) => format(date, 'd');

  const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));
  const selectedSessions = getSessionsForDate(selectedDate);

  const getStatusColor = (session: Session) => {
    if (session.attendance_marked > 0) return '#10b981';
    if (isSameDay(parseISO(session.session_date), new Date())) return '#3b82f6';
    return '#6b7280';
  };

  return (
    <View style={styles.container}>
      {/* Week Navigation */}
      <View style={styles.weekNav}>
        <TouchableOpacity onPress={goToPreviousWeek} style={styles.navButton}>
          <Ionicons name="chevron-back" size={24} color="#374151" />
        </TouchableOpacity>
        <TouchableOpacity onPress={goToToday} style={styles.weekTitle}>
          <Text style={styles.weekTitleText}>
            {format(weekStart, 'd MMM', { locale: pl })} - {format(addDays(weekStart, 6), 'd MMM yyyy', { locale: pl })}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={goToNextWeek} style={styles.navButton}>
          <Ionicons name="chevron-forward" size={24} color="#374151" />
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
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {isLoading ? (
          <ActivityIndicator size="large" color="#10b981" style={{ marginTop: 40 }} />
        ) : selectedSessions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="calendar-outline" size={64} color="#d1d5db" />
            <Text style={styles.emptyStateTitle}>Brak zajęć</Text>
            <Text style={styles.emptyStateText}>
              {format(selectedDate, 'EEEE, d MMMM', { locale: pl })}
            </Text>
          </View>
        ) : (
          <>
            <Text style={styles.dateHeader}>
              {format(selectedDate, 'EEEE, d MMMM yyyy', { locale: pl })}
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
                    <Ionicons name="location-outline" size={14} color="#6b7280" />
                    <Text style={styles.sessionMetaText}>{session.facility_name}</Text>
                  </View>
                  <View style={styles.sessionFooter}>
                    <View style={styles.sessionStat}>
                      <Ionicons name="people-outline" size={14} color="#6b7280" />
                      <Text style={styles.sessionStatText}>
                        {session.enrolled_count}/{session.max_participants}
                      </Text>
                    </View>
                    {session.attendance_marked > 0 ? (
                      <View style={styles.attendanceBadge}>
                        <Ionicons name="checkmark-circle" size={14} color="#10b981" />
                        <Text style={styles.attendanceBadgeText}>Obecność sprawdzona</Text>
                      </View>
                    ) : (
                      <View style={styles.attendancePending}>
                        <Ionicons name="time-outline" size={14} color="#f59e0b" />
                        <Text style={styles.attendancePendingText}>Sprawdź obecność</Text>
                      </View>
                    )}
                  </View>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
              </TouchableOpacity>
            ))}
          </>
        )}
        <View style={{ height: 24 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  weekNav: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  navButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#f3f4f6',
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
    color: '#111827',
  },
  daySelector: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    paddingVertical: 12,
    paddingHorizontal: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  dayButton: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 8,
    borderRadius: 12,
    marginHorizontal: 2,
  },
  dayButtonSelected: {
    backgroundColor: '#10b981',
  },
  dayButtonToday: {
    backgroundColor: '#ecfdf5',
  },
  dayName: {
    fontSize: 11,
    color: '#6b7280',
    marginBottom: 4,
    textTransform: 'uppercase',
  },
  dayNameSelected: {
    color: '#fff',
  },
  dayNumber: {
    fontSize: 18,
    fontWeight: '600',
    color: '#111827',
  },
  dayNumberSelected: {
    color: '#fff',
  },
  dayDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#10b981',
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
    color: '#6b7280',
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
    color: '#374151',
    marginTop: 16,
  },
  emptyStateText: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 4,
    textTransform: 'capitalize',
  },
  sessionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 16,
    marginBottom: 12,
    overflow: 'hidden',
    shadowColor: '#000',
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
    color: '#111827',
  },
  sessionTimeEnd: {
    fontSize: 12,
    color: '#9ca3af',
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
    color: '#111827',
  },
  levelBadge: {
    backgroundColor: '#eff6ff',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  levelBadgeText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#3b82f6',
  },
  sessionMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
    gap: 4,
  },
  sessionMetaText: {
    fontSize: 13,
    color: '#6b7280',
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
    color: '#6b7280',
  },
  attendanceBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ecfdf5',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    gap: 4,
  },
  attendanceBadgeText: {
    fontSize: 12,
    color: '#10b981',
    fontWeight: '500',
  },
  attendancePending: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef3c7',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    gap: 4,
  },
  attendancePendingText: {
    fontSize: 12,
    color: '#f59e0b',
    fontWeight: '500',
  },
});
