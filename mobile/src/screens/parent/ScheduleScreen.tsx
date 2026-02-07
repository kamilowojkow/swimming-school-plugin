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
import { format, isToday, isTomorrow, isSameDay } from 'date-fns';
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

  const openAbsenceModal = (session: Session) => {
    setSelectedSession(session);
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

  const getDateLabel = (dateStr: string): string => {
    const date = new Date(dateStr);
    if (isToday(date)) return language === 'pl' ? 'Dzisiaj' : 'Today';
    if (isTomorrow(date)) return language === 'pl' ? 'Jutro' : 'Tomorrow';
    return format(date, 'EEEE, d MMMM', { locale: dateLocale });
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

            {groupedSessions.map((group, groupIndex) => {
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
                    const canReport = canReportAbsence(session);
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

                          {/* Action footer */}
                          {!absent && canReport && (
                            <TouchableOpacity
                              style={styles.absenceButton}
                              onPress={() => openAbsenceModal(session)}
                            >
                              <Ionicons name="close-circle-outline" size={18} color="#fff" />
                              <Text style={styles.absenceButtonText}>
                                {t.absences.reportAbsence}
                              </Text>
                            </TouchableOpacity>
                          )}
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
      <Modal visible={showAbsenceModal} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{t.absences.reportAbsence}</Text>
              <TouchableOpacity onPress={() => setShowAbsenceModal(false)}>
                <Ionicons name="close" size={24} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            {selectedSession && (
              <View style={styles.modalSession}>
                <View style={styles.modalSessionDate}>
                  <Text style={styles.modalSessionDay}>
                    {(() => { try { return format(new Date(selectedSession.session_date), 'd'); } catch { return '-'; } })()}
                  </Text>
                  <Text style={styles.modalSessionMonth}>
                    {(() => { try { return format(new Date(selectedSession.session_date), 'MMM', { locale: dateLocale }); } catch { return '-'; } })()}
                  </Text>
                </View>
                <View style={styles.modalSessionInfo}>
                  <Text style={styles.modalSessionClass}>{selectedSession.class_name}</Text>
                  <Text style={styles.modalSessionMeta}>
                    {selectedSession.child_first_name} {selectedSession.child_last_name}
                  </Text>
                  <Text style={styles.modalSessionMeta}>
                    {selectedSession.time_start?.substring(0, 5) || '-'} - {selectedSession.time_end?.substring(0, 5) || '-'}
                  </Text>
                </View>
              </View>
            )}

            <Text style={styles.inputLabel}>
              {t.absences.reason} ({language === 'pl' ? 'opcjonalnie' : 'optional'})
            </Text>
            <TextInput
              style={styles.textInput}
              placeholder={language === 'pl' ? 'Np. choroba, wyjazd...' : 'E.g. illness, travel...'}
              placeholderTextColor={colors.textTertiary}
              value={absenceReason}
              onChangeText={setAbsenceReason}
              multiline
              numberOfLines={3}
            />

            <View style={styles.modalWarning}>
              <Ionicons name="information-circle-outline" size={18} color={colors.warning} />
              <Text style={styles.modalWarningText}>
                {language === 'pl'
                  ? 'Zgłoszona nieobecność będzie mogła być odrobiona na innych zajęciach.'
                  : 'Reported absence can be made up in other sessions.'}
              </Text>
            </View>

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.modalCancelButton}
                onPress={() => setShowAbsenceModal(false)}
              >
                <Text style={styles.modalCancelText}>{t.common.cancel}</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalSubmitButton, isSubmitting && styles.modalSubmitDisabled]}
                onPress={submitAbsence}
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <>
                    <Ionicons name="checkmark" size={18} color="#fff" />
                    <Text style={styles.modalSubmitText}>{t.absences.reportAbsence}</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
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
    scrollView: {
      flex: 1,
    },
    scrollContent: {
      paddingTop: 16,
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
    // Absence button
    absenceButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#ef4444',
      paddingVertical: 10,
      gap: 6,
    },
    absenceButtonText: {
      fontSize: 13,
      fontWeight: '600',
      color: '#fff',
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
    modalSession: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: isDark ? colors.surfaceSecondary : colors.background,
      borderRadius: 14,
      padding: 14,
      marginBottom: 20,
    },
    modalSessionDate: {
      width: 52,
      height: 52,
      backgroundColor: colors.primaryLight,
      borderRadius: 12,
      justifyContent: 'center',
      alignItems: 'center',
      marginRight: 14,
    },
    modalSessionDay: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.primary,
    },
    modalSessionMonth: {
      fontSize: 11,
      color: colors.primary,
      textTransform: 'uppercase',
    },
    modalSessionInfo: {
      flex: 1,
    },
    modalSessionClass: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    modalSessionMeta: {
      fontSize: 13,
      color: colors.textSecondary,
      marginTop: 2,
    },
    inputLabel: {
      fontSize: 14,
      fontWeight: '500',
      color: colors.text,
      marginBottom: 8,
    },
    textInput: {
      backgroundColor: isDark ? colors.surfaceSecondary : colors.background,
      borderRadius: 12,
      padding: 14,
      fontSize: 15,
      color: colors.text,
      borderWidth: 1,
      borderColor: colors.border,
      minHeight: 80,
      textAlignVertical: 'top',
      marginBottom: 16,
    },
    modalWarning: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      backgroundColor: colors.warningLight,
      borderRadius: 12,
      padding: 12,
      marginBottom: 20,
      gap: 8,
    },
    modalWarningText: {
      fontSize: 13,
      color: isDark ? colors.warning : '#92400e',
      flex: 1,
      lineHeight: 18,
    },
    modalActions: {
      flexDirection: 'row',
      gap: 12,
    },
    modalCancelButton: {
      flex: 1,
      paddingVertical: 14,
      alignItems: 'center',
      borderRadius: 14,
      backgroundColor: isDark ? colors.surfaceSecondary : colors.background,
      borderWidth: 1,
      borderColor: colors.border,
    },
    modalCancelText: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.textSecondary,
    },
    modalSubmitButton: {
      flex: 2,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: 14,
      borderRadius: 14,
      backgroundColor: '#ef4444',
      gap: 6,
    },
    modalSubmitDisabled: {
      opacity: 0.6,
    },
    modalSubmitText: {
      fontSize: 15,
      fontWeight: '600',
      color: '#fff',
    },
  });
