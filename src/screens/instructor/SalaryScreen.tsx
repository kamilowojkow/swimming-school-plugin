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
import { format, subMonths, addMonths } from 'date-fns';
import { pl } from 'date-fns/locale';
import api from '../../api/client';

// Safe date formatting helper
const safeFormatDate = (dateStr: string | undefined, formatStr: string): string => {
  if (!dateStr) return '-';
  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    return format(date, formatStr, { locale: pl });
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
  duration_minutes: number;
}

interface SalaryData {
  month: number;
  year: number;
  hourly_rate: number;
  total_hours: number;
  total_salary: number;
  sessions_count: number;
  sessions: Session[];
}

export default function SalaryScreen() {
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [salaryData, setSalaryData] = useState<SalaryData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchSalary = async () => {
    try {
      const month = selectedDate.getMonth() + 1;
      const year = selectedDate.getFullYear();
      const data = await api.getSalary(month, year);
      // Ensure sessions is an array
      if (data && !Array.isArray(data.sessions)) {
        data.sessions = [];
      }
      setSalaryData(data);
    } catch (error) {
      console.error('Error fetching salary:', error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    setIsLoading(true);
    fetchSalary();
  }, [selectedDate]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchSalary();
    setRefreshing(false);
  };

  const goToPreviousMonth = () => {
    setSelectedDate(subMonths(selectedDate, 1));
  };

  const goToNextMonth = () => {
    setSelectedDate(addMonths(selectedDate, 1));
  };

  const formatTime = (time: string) => time.substring(0, 5);

  const formatCurrency = (amount: number) => {
    return amount.toLocaleString('pl-PL', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  };

  const groupSessionsByDate = (sessions: Session[]) => {
    const groups: { [key: string]: Session[] } = {};
    sessions.forEach((session) => {
      const date = session.session_date;
      if (!groups[date]) {
        groups[date] = [];
      }
      groups[date].push(session);
    });
    return groups;
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#10b981" />
      </View>
    );
  }

  const sessionsByDate = salaryData ? groupSessionsByDate(salaryData.sessions) : {};

  return (
    <View style={styles.container}>
      {/* Month Navigation */}
      <View style={styles.monthNav}>
        <TouchableOpacity onPress={goToPreviousMonth} style={styles.navButton}>
          <Ionicons name="chevron-back" size={24} color="#374151" />
        </TouchableOpacity>
        <View style={styles.monthTitle}>
          <Text style={styles.monthTitleText}>
            {format(selectedDate, 'LLLL yyyy', { locale: pl })}
          </Text>
        </View>
        <TouchableOpacity onPress={goToNextMonth} style={styles.navButton}>
          <Ionicons name="chevron-forward" size={24} color="#374151" />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {/* Summary Card */}
        <View style={styles.summaryCard}>
          <View style={styles.summaryMain}>
            <Text style={styles.summaryLabel}>Do wypłaty</Text>
            <Text style={styles.summaryAmount}>
              {salaryData ? formatCurrency(salaryData.total_salary) : '0,00'} zł
            </Text>
          </View>

          <View style={styles.summaryStats}>
            <View style={styles.summaryStat}>
              <View style={[styles.summaryStatIcon, { backgroundColor: '#ecfdf5' }]}>
                <Ionicons name="calendar" size={20} color="#10b981" />
              </View>
              <View>
                <Text style={styles.summaryStatValue}>{salaryData?.sessions_count || 0}</Text>
                <Text style={styles.summaryStatLabel}>Zajęć</Text>
              </View>
            </View>

            <View style={styles.summaryStat}>
              <View style={[styles.summaryStatIcon, { backgroundColor: '#eff6ff' }]}>
                <Ionicons name="time" size={20} color="#3b82f6" />
              </View>
              <View>
                <Text style={styles.summaryStatValue}>
                  {salaryData?.total_hours.toFixed(1) || '0'}h
                </Text>
                <Text style={styles.summaryStatLabel}>Godzin</Text>
              </View>
            </View>

            <View style={styles.summaryStat}>
              <View style={[styles.summaryStatIcon, { backgroundColor: '#fef3c7' }]}>
                <Ionicons name="cash" size={20} color="#f59e0b" />
              </View>
              <View>
                <Text style={styles.summaryStatValue}>
                  {salaryData?.hourly_rate || 0} zł
                </Text>
                <Text style={styles.summaryStatLabel}>Za godzinę</Text>
              </View>
            </View>
          </View>
        </View>

        {/* Sessions List */}
        <View style={styles.sessionsSection}>
          <Text style={styles.sectionTitle}>Przeprowadzone zajęcia</Text>

          {Object.keys(sessionsByDate).length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="calendar-outline" size={48} color="#d1d5db" />
              <Text style={styles.emptyStateText}>Brak zajęć w tym miesiącu</Text>
            </View>
          ) : (
            Object.keys(sessionsByDate)
              .sort()
              .reverse()
              .map((date) => (
                <View key={date} style={styles.dayGroup}>
                  <View style={styles.dayHeader}>
                    <Text style={styles.dayDate}>
                      {safeFormatDate(date, 'EEEE, d MMMM')}
                    </Text>
                    <Text style={styles.daySessions}>
                      {sessionsByDate[date].length}{' '}
                      {sessionsByDate[date].length === 1 ? 'zajęcia' : 'zajęć'}
                    </Text>
                  </View>

                  {sessionsByDate[date].map((session) => {
                    const hours = session.duration_minutes / 60;
                    const earnings = hours * (salaryData?.hourly_rate || 0);

                    return (
                      <View key={session.id} style={styles.sessionCard}>
                        <View style={styles.sessionTime}>
                          <Text style={styles.sessionTimeText}>
                            {session.time_start?.substring(0, 5) || '-'}
                          </Text>
                          <Text style={styles.sessionTimeEnd}>
                            {session.time_end?.substring(0, 5) || '-'}
                          </Text>
                        </View>
                        <View style={styles.sessionInfo}>
                          <Text style={styles.sessionTitle}>{session.class_name}</Text>
                          <Text style={styles.sessionDuration}>
                            {hours.toFixed(1)}h • {formatCurrency(earnings)} zł
                          </Text>
                        </View>
                        <View style={styles.sessionEarnings}>
                          <Text style={styles.sessionEarningsText}>
                            +{formatCurrency(earnings)} zł
                          </Text>
                        </View>
                      </View>
                    );
                  })}
                </View>
              ))
          )}
        </View>

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
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  monthNav: {
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
  monthTitle: {
    flex: 1,
    alignItems: 'center',
  },
  monthTitleText: {
    fontSize: 18,
    fontWeight: '600',
    color: '#111827',
    textTransform: 'capitalize',
  },
  content: {
    flex: 1,
  },
  summaryCard: {
    backgroundColor: '#10b981',
    margin: 16,
    borderRadius: 20,
    padding: 20,
    shadowColor: '#10b981',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  summaryMain: {
    alignItems: 'center',
    marginBottom: 20,
  },
  summaryLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginBottom: 4,
  },
  summaryAmount: {
    fontSize: 40,
    fontWeight: '700',
    color: '#fff',
  },
  summaryStats: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    backgroundColor: 'rgba(255,255,255,0.15)',
    borderRadius: 12,
    padding: 16,
  },
  summaryStat: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  summaryStatIcon: {
    width: 40,
    height: 40,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  summaryStatValue: {
    fontSize: 16,
    fontWeight: '700',
    color: '#fff',
  },
  summaryStatLabel: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.8)',
  },
  sessionsSection: {
    padding: 16,
    paddingTop: 0,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#111827',
    marginBottom: 16,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  emptyStateText: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 12,
  },
  dayGroup: {
    marginBottom: 20,
  },
  dayHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  dayDate: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
    textTransform: 'capitalize',
  },
  daySessions: {
    fontSize: 12,
    color: '#6b7280',
  },
  sessionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  sessionTime: {
    width: 50,
    alignItems: 'center',
  },
  sessionTimeText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#111827',
  },
  sessionTimeEnd: {
    fontSize: 11,
    color: '#9ca3af',
  },
  sessionInfo: {
    flex: 1,
    marginLeft: 12,
  },
  sessionTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#111827',
  },
  sessionDuration: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 2,
  },
  sessionEarnings: {
    backgroundColor: '#ecfdf5',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  sessionEarningsText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#10b981',
  },
});
