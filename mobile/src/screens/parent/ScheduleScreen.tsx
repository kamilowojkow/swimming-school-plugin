import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Alert,
  Modal,
  TextInput,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { format, isToday, isTomorrow } from 'date-fns';
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
  facility_name: string;
  instructor_name: string;
  child_id: number;
  child_first_name: string;
  child_last_name: string;
  status: 'scheduled' | 'completed' | 'cancelled';
  attendance_status?: 'present' | 'absent' | 'late' | 'excused';
  can_report_absence?: boolean;
  is_absent?: boolean;
}

const CHILD_COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];

export default function ScheduleScreen() {
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();
  const dateLocale = language === 'pl' ? pl : enUS;

  const [sessions, setSessions] = useState<Session[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Absence modal state
  const [showAbsenceModal, setShowAbsenceModal] = useState(false);
  const [selectedSession, setSelectedSession] = useState<Session | null>(null);
  const [absenceReason, setAbsenceReason] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const fetchSchedule = useCallback(async () => {
    try {
      const data = await api.getParentSchedule();
      const sessionsArray = Array.isArray(data) ? data : (data?.sessions || data?.schedule || []);
      const sorted = sessionsArray
        .filter((s: Session) => new Date(s.session_date) >= new Date(new Date().toDateString()))
        .sort((a: Session, b: Session) =>
          new Date(a.session_date).getTime() - new Date(b.session_date).getTime() ||
          (a.time_start || '').localeCompare(b.time_start || '')
        );
      setSessions(sorted);
    } catch (error) {
      console.error('Error fetching schedule:', error);
      setSessions([]);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchSchedule();
  }, [fetchSchedule]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchSchedule();
    setRefreshing(false);
  };

  const getChildColor = (childId: number) => {
    return CHILD_COLORS[childId % CHILD_COLORS.length];
  };

  const canReportAbsence = (session: Session): boolean => {
    if (session.can_report_absence !== undefined) return session.can_report_absence;
    if (session.is_absent || session.attendance_status === 'absent') return false;
    const sessionDateTime = new Date(`${session.session_date}T${session.time_start}`);
    const hoursUntil = (sessionDateTime.getTime() - Date.now()) / (1000 * 60 * 60);
    return hoursUntil >= 24;
  };

  const isSessionAbsent = (session: Session): boolean => {
    return session.is_absent === true || session.attendance_status === 'absent';
  };

  // Sessions available for absence reporting
  const reportableSessions = sessions.filter((s) => canReportAbsence(s));

  const openAbsenceModal = () => {
    setSelectedSession(null);
    setAbsenceReason('');
    setShowAbsenceModal(true);
  };

  const submitAbsence = async () => {
    if (!selectedSession) return;
    setIsSubmitting(true);
    try {
      await api.reportAbsence(selectedSession.id, selectedSession.child_id, absenceReason);
      Alert.alert(
        language === 'pl' ? 'Sukces' : 'Success',
        language === 'pl' ? 'Nieobecność została zgłoszona' : 'Absence has been reported'
      );
      setShowAbsenceModal(false);
      setSelectedSession(null);
      setAbsenceReason('');
      fetchSchedule();
    } catch (error) {
      Alert.alert(
        t.common.error,
        language === 'pl' ? 'Nie udało się zgłosić nieobecności' : 'Failed to report absence'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const getDateBadgeType = (dateStr: string): 'today' | 'tomorrow' | null => {
    const date = new Date(dateStr);
    if (isToday(date)) return 'today';
    if (isTomorrow(date)) return 'tomorrow';
    return null;
  };

  // Group sessions by date
  const groupedSessions: { date: string; sessions: Session[] }[] = [];
  sessions.forEach((session) => {
    const lastGroup = groupedSessions[groupedSessions.length - 1];
    if (lastGroup && lastGroup.date === session.session_date) {
      lastGroup.sessions.push(session);
    } else {
      groupedSessions.push({ date: session.session_date, sessions: [session] });
    }
  });

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
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>{t.schedule.title}</Text>
        <Text style={styles.headerSubtitle}>
          {language === 'pl' ? 'Zaplanowane zajęcia' : 'Scheduled sessions'}
        </Text>
      </View>

      {/* Report Absence Button */}
      {reportableSessions.length > 0 && (
        <TouchableOpacity style={styles.reportButton} onPress={openAbsenceModal}>
          <Ionicons name="add-circle" size={20} color="#fff" />
          <Text style={styles.reportButtonText}>{t.absences.reportAbsence}</Text>
        </TouchableOpacity>
      )}

      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
        }
      >
        {groupedSessions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="calendar-outline" size={56} color={colors.textTertiary} />
            <Text style={styles.emptyTitle}>{t.schedule.noLessons}</Text>
            <Text style={styles.emptyText}>
              {language === 'pl'
                ? 'Brak zaplanowanych zajęć'
                : 'No scheduled sessions'}
            </Text>
          </View>
        ) : (
          <View style={styles.timeline}>
            {/* Timeline vertical line */}
            <View style={styles.timelineLine} />

            {groupedSessions.map((group) => {
              const badgeType = getDateBadgeType(group.date);
              const isTodayGroup = badgeType === 'today';

              return (
                <View key={group.date}>
                  {/* Date Header */}
                  <View style={styles.dateHeader}>
                    <View
                      style={[
                        styles.dateDot,
                        isTodayGroup && styles.dateDotToday,
                        badgeType === 'tomorrow' && styles.dateDotTomorrow,
                      ]}
                    />
                    <View style={styles.dateLabelContainer}>
                      {badgeType && (
                        <View
                          style={[
                            styles.dateBadge,
                            badgeType === 'today' && styles.dateBadgeToday,
                            badgeType === 'tomorrow' && styles.dateBadgeTomorrow,
                          ]}
                        >
                          <Text style={styles.dateBadgeText}>
                            {badgeType === 'today'
                              ? (language === 'pl' ? 'Dzisiaj' : 'Today')
                              : (language === 'pl' ? 'Jutro' : 'Tomorrow')}
                          </Text>
                        </View>
                      )}
                      <Text style={[styles.dateText, isTodayGroup && styles.dateTextToday]}>
                        {format(new Date(group.date), badgeType ? 'd MMMM' : 'EEEE, d MMMM', { locale: dateLocale })}
                      </Text>
                    </View>
                  </View>

                  {/* Sessions for this date */}
                  {group.sessions.map((session) => {
                    const absent = isSessionAbsent(session);
                    const childColor = getChildColor(session.child_id);

                    return (
                      <View key={session.id} style={styles.timelineItem}>
                        <View style={[styles.itemDot, absent && styles.itemDotAbsent]} />

                        <View
                          style={[
                            styles.sessionCard,
                            { borderLeftColor: childColor },
                            isTodayGroup && styles.sessionCardToday,
                            absent && styles.sessionCardAbsent,
                          ]}
                        >
                          {/* Time bar */}
                          <View style={styles.cardTimeBar}>
                            <Ionicons name="time-outline" size={14} color={colors.primary} />
                            <Text style={styles.cardTimeText}>
                              {session.time_start?.substring(0, 5) || '-'} -{' '}
                              {session.time_end?.substring(0, 5) || '-'}
                            </Text>
                            {absent && (
                              <View style={styles.absentBadge}>
                                <Text style={styles.absentBadgeText}>
                                  {language === 'pl' ? 'Nieobecność' : 'Absent'}
                                </Text>
                              </View>
                            )}
                          </View>

                          {/* Card body */}
                          <View style={styles.cardBody}>
                            <Text style={[styles.cardTitle, absent && styles.cardTitleAbsent]}>
                              {session.class_name}
                            </Text>

                            <View style={styles.cardDetails}>
                              <View style={styles.detailRow}>
                                <Ionicons name="person-outline" size={14} color={colors.textSecondary} />
                                <Text style={styles.detailText}>
                                  {session.child_first_name} {session.child_last_name}
                                </Text>
                              </View>
                              <View style={styles.detailRow}>
                                <Ionicons name="location-outline" size={14} color={colors.textSecondary} />
                                <Text style={styles.detailText}>{session.facility_name}</Text>
                              </View>
                              <View style={styles.detailRow}>
                                <Ionicons name="fitness-outline" size={14} color={colors.textSecondary} />
                                <Text style={styles.detailText}>{session.instructor_name}</Text>
                              </View>
                            </View>
                          </View>
                        </View>
                      </View>
                    );
                  })}
                </View>
              );
            })}
          </View>
        )}

        <View style={{ height: 32 }} />
      </ScrollView>

      {/* Legend */}
      {sessions.length > 0 && (
        <View style={styles.legend}>
          {[...new Map(sessions.map((s) => [s.child_id, s])).values()].map((s) => (
            <View key={s.child_id} style={styles.legendItem}>
              <View style={[styles.legendDot, { backgroundColor: getChildColor(s.child_id) }]} />
              <Text style={styles.legendText}>{s.child_first_name}</Text>
            </View>
          ))}
        </View>
      )}

      {/* Report Absence Modal */}
      <Modal
        visible={showAbsenceModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowAbsenceModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{t.absences.reportAbsence}</Text>
              <TouchableOpacity onPress={() => setShowAbsenceModal(false)}>
                <Ionicons name="close" size={24} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            {/* Session picker */}
            <Text style={styles.modalLabel}>
              {language === 'pl' ? 'Wybierz zajęcia:' : 'Select session:'}
            </Text>
            <ScrollView style={styles.sessionPicker}>
              {reportableSessions.map((session) => {
                const isSelected = selectedSession?.id === session.id;
                return (
                  <TouchableOpacity
                    key={session.id}
                    style={[
                      styles.sessionOption,
                      isSelected && styles.sessionOptionSelected,
                    ]}
                    onPress={() => setSelectedSession(session)}
                  >
                    <View style={styles.sessionOptionDate}>
                      <Text style={styles.sessionOptionDay}>
                        {safeFormatDate(session.session_date, 'd', dateLocale)}
                      </Text>
                      <Text style={styles.sessionOptionMonth}>
                        {safeFormatDate(session.session_date, 'MMM', dateLocale)}
                      </Text>
                    </View>
                    <View style={styles.sessionOptionInfo}>
                      <Text style={styles.sessionOptionTitle}>{session.class_name}</Text>
                      <Text style={styles.sessionOptionMeta}>
                        {session.child_first_name} {session.child_last_name} • {session.time_start?.substring(0, 5) || '-'}
                      </Text>
                      <Text style={styles.sessionOptionMeta}>
                        {safeFormatDate(session.session_date, 'EEEE', dateLocale)} • {session.facility_name}
                      </Text>
                    </View>
                    {isSelected && (
                      <Ionicons name="checkmark-circle" size={24} color={colors.primary} />
                    )}
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            {/* Reason input */}
            <Text style={styles.modalLabel}>
              {t.absences.reason} ({language === 'pl' ? 'opcjonalnie' : 'optional'}):
            </Text>
            <TextInput
              style={styles.reasonInput}
              placeholder={language === 'pl' ? 'Np. choroba, wyjazd...' : 'E.g. illness, travel...'}
              placeholderTextColor={colors.textTertiary}
              value={absenceReason}
              onChangeText={setAbsenceReason}
              multiline
            />

            {/* Submit button */}
            <TouchableOpacity
              style={[
                styles.submitButton,
                (!selectedSession || isSubmitting) && styles.submitButtonDisabled,
              ]}
              onPress={submitAbsence}
              disabled={!selectedSession || isSubmitting}
            >
              {isSubmitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <Ionicons name="checkmark" size={20} color="#fff" />
                  <Text style={styles.submitButtonText}>{t.absences.reportAbsence}</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const TIMELINE_LEFT = 28;
const LINE_LEFT = 11;

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
      padding: 16,
      paddingBottom: 12,
      backgroundColor: colors.surface,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    headerTitle: {
      fontSize: 22,
      fontWeight: '700',
      color: colors.text,
    },
    headerSubtitle: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 2,
    },
    // Report absence button (top)
    reportButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#ef4444',
      marginHorizontal: 16,
      marginTop: 12,
      marginBottom: 4,
      paddingVertical: 14,
      borderRadius: 12,
      gap: 8,
    },
    reportButtonText: {
      color: '#fff',
      fontSize: 15,
      fontWeight: '600',
    },
    scrollView: {
      flex: 1,
    },
    scrollContent: {
      paddingTop: 12,
      paddingHorizontal: 16,
    },
    // Empty state
    emptyState: {
      alignItems: 'center',
      padding: 48,
      backgroundColor: colors.surface,
      borderRadius: 20,
      marginTop: 20,
    },
    emptyTitle: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
      marginTop: 16,
    },
    emptyText: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    // Timeline
    timeline: {
      position: 'relative',
      paddingLeft: TIMELINE_LEFT,
    },
    timelineLine: {
      position: 'absolute',
      left: LINE_LEFT,
      top: 0,
      bottom: 0,
      width: 2,
      backgroundColor: colors.border,
      borderRadius: 1,
    },
    // Date header
    dateHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 20,
      marginBottom: 8,
    },
    dateDot: {
      position: 'absolute',
      left: -TIMELINE_LEFT + LINE_LEFT - 6,
      width: 14,
      height: 14,
      borderRadius: 7,
      backgroundColor: colors.primary,
      borderWidth: 3,
      borderColor: colors.background,
      zIndex: 1,
    },
    dateDotToday: {
      backgroundColor: colors.warning,
      width: 16,
      height: 16,
      borderRadius: 8,
      left: -TIMELINE_LEFT + LINE_LEFT - 7,
    },
    dateDotTomorrow: {
      backgroundColor: '#3b82f6',
    },
    dateLabelContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      flexWrap: 'wrap',
    },
    dateBadge: {
      paddingHorizontal: 10,
      paddingVertical: 3,
      borderRadius: 12,
    },
    dateBadgeToday: {
      backgroundColor: colors.warning,
    },
    dateBadgeTomorrow: {
      backgroundColor: '#3b82f6',
    },
    dateBadgeText: {
      fontSize: 11,
      fontWeight: '700',
      color: '#fff',
      textTransform: 'uppercase',
      letterSpacing: 0.5,
    },
    dateText: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
      textTransform: 'capitalize',
    },
    dateTextToday: {
      fontWeight: '700',
    },
    // Timeline item
    timelineItem: {
      position: 'relative',
      marginBottom: 12,
    },
    itemDot: {
      position: 'absolute',
      left: -TIMELINE_LEFT + LINE_LEFT - 3,
      top: 20,
      width: 8,
      height: 8,
      borderRadius: 4,
      backgroundColor: colors.border,
      zIndex: 1,
    },
    itemDotAbsent: {
      backgroundColor: colors.textTertiary,
    },
    // Session card
    sessionCard: {
      backgroundColor: colors.surface,
      borderRadius: 14,
      borderLeftWidth: 4,
      overflow: 'hidden',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: isDark ? 0.3 : 0.06,
      shadowRadius: 3,
      elevation: 2,
    },
    sessionCardToday: {
      shadowOpacity: isDark ? 0.4 : 0.1,
      elevation: 3,
    },
    sessionCardAbsent: {
      opacity: 0.55,
    },
    // Card time bar
    cardTimeBar: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: 14,
      paddingVertical: 8,
      backgroundColor: isDark ? colors.surfaceSecondary : colors.background,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
      gap: 6,
    },
    cardTimeText: {
      fontSize: 14,
      fontWeight: '700',
      color: colors.primary,
      flex: 1,
    },
    absentBadge: {
      backgroundColor: colors.textTertiary,
      paddingHorizontal: 10,
      paddingVertical: 3,
      borderRadius: 12,
    },
    absentBadgeText: {
      fontSize: 11,
      fontWeight: '600',
      color: '#fff',
    },
    // Card body
    cardBody: {
      padding: 14,
    },
    cardTitle: {
      fontSize: 16,
      fontWeight: '700',
      color: colors.text,
      marginBottom: 8,
    },
    cardTitleAbsent: {
      textDecorationLine: 'line-through',
      color: colors.textSecondary,
    },
    cardDetails: {
      gap: 5,
    },
    detailRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 6,
    },
    detailText: {
      fontSize: 13,
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
    // Modal
    modalOverlay: {
      flex: 1,
      backgroundColor: 'rgba(0,0,0,0.5)',
      justifyContent: 'flex-end',
    },
    modalContent: {
      backgroundColor: colors.surface,
      borderTopLeftRadius: 24,
      borderTopRightRadius: 24,
      padding: 20,
      maxHeight: '80%',
    },
    modalHeader: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: 20,
    },
    modalTitle: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.text,
    },
    modalLabel: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 8,
    },
    // Session picker
    sessionPicker: {
      maxHeight: 220,
      marginBottom: 16,
    },
    sessionOption: {
      flexDirection: 'row',
      alignItems: 'center',
      padding: 12,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: colors.border,
      marginBottom: 8,
    },
    sessionOptionSelected: {
      borderColor: colors.primary,
      backgroundColor: colors.primaryLight,
    },
    sessionOptionDate: {
      width: 44,
      alignItems: 'center',
      marginRight: 12,
    },
    sessionOptionDay: {
      fontSize: 18,
      fontWeight: '700',
      color: colors.text,
    },
    sessionOptionMonth: {
      fontSize: 11,
      color: colors.textSecondary,
      textTransform: 'uppercase',
    },
    sessionOptionInfo: {
      flex: 1,
    },
    sessionOptionTitle: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    sessionOptionMeta: {
      fontSize: 13,
      color: colors.textSecondary,
      marginTop: 2,
      textTransform: 'capitalize',
    },
    // Reason input
    reasonInput: {
      backgroundColor: isDark ? colors.surfaceSecondary : colors.background,
      borderRadius: 12,
      padding: 14,
      fontSize: 15,
      color: colors.text,
      minHeight: 80,
      textAlignVertical: 'top',
      marginBottom: 20,
    },
    // Submit button
    submitButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#ef4444',
      paddingVertical: 16,
      borderRadius: 12,
      gap: 8,
    },
    submitButtonDisabled: {
      backgroundColor: colors.textTertiary,
    },
    submitButtonText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '600',
    },
  });
