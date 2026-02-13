import React, { useEffect, useState } from 'react';
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
import { format } from 'date-fns';
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

interface Absence {
  id: number;
  child_id: number;
  child_name: string;
  session_id: number;
  session_date: string;
  time_start: string;
  class_name: string;
  status: 'reported' | 'confirmed' | 'makeup_scheduled' | 'makeup_completed';
  reason?: string;
  makeup_session?: {
    id: number;
    date: string;
    time: string;
    class_name: string;
  };
  reported_at: string;
}

interface UpcomingSession {
  id: number;
  session_date: string;
  time_start: string;
  class_name: string;
  child_id: number;
  child_name: string;
  can_report_absence: boolean;
}

interface MakeupSlot {
  id: number;
  date: string;
  time_start: string;
  time_end: string;
  class_name: string;
  facility_name: string;
  available_spots: number;
}

export default function AbsencesScreen() {
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();
  const dateLocale = language === 'pl' ? pl : enUS;

  const [absences, setAbsences] = useState<Absence[]>([]);
  const [upcomingSessions, setUpcomingSessions] = useState<UpcomingSession[]>([]);
  const [makeupSlots, setMakeupSlots] = useState<MakeupSlot[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<'absences' | 'makeups'>('absences');

  // Modal states
  const [showReportModal, setShowReportModal] = useState(false);
  const [showMakeupModal, setShowMakeupModal] = useState(false);
  const [selectedSession, setSelectedSession] = useState<UpcomingSession | null>(null);
  const [selectedAbsence, setSelectedAbsence] = useState<Absence | null>(null);
  const [absenceReason, setAbsenceReason] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string; icon: string }> = {
    reported: { label: language === 'pl' ? 'Zgloszone' : 'Reported', color: colors.warning, bg: colors.warningLight, icon: 'time' },
    confirmed: { label: t.absences.confirmed, color: colors.primary, bg: colors.primaryLight, icon: 'checkmark' },
    makeup_scheduled: { label: language === 'pl' ? 'Odrabianie zaplanowane' : 'Makeup scheduled', color: '#8b5cf6', bg: '#f3e8ff', icon: 'calendar' },
    makeup_completed: { label: language === 'pl' ? 'Odrobione' : 'Completed', color: colors.success, bg: colors.successLight, icon: 'checkmark-circle' },
  };

  // Safe date formatting helper
  const safeFormatDate = (dateStr: string | undefined, formatStr: string): string => {
    if (!dateStr) return '-';
    try {
      const date = new Date(dateStr);
      if (isNaN(date.getTime())) return '-';
      return format(date, formatStr, { locale: dateLocale });
    } catch {
      return '-';
    }
  };

  const fetchData = async () => {
    try {
      const [absencesData, sessionsData, slotsData] = await Promise.all([
        api.getAbsences(),
        api.getUpcomingSessions(),
        api.getMakeupSlots(),
      ]);

      // Handle both array and object API responses
      const absencesArray = Array.isArray(absencesData) ? absencesData : (absencesData?.absences || []);
      const sessionsArray = Array.isArray(sessionsData) ? sessionsData : (sessionsData?.sessions || []);
      const slotsArray = Array.isArray(slotsData) ? slotsData : (slotsData?.slots || []);

      setAbsences(absencesArray);
      setUpcomingSessions(sessionsArray);
      setMakeupSlots(slotsArray);
    } catch (error) {
      console.error('Error fetching data:', error);
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

  const openReportModal = (session: UpcomingSession) => {
    setSelectedSession(session);
    setAbsenceReason('');
    setShowReportModal(true);
  };

  const submitAbsence = async () => {
    if (!selectedSession) return;

    setIsSubmitting(true);
    try {
      await api.reportAbsence(selectedSession.id, selectedSession.child_id, absenceReason);
      Alert.alert(
        language === 'pl' ? 'Sukces' : 'Success',
        language === 'pl' ? 'Nieobecnosc zostala zgloszona' : 'Absence has been reported'
      );
      setShowReportModal(false);
      fetchData();
    } catch (error) {
      Alert.alert(
        t.common.error,
        language === 'pl' ? 'Nie udalo sie zglosic nieobecnosci' : 'Failed to report absence'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const openMakeupModal = (absence: Absence) => {
    setSelectedAbsence(absence);
    setShowMakeupModal(true);
  };

  const scheduleMakeup = async (slotId: number) => {
    if (!selectedAbsence) return;

    setIsSubmitting(true);
    try {
      await api.scheduleMakeup(selectedAbsence.id, slotId);
      Alert.alert(
        language === 'pl' ? 'Sukces' : 'Success',
        language === 'pl' ? 'Odrabianie zostalo zaplanowane' : 'Makeup has been scheduled'
      );
      setShowMakeupModal(false);
      fetchData();
    } catch (error) {
      Alert.alert(
        t.common.error,
        language === 'pl' ? 'Nie udalo sie zaplanowac odrabiania' : 'Failed to schedule makeup'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const pendingAbsences = absences.filter(
    (a) => a.status === 'reported' || a.status === 'confirmed'
  );
  const scheduledMakeups = absences.filter((a) => a.status === 'makeup_scheduled');
  const completedMakeups = absences.filter((a) => a.status === 'makeup_completed');

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
      {/* Summary Cards */}
      <View style={styles.summaryContainer}>
        <View style={[styles.summaryCard, { backgroundColor: colors.warningLight }]}>
          <Ionicons name="calendar-outline" size={24} color={colors.warning} />
          <Text style={styles.summaryNumber}>{pendingAbsences.length}</Text>
          <Text style={styles.summaryLabel}>{t.absences.title}</Text>
        </View>
        <View style={[styles.summaryCard, { backgroundColor: '#f3e8ff' }]}>
          <Ionicons name="refresh" size={24} color="#8b5cf6" />
          <Text style={styles.summaryNumber}>{scheduledMakeups.length}</Text>
          <Text style={styles.summaryLabel}>
            {language === 'pl' ? 'Do odrobienia' : 'To makeup'}
          </Text>
        </View>
        <View style={[styles.summaryCard, { backgroundColor: colors.successLight }]}>
          <Ionicons name="checkmark-circle" size={24} color={colors.success} />
          <Text style={styles.summaryNumber}>{completedMakeups.length}</Text>
          <Text style={styles.summaryLabel}>
            {language === 'pl' ? 'Odrobione' : 'Completed'}
          </Text>
        </View>
      </View>

      {/* Tabs */}
      <View style={styles.tabsContainer}>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'absences' && styles.tabActive]}
          onPress={() => setActiveTab('absences')}
        >
          <Text style={[styles.tabText, activeTab === 'absences' && styles.tabTextActive]}>
            {t.absences.title}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'makeups' && styles.tabActive]}
          onPress={() => setActiveTab('makeups')}
        >
          <Text style={[styles.tabText, activeTab === 'makeups' && styles.tabTextActive]}>
            {language === 'pl' ? 'Odrabianie' : 'Makeups'}
          </Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
        }
      >
        {activeTab === 'absences' ? (
          <>
            {/* Report Absence Section */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>{t.absences.reportAbsence}</Text>
              <Text style={styles.sectionSubtitle}>
                {language === 'pl'
                  ? 'Wybierz zajecia, na ktorych Twoje dziecko bedzie nieobecne'
                  : 'Select sessions your child will be absent from'}
              </Text>

              {upcomingSessions.filter((s) => s.can_report_absence).length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    {language === 'pl' ? 'Brak nadchodzacych zajec do zgloszenia' : 'No upcoming sessions to report'}
                  </Text>
                </View>
              ) : (
                upcomingSessions
                  .filter((s) => s.can_report_absence)
                  .slice(0, 5)
                  .map((session) => (
                    <TouchableOpacity
                      key={session.id}
                      style={styles.sessionCard}
                      onPress={() => openReportModal(session)}
                    >
                      <View style={styles.sessionDate}>
                        <Text style={styles.sessionDay}>
                          {safeFormatDate(session.session_date, 'd')}
                        </Text>
                        <Text style={styles.sessionMonth}>
                          {safeFormatDate(session.session_date, 'MMM')}
                        </Text>
                      </View>
                      <View style={styles.sessionInfo}>
                        <Text style={styles.sessionClass}>{session.class_name}</Text>
                        <Text style={styles.sessionMeta}>
                          {session.child_name} • {session.time_start?.substring(0, 5) || '-'}
                        </Text>
                      </View>
                      <View style={styles.reportButton}>
                        <Ionicons name="add" size={20} color={colors.primary} />
                      </View>
                    </TouchableOpacity>
                  ))
              )}
            </View>

            {/* Reported Absences */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {language === 'pl' ? 'Zgloszone nieobecnosci' : 'Reported absences'}
              </Text>

              {pendingAbsences.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Ionicons name="checkmark-circle" size={40} color={colors.success} />
                  <Text style={styles.emptyTitle}>{t.absences.noAbsences}</Text>
                  <Text style={styles.emptyText}>
                    {language === 'pl' ? 'Wszystkie zajecia odrobione' : 'All sessions completed'}
                  </Text>
                </View>
              ) : (
                pendingAbsences.map((absence) => {
                  const statusConfig = STATUS_CONFIG[absence.status];
                  return (
                    <View key={absence.id} style={styles.absenceCard}>
                      <View style={styles.absenceHeader}>
                        <View>
                          <Text style={styles.absenceClass}>{absence.class_name}</Text>
                          <Text style={styles.absenceDate}>
                            {safeFormatDate(absence.session_date, 'd MMMM yyyy')} {language === 'pl' ? 'o' : 'at'}{' '}
                            {absence.time_start?.substring(0, 5) || '-'}
                          </Text>
                          <Text style={styles.absenceChild}>{absence.child_name}</Text>
                        </View>
                        <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                          <Ionicons
                            name={statusConfig.icon as any}
                            size={12}
                            color={statusConfig.color}
                          />
                          <Text style={[styles.statusText, { color: statusConfig.color }]}>
                            {statusConfig.label}
                          </Text>
                        </View>
                      </View>

                      {absence.reason && (
                        <View style={styles.reasonContainer}>
                          <Text style={styles.reasonLabel}>{t.absences.reason}:</Text>
                          <Text style={styles.reasonText}>{absence.reason}</Text>
                        </View>
                      )}

                      {absence.status === 'confirmed' && (
                        <TouchableOpacity
                          style={styles.scheduleButton}
                          onPress={() => openMakeupModal(absence)}
                        >
                          <Ionicons name="calendar" size={18} color="#fff" />
                          <Text style={styles.scheduleButtonText}>{t.absences.scheduleMakeup}</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  );
                })
              )}
            </View>
          </>
        ) : (
          // Makeups Tab
          <>
            {/* Scheduled Makeups */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {language === 'pl' ? 'Zaplanowane odrabianie' : 'Scheduled makeups'}
              </Text>

              {scheduledMakeups.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    {language === 'pl' ? 'Brak zaplanowanych odrabien' : 'No scheduled makeups'}
                  </Text>
                </View>
              ) : (
                scheduledMakeups.map((absence) => (
                  <View key={absence.id} style={styles.makeupCard}>
                    <View style={styles.makeupIcon}>
                      <Ionicons name="refresh" size={24} color="#8b5cf6" />
                    </View>
                    <View style={styles.makeupInfo}>
                      <Text style={styles.makeupClass}>
                        {absence.makeup_session?.class_name || absence.class_name}
                      </Text>
                      {absence.makeup_session && (
                        <Text style={styles.makeupDate}>
                          {safeFormatDate(absence.makeup_session.date, 'd MMMM')} {language === 'pl' ? 'o' : 'at'}{' '}
                          {absence.makeup_session.time}
                        </Text>
                      )}
                      <Text style={styles.makeupChild}>{absence.child_name}</Text>
                    </View>
                    <View style={styles.makeupArrow}>
                      <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
                    </View>
                  </View>
                ))
              )}
            </View>

            {/* Completed Makeups */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>
                {language === 'pl' ? 'Historia odrabien' : 'Makeup history'}
              </Text>

              {completedMakeups.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    {language === 'pl' ? 'Brak historii odrabien' : 'No makeup history'}
                  </Text>
                </View>
              ) : (
                completedMakeups.slice(0, 10).map((absence) => (
                  <View key={absence.id} style={styles.completedCard}>
                    <View style={styles.completedIcon}>
                      <Ionicons name="checkmark-circle" size={20} color={colors.success} />
                    </View>
                    <View style={styles.completedInfo}>
                      <Text style={styles.completedClass}>{absence.class_name}</Text>
                      <Text style={styles.completedMeta}>
                        {absence.child_name} •{' '}
                        {safeFormatDate(absence.session_date, 'd MMM')}
                      </Text>
                    </View>
                  </View>
                ))
              )}
            </View>
          </>
        )}

        <View style={{ height: 24 }} />
      </ScrollView>

      {/* Report Absence Modal */}
      <Modal visible={showReportModal} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{t.absences.reportAbsence}</Text>
              <TouchableOpacity onPress={() => setShowReportModal(false)}>
                <Ionicons name="close" size={24} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            {selectedSession && (
              <View style={styles.modalSession}>
                <View style={styles.modalSessionDate}>
                  <Text style={styles.modalSessionDay}>
                    {safeFormatDate(selectedSession.session_date, 'd')}
                  </Text>
                  <Text style={styles.modalSessionMonth}>
                    {safeFormatDate(selectedSession.session_date, 'MMM')}
                  </Text>
                </View>
                <View>
                  <Text style={styles.modalSessionClass}>{selectedSession.class_name}</Text>
                  <Text style={styles.modalSessionMeta}>
                    {selectedSession.child_name} • {selectedSession.time_start?.substring(0, 5) || '-'}
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

            <TouchableOpacity
              style={[styles.submitButton, isSubmitting && styles.submitButtonDisabled]}
              onPress={submitAbsence}
              disabled={isSubmitting}
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

      {/* Schedule Makeup Modal */}
      <Modal visible={showMakeupModal} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {language === 'pl' ? 'Wybierz termin odrabiania' : 'Choose makeup time'}
              </Text>
              <TouchableOpacity onPress={() => setShowMakeupModal(false)}>
                <Ionicons name="close" size={24} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.slotsContainer}>
              {makeupSlots.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    {language === 'pl' ? 'Brak dostepnych terminow' : 'No available slots'}
                  </Text>
                </View>
              ) : (
                makeupSlots.map((slot) => (
                  <TouchableOpacity
                    key={slot.id}
                    style={styles.slotCard}
                    onPress={() => scheduleMakeup(slot.id)}
                  >
                    <View style={styles.slotDate}>
                      <Text style={styles.slotDay}>
                        {safeFormatDate(slot.date, 'd')}
                      </Text>
                      <Text style={styles.slotMonth}>
                        {safeFormatDate(slot.date, 'MMM')}
                      </Text>
                    </View>
                    <View style={styles.slotInfo}>
                      <Text style={styles.slotClass}>{slot.class_name}</Text>
                      <Text style={styles.slotTime}>
                        {slot.time_start?.substring(0, 5) || '-'} - {slot.time_end?.substring(0, 5) || '-'}
                      </Text>
                      <Text style={styles.slotFacility}>{slot.facility_name}</Text>
                    </View>
                    <View style={styles.slotSpots}>
                      <Text style={styles.slotSpotsNumber}>{slot.available_spots}</Text>
                      <Text style={styles.slotSpotsLabel}>
                        {language === 'pl' ? 'miejsc' : 'spots'}
                      </Text>
                    </View>
                  </TouchableOpacity>
                ))
              )}
            </ScrollView>
          </View>
        </View>
      </Modal>
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
    summaryContainer: {
      flexDirection: 'row',
      padding: 16,
      gap: 12,
    },
    summaryCard: {
      flex: 1,
      borderRadius: 16,
      padding: 16,
      alignItems: 'center',
    },
    summaryNumber: {
      fontSize: 28,
      fontWeight: '700',
      color: colors.text,
      marginTop: 8,
    },
    summaryLabel: {
      fontSize: 11,
      color: colors.textSecondary,
      marginTop: 2,
    },
    tabsContainer: {
      flexDirection: 'row',
      marginHorizontal: 16,
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 4,
    },
    tab: {
      flex: 1,
      paddingVertical: 12,
      alignItems: 'center',
      borderRadius: 10,
    },
    tabActive: {
      backgroundColor: colors.primary,
    },
    tabText: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.textSecondary,
    },
    tabTextActive: {
      color: '#fff',
    },
    content: {
      flex: 1,
    },
    section: {
      padding: 16,
    },
    sectionTitle: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 4,
    },
    sectionSubtitle: {
      fontSize: 13,
      color: colors.textSecondary,
      marginBottom: 16,
    },
    emptyCard: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      padding: 24,
      alignItems: 'center',
    },
    emptyTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
      marginTop: 12,
    },
    emptyText: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    sessionCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 14,
      padding: 14,
      marginBottom: 10,
    },
    sessionDate: {
      width: 48,
      height: 48,
      backgroundColor: colors.primaryLight,
      borderRadius: 12,
      justifyContent: 'center',
      alignItems: 'center',
    },
    sessionDay: {
      fontSize: 18,
      fontWeight: '700',
      color: colors.primary,
    },
    sessionMonth: {
      fontSize: 11,
      color: colors.primary,
      textTransform: 'uppercase',
    },
    sessionInfo: {
      flex: 1,
      marginLeft: 12,
    },
    sessionClass: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    sessionMeta: {
      fontSize: 13,
      color: colors.textSecondary,
      marginTop: 2,
    },
    reportButton: {
      width: 36,
      height: 36,
      borderRadius: 18,
      backgroundColor: colors.primaryLight,
      justifyContent: 'center',
      alignItems: 'center',
    },
    absenceCard: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      padding: 16,
      marginBottom: 12,
    },
    absenceHeader: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
    },
    absenceClass: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    absenceDate: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    absenceChild: {
      fontSize: 13,
      color: colors.textTertiary,
      marginTop: 2,
    },
    statusBadge: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: 10,
      paddingVertical: 5,
      borderRadius: 20,
      gap: 4,
    },
    statusText: {
      fontSize: 11,
      fontWeight: '600',
    },
    reasonContainer: {
      marginTop: 12,
      paddingTop: 12,
      borderTopWidth: 1,
      borderTopColor: colors.border,
    },
    reasonLabel: {
      fontSize: 12,
      color: colors.textSecondary,
    },
    reasonText: {
      fontSize: 14,
      color: colors.text,
      marginTop: 2,
    },
    scheduleButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#8b5cf6',
      borderRadius: 12,
      paddingVertical: 12,
      marginTop: 16,
      gap: 8,
    },
    scheduleButtonText: {
      fontSize: 14,
      fontWeight: '600',
      color: '#fff',
    },
    makeupCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 14,
      padding: 14,
      marginBottom: 10,
    },
    makeupIcon: {
      width: 48,
      height: 48,
      backgroundColor: '#f3e8ff',
      borderRadius: 12,
      justifyContent: 'center',
      alignItems: 'center',
    },
    makeupInfo: {
      flex: 1,
      marginLeft: 12,
    },
    makeupClass: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    makeupDate: {
      fontSize: 13,
      color: colors.textSecondary,
      marginTop: 2,
    },
    makeupChild: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
    },
    makeupArrow: {},
    completedCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 12,
      marginBottom: 8,
    },
    completedIcon: {
      marginRight: 12,
    },
    completedInfo: {
      flex: 1,
    },
    completedClass: {
      fontSize: 14,
      fontWeight: '500',
      color: colors.text,
    },
    completedMeta: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
    },
    // Modal styles
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
    modalSession: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surfaceSecondary,
      borderRadius: 14,
      padding: 14,
      marginBottom: 20,
    },
    modalSessionDate: {
      width: 56,
      height: 56,
      backgroundColor: colors.primaryLight,
      borderRadius: 12,
      justifyContent: 'center',
      alignItems: 'center',
      marginRight: 14,
    },
    modalSessionDay: {
      fontSize: 22,
      fontWeight: '700',
      color: colors.primary,
    },
    modalSessionMonth: {
      fontSize: 12,
      color: colors.primary,
      textTransform: 'uppercase',
    },
    modalSessionClass: {
      fontSize: 17,
      fontWeight: '600',
      color: colors.text,
    },
    modalSessionMeta: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    inputLabel: {
      fontSize: 14,
      fontWeight: '500',
      color: colors.text,
      marginBottom: 8,
    },
    textInput: {
      backgroundColor: colors.surfaceSecondary,
      borderRadius: 12,
      padding: 14,
      fontSize: 15,
      color: colors.text,
      borderWidth: 1,
      borderColor: colors.border,
      minHeight: 100,
      textAlignVertical: 'top',
      marginBottom: 20,
    },
    submitButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.primary,
      borderRadius: 14,
      paddingVertical: 16,
      gap: 8,
    },
    submitButtonDisabled: {
      opacity: 0.6,
    },
    submitButtonText: {
      fontSize: 16,
      fontWeight: '600',
      color: '#fff',
    },
    slotsContainer: {
      maxHeight: 400,
    },
    slotCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surfaceSecondary,
      borderRadius: 14,
      padding: 14,
      marginBottom: 10,
    },
    slotDate: {
      width: 52,
      height: 52,
      backgroundColor: colors.surface,
      borderRadius: 12,
      justifyContent: 'center',
      alignItems: 'center',
      borderWidth: 1,
      borderColor: colors.border,
    },
    slotDay: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.text,
    },
    slotMonth: {
      fontSize: 11,
      color: colors.textSecondary,
      textTransform: 'uppercase',
    },
    slotInfo: {
      flex: 1,
      marginLeft: 12,
    },
    slotClass: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    slotTime: {
      fontSize: 13,
      color: colors.textSecondary,
      marginTop: 2,
    },
    slotFacility: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
    },
    slotSpots: {
      alignItems: 'center',
      backgroundColor: colors.successLight,
      paddingHorizontal: 12,
      paddingVertical: 8,
      borderRadius: 10,
    },
    slotSpotsNumber: {
      fontSize: 18,
      fontWeight: '700',
      color: colors.success,
    },
    slotSpotsLabel: {
      fontSize: 10,
      color: colors.success,
    },
  });
