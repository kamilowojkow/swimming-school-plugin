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
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
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
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');

  const [selectedDate, setSelectedDate] = useState(new Date());
  const [salaryData, setSalaryData] = useState<SalaryData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const dateLocale = language === 'pl' ? pl : enUS;
  const styles = createStyles(colors);

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
    return amount.toLocaleString(language === 'pl' ? 'pl-PL' : 'en-US', {
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
        <ActivityIndicator size="large" color={colors.secondary} />
      </View>
    );
  }

  const sessionsByDate = salaryData ? groupSessionsByDate(salaryData.sessions) : {};

  return (
    <View style={styles.container}>
      {/* Month Navigation */}
      <View style={styles.monthNav}>
        <TouchableOpacity onPress={goToPreviousMonth} style={styles.navButton}>
          <Ionicons name="chevron-back" size={24} color={colors.text} />
        </TouchableOpacity>
        <View style={styles.monthTitle}>
          <Text style={styles.monthTitleText}>
            {format(selectedDate, 'LLLL yyyy', { locale: dateLocale })}
          </Text>
        </View>
        <TouchableOpacity onPress={goToNextMonth} style={styles.navButton}>
          <Ionicons name="chevron-forward" size={24} color={colors.text} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.secondary} />}
      >
        {/* Summary Card */}
        <View style={styles.summaryCard}>
          <View style={styles.summaryMain}>
            <Text style={styles.summaryLabel}>{t.salary.toPay}</Text>
            <Text style={styles.summaryAmount}>
              {salaryData ? formatCurrency(salaryData.total_salary) : '0,00'} {language === 'pl' ? 'zł' : 'PLN'}
            </Text>
          </View>

          <View style={styles.summaryStats}>
            <View style={styles.summaryStat}>
              <View style={[styles.summaryStatIcon, { backgroundColor: colors.secondaryLight }]}>
                <Ionicons name="calendar" size={20} color={colors.secondary} />
              </View>
              <View>
                <Text style={styles.summaryStatValue}>{salaryData?.sessions_count || 0}</Text>
                <Text style={styles.summaryStatLabel}>{t.salary.sessions}</Text>
              </View>
            </View>

            <View style={styles.summaryStat}>
              <View style={[styles.summaryStatIcon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="time" size={20} color={colors.primary} />
              </View>
              <View>
                <Text style={styles.summaryStatValue}>
                  {salaryData?.total_hours.toFixed(1) || '0'}h
                </Text>
                <Text style={styles.summaryStatLabel}>{t.salary.hoursWorked}</Text>
              </View>
            </View>

            <View style={styles.summaryStat}>
              <View style={[styles.summaryStatIcon, { backgroundColor: colors.warningLight }]}>
                <Ionicons name="cash" size={20} color={colors.warning} />
              </View>
              <View>
                <Text style={styles.summaryStatValue}>
                  {salaryData?.hourly_rate || 0} {language === 'pl' ? 'zł' : 'PLN'}
                </Text>
                <Text style={styles.summaryStatLabel}>{t.salary.hourlyRate}</Text>
              </View>
            </View>
          </View>
        </View>

        {/* Sessions List */}
        <View style={styles.sessionsSection}>
          <Text style={styles.sectionTitle}>{t.salary.conductedSessions}</Text>

          {Object.keys(sessionsByDate).length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="calendar-outline" size={48} color={colors.border} />
              <Text style={styles.emptyStateText}>{t.salary.noSessionsThisMonth}</Text>
            </View>
          ) : (
            Object.keys(sessionsByDate)
              .sort()
              .reverse()
              .map((date) => (
                <View key={date} style={styles.dayGroup}>
                  <View style={styles.dayHeader}>
                    <Text style={styles.dayDate}>
                      {safeFormatDate(date, 'EEEE, d MMMM', dateLocale)}
                    </Text>
                    <Text style={styles.daySessions}>
                      {sessionsByDate[date].length}{' '}
                      {language === 'pl'
                        ? (sessionsByDate[date].length === 1 ? 'zajęcia' : 'zajęć')
                        : (sessionsByDate[date].length === 1 ? 'session' : 'sessions')}
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
                            {hours.toFixed(1)}h • {formatCurrency(earnings)} {language === 'pl' ? 'zł' : 'PLN'}
                          </Text>
                        </View>
                        <View style={styles.sessionEarnings}>
                          <Text style={styles.sessionEarningsText}>
                            +{formatCurrency(earnings)} {language === 'pl' ? 'zł' : 'PLN'}
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

const createStyles = (colors: ReturnType<typeof useThemeColors>) =>
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
    monthNav: {
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
    monthTitle: {
      flex: 1,
      alignItems: 'center',
    },
    monthTitleText: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
      textTransform: 'capitalize',
    },
    content: {
      flex: 1,
    },
    summaryCard: {
      backgroundColor: colors.secondary,
      margin: 16,
      borderRadius: 20,
      padding: 20,
      shadowColor: colors.secondary,
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
      color: colors.text,
      marginBottom: 16,
    },
    emptyState: {
      alignItems: 'center',
      paddingVertical: 40,
    },
    emptyStateText: {
      fontSize: 14,
      color: colors.textSecondary,
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
      color: colors.text,
      textTransform: 'capitalize',
    },
    daySessions: {
      fontSize: 12,
      color: colors.textSecondary,
    },
    sessionCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 12,
      marginBottom: 8,
      shadowColor: colors.shadow,
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
      color: colors.text,
    },
    sessionTimeEnd: {
      fontSize: 11,
      color: colors.textTertiary,
    },
    sessionInfo: {
      flex: 1,
      marginLeft: 12,
    },
    sessionTitle: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.text,
    },
    sessionDuration: {
      fontSize: 12,
      color: colors.textSecondary,
      marginTop: 2,
    },
    sessionEarnings: {
      backgroundColor: colors.secondaryLight,
      paddingHorizontal: 10,
      paddingVertical: 6,
      borderRadius: 8,
    },
    sessionEarningsText: {
      fontSize: 13,
      fontWeight: '600',
      color: colors.secondary,
    },
  });
