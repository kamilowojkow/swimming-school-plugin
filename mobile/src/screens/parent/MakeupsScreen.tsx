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
  can_makeup: boolean;
  makeup_session?: {
    id: number;
    date: string;
    time: string;
    class_name: string;
  };
  reported_at: string;
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

export default function MakeupsScreen() {
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();
  const dateLocale = language === 'pl' ? pl : enUS;

  const [absences, setAbsences] = useState<Absence[]>([]);
  const [makeupSlots, setMakeupSlots] = useState<MakeupSlot[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Modal states
  const [showMakeupModal, setShowMakeupModal] = useState(false);
  const [selectedAbsence, setSelectedAbsence] = useState<Absence | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

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
      console.log('=== MAKEUPS SCREEN: Fetching data ===');

      const [absencesData, slotsData] = await Promise.all([
        api.getAbsences(),
        api.getMakeupSlots(),
      ]);

      console.log('=== RAW absencesData ===');
      console.log(JSON.stringify(absencesData, null, 2));

      console.log('=== RAW slotsData ===');
      console.log(JSON.stringify(slotsData, null, 2));

      const absencesArray = Array.isArray(absencesData) ? absencesData : (absencesData?.absences || []);
      const slotsArray = Array.isArray(slotsData) ? slotsData : (slotsData?.slots || []);

      console.log('=== Parsed absencesArray ===');
      console.log('Count:', absencesArray.length);
      console.log(JSON.stringify(absencesArray, null, 2));

      console.log('=== Debug info from API ===');
      if (absencesData?._debug) {
        console.log(JSON.stringify(absencesData._debug, null, 2));
      }

      setAbsences(absencesArray);
      setMakeupSlots(slotsArray);
    } catch (error) {
      console.error('=== ERROR fetching makeups data ===');
      console.error(error);
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
        language === 'pl' ? 'Odrabianie zostało zaplanowane' : 'Makeup has been scheduled'
      );
      setShowMakeupModal(false);
      fetchData();
    } catch (error) {
      Alert.alert(
        t.common.error,
        language === 'pl' ? 'Nie udało się zaplanować odrabiania' : 'Failed to schedule makeup'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const cancelAbsence = (absence: Absence) => {
    Alert.alert(
      language === 'pl' ? 'Cofnij zgłoszenie' : 'Cancel absence',
      language === 'pl'
        ? `Czy na pewno chcesz cofnąć zgłoszenie nieobecności dla ${absence.child_name} na zajęcia ${absence.class_name}?`
        : `Are you sure you want to cancel the absence report for ${absence.child_name} for ${absence.class_name}?`,
      [
        {
          text: language === 'pl' ? 'Nie' : 'No',
          style: 'cancel',
        },
        {
          text: language === 'pl' ? 'Tak, cofnij' : 'Yes, cancel',
          style: 'destructive',
          onPress: async () => {
            try {
              await api.cancelAbsence(absence.id);
              Alert.alert(
                language === 'pl' ? 'Sukces' : 'Success',
                language === 'pl' ? 'Zgłoszenie nieobecności zostało cofnięte' : 'Absence report has been cancelled'
              );
              fetchData();
            } catch (error: any) {
              Alert.alert(
                t.common.error,
                error?.response?.data?.message ||
                  (language === 'pl' ? 'Nie udało się cofnąć zgłoszenia' : 'Failed to cancel absence')
              );
            }
          },
        },
      ]
    );
  };

  // Check if absence can be cancelled (future session)
  const canCancelAbsence = (absence: Absence): boolean => {
    if (!absence.session_date) return false;
    const sessionDate = new Date(absence.session_date);
    const now = new Date();
    return sessionDate > now;
  };

  // Absences waiting for makeup scheduling (only those that CAN be made up)
  const pendingMakeups = absences.filter(
    (a) => (a.status === 'reported' || a.status === 'confirmed') && a.can_makeup
  );
  // Absences that cannot be made up (too late, limit exceeded, etc.)
  const expiredAbsences = absences.filter(
    (a) => (a.status === 'reported' || a.status === 'confirmed') && !a.can_makeup
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
          <Ionicons name="time-outline" size={24} color={colors.warning} />
          <Text style={styles.summaryNumber}>{pendingMakeups.length}</Text>
          <Text style={styles.summaryLabel}>
            {language === 'pl' ? 'Do odrobienia' : 'To makeup'}
          </Text>
        </View>
        <View style={[styles.summaryCard, { backgroundColor: '#f3e8ff' }]}>
          <Ionicons name="calendar-outline" size={24} color="#8b5cf6" />
          <Text style={styles.summaryNumber}>{scheduledMakeups.length}</Text>
          <Text style={styles.summaryLabel}>
            {language === 'pl' ? 'Zaplanowane' : 'Scheduled'}
          </Text>
        </View>
        <View style={[styles.summaryCard, { backgroundColor: colors.errorLight || '#fee2e2' }]}>
          <Ionicons name="close-circle" size={24} color={colors.error} />
          <Text style={styles.summaryNumber}>{expiredAbsences.length}</Text>
          <Text style={styles.summaryLabel}>
            {language === 'pl' ? 'Bez odrobienia' : 'No makeup'}
          </Text>
        </View>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
        }
      >
        {/* Pending Makeups - needs scheduling */}
        {pendingMakeups.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>
              {language === 'pl' ? 'Wymagają zaplanowania' : 'Needs scheduling'}
            </Text>

            {pendingMakeups.map((absence) => (
              <View key={absence.id} style={styles.pendingCard}>
                <View style={styles.pendingInfo}>
                  <Text style={styles.pendingClass}>{absence.class_name}</Text>
                  <Text style={styles.pendingDate}>
                    {language === 'pl' ? 'Nieobecność:' : 'Absent:'}{' '}
                    {safeFormatDate(absence.session_date, 'd MMMM yyyy')}
                  </Text>
                  <Text style={styles.pendingChild}>{absence.child_name}</Text>
                </View>
                <View style={styles.pendingActions}>
                  <TouchableOpacity
                    style={styles.scheduleButton}
                    onPress={() => openMakeupModal(absence)}
                  >
                    <Ionicons name="add" size={20} color="#fff" />
                    <Text style={styles.scheduleButtonText}>
                      {language === 'pl' ? 'Zaplanuj' : 'Schedule'}
                    </Text>
                  </TouchableOpacity>
                  {canCancelAbsence(absence) && (
                    <TouchableOpacity
                      style={styles.cancelButton}
                      onPress={() => cancelAbsence(absence)}
                    >
                      <Ionicons name="close" size={18} color={colors.error} />
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            ))}
          </View>
        )}

        {/* Expired Absences - cannot be made up */}
        {expiredAbsences.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>
              {language === 'pl' ? 'Bez możliwości odrobienia' : 'Cannot be made up'}
            </Text>
            <Text style={styles.sectionSubtitle}>
              {language === 'pl'
                ? 'Zgłoszone po terminie (min. 24h przed) lub przekroczony limit odrabiań'
                : 'Reported too late (min. 24h before) or makeup limit exceeded'}
            </Text>

            {expiredAbsences.map((absence) => (
              <View key={absence.id} style={styles.expiredCard}>
                <View style={styles.expiredIcon}>
                  <Ionicons name="close-circle" size={20} color={colors.error} />
                </View>
                <View style={styles.expiredInfo}>
                  <Text style={styles.expiredClass}>{absence.class_name}</Text>
                  <Text style={styles.expiredDate}>
                    {safeFormatDate(absence.session_date, 'd MMMM yyyy')}
                  </Text>
                  <Text style={styles.expiredChild}>{absence.child_name}</Text>
                </View>
                {canCancelAbsence(absence) && (
                  <TouchableOpacity
                    style={styles.cancelButtonSmall}
                    onPress={() => cancelAbsence(absence)}
                  >
                    <Ionicons name="arrow-undo" size={16} color={colors.textSecondary} />
                  </TouchableOpacity>
                )}
              </View>
            ))}
          </View>
        )}

        {/* Scheduled Makeups */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {language === 'pl' ? 'Zaplanowane odrabianie' : 'Scheduled makeups'}
          </Text>

          {scheduledMakeups.length === 0 ? (
            <View style={styles.emptyCard}>
              <Ionicons name="calendar-outline" size={40} color={colors.textTertiary} />
              <Text style={styles.emptyText}>
                {language === 'pl' ? 'Brak zaplanowanych odrabiań' : 'No scheduled makeups'}
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
              </View>
            ))
          )}
        </View>

        {/* Completed Makeups */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            {language === 'pl' ? 'Historia odrabiań' : 'Makeup history'}
          </Text>

          {completedMakeups.length === 0 ? (
            <View style={styles.emptyCard}>
              <Ionicons name="checkmark-circle-outline" size={40} color={colors.textTertiary} />
              <Text style={styles.emptyText}>
                {language === 'pl' ? 'Brak historii odrabiań' : 'No makeup history'}
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
                    {absence.child_name} • {safeFormatDate(absence.session_date, 'd MMM')}
                  </Text>
                </View>
              </View>
            ))
          )}
        </View>

        <View style={{ height: 24 }} />
      </ScrollView>

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
                    {language === 'pl' ? 'Brak dostępnych terminów' : 'No available slots'}
                  </Text>
                </View>
              ) : (
                makeupSlots.map((slot) => (
                  <TouchableOpacity
                    key={slot.id}
                    style={styles.slotCard}
                    onPress={() => scheduleMakeup(slot.id)}
                    disabled={isSubmitting}
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
      textAlign: 'center',
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
      marginBottom: 12,
    },
    emptyCard: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      padding: 32,
      alignItems: 'center',
    },
    emptyText: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 8,
    },
    pendingCard: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      padding: 16,
      marginBottom: 12,
      borderLeftWidth: 4,
      borderLeftColor: colors.warning,
    },
    pendingInfo: {
      marginBottom: 12,
    },
    pendingClass: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    pendingDate: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    pendingChild: {
      fontSize: 13,
      color: colors.textTertiary,
      marginTop: 2,
    },
    scheduleButton: {
      flex: 1,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: '#8b5cf6',
      borderRadius: 12,
      paddingVertical: 12,
      gap: 8,
    },
    scheduleButtonText: {
      fontSize: 14,
      fontWeight: '600',
      color: '#fff',
    },
    pendingActions: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
    },
    cancelButton: {
      width: 40,
      height: 40,
      borderRadius: 20,
      backgroundColor: colors.errorLight || '#fee2e2',
      justifyContent: 'center',
      alignItems: 'center',
    },
    cancelButtonSmall: {
      width: 32,
      height: 32,
      borderRadius: 16,
      backgroundColor: colors.surfaceSecondary,
      justifyContent: 'center',
      alignItems: 'center',
      marginLeft: 'auto',
    },
    expiredCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 12,
      marginBottom: 8,
      opacity: 0.7,
    },
    expiredIcon: {
      marginRight: 12,
    },
    expiredInfo: {
      flex: 1,
    },
    expiredClass: {
      fontSize: 14,
      fontWeight: '500',
      color: colors.text,
    },
    expiredDate: {
      fontSize: 12,
      color: colors.textSecondary,
      marginTop: 2,
    },
    expiredChild: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
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
