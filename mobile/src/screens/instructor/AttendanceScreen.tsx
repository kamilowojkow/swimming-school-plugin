import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  TextInput,
} from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { format } from 'date-fns';
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

type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused' | 'unmarked';

interface Participant {
  enrollment_id: number;
  child_id: number;
  first_name: string;
  last_name: string;
  status: AttendanceStatus;
  notes: string;
  swimming_level?: string;
  medical_notes?: string;
}

interface SessionDetails {
  id: number;
  session_date: string;
  time_start: string;
  time_end: string;
  class_name: string;
  level: string;
  facility_name: string;
  facility_address: string;
  max_participants: number;
  description: string;
  participants: Participant[];
}

export default function AttendanceScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { sessionId } = route.params;

  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');

  const [session, setSession] = useState<SessionDetails | null>(null);
  const [attendance, setAttendance] = useState<Map<number, { status: AttendanceStatus; notes: string }>>(new Map());
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [expandedChild, setExpandedChild] = useState<number | null>(null);
  const [hasChanges, setHasChanges] = useState(false);

  const dateLocale = language === 'pl' ? pl : enUS;
  const styles = createStyles(colors);

  const STATUS_CONFIG: Record<AttendanceStatus, { label: string; color: string; bg: string; icon: string }> = {
    present: { label: t.attendance.present, color: colors.secondary, bg: colors.secondaryLight, icon: 'checkmark-circle' },
    absent: { label: t.attendance.absent, color: colors.error, bg: colors.errorLight, icon: 'close-circle' },
    late: { label: t.attendance.late, color: colors.warning, bg: colors.warningLight, icon: 'time' },
    excused: { label: t.attendance.excused, color: colors.primary, bg: colors.primaryLight, icon: 'document-text' },
    unmarked: { label: t.attendance.unmarked, color: colors.textSecondary, bg: colors.surfaceSecondary, icon: 'help-circle' },
  };

  useEffect(() => {
    fetchSessionDetails();
  }, [sessionId]);

  const fetchSessionDetails = async () => {
    try {
      const data = await api.getSessionDetails(sessionId);
      // Ensure participants is an array
      if (data && !Array.isArray(data.participants)) {
        data.participants = [];
      }
      setSession(data);

      // Initialize attendance map
      const attendanceMap = new Map<number, { status: AttendanceStatus; notes: string }>();
      (data.participants || []).forEach((p: Participant) => {
        attendanceMap.set(p.enrollment_id, {
          status: p.status || 'unmarked',
          notes: p.notes || '',
        });
      });
      setAttendance(attendanceMap);
    } catch (error) {
      console.error('Error fetching session:', error);
      Alert.alert(t.common.error, language === 'pl' ? 'Nie udało się pobrać danych sesji' : 'Failed to fetch session data');
    } finally {
      setIsLoading(false);
    }
  };

  const updateAttendance = (enrollmentId: number, status: AttendanceStatus) => {
    const current = attendance.get(enrollmentId) || { status: 'unmarked', notes: '' };
    const newAttendance = new Map(attendance);
    newAttendance.set(enrollmentId, { ...current, status });
    setAttendance(newAttendance);
    setHasChanges(true);
  };

  const updateNotes = (enrollmentId: number, notes: string) => {
    const current = attendance.get(enrollmentId) || { status: 'unmarked', notes: '' };
    const newAttendance = new Map(attendance);
    newAttendance.set(enrollmentId, { ...current, notes });
    setAttendance(newAttendance);
    setHasChanges(true);
  };

  const markAllPresent = () => {
    const newAttendance = new Map(attendance);
    session?.participants.forEach((p) => {
      const current = newAttendance.get(p.enrollment_id) || { status: 'unmarked', notes: '' };
      newAttendance.set(p.enrollment_id, { ...current, status: 'present' });
    });
    setAttendance(newAttendance);
    setHasChanges(true);
  };

  const saveAttendance = async () => {
    if (!session) return;

    setIsSaving(true);
    try {
      const attendanceData = Array.from(attendance.entries()).map(([enrollment_id, data]) => {
        // Find participant to get child_id
        const participant = session.participants.find(p => p.enrollment_id === enrollment_id);
        return {
          enrollment_id,
          child_id: participant?.child_id || enrollment_id, // Use child_id for database
          status: data.status === 'unmarked' ? 'absent' : data.status,
          notes: data.notes,
        };
      });

      await api.saveAttendance(sessionId, attendanceData);
      Alert.alert(
        language === 'pl' ? 'Sukces' : 'Success',
        t.attendance.saved,
        [{ text: 'OK', onPress: () => navigation.goBack() }]
      );
    } catch (error) {
      console.error('Error saving attendance:', error);
      Alert.alert(t.common.error, language === 'pl' ? 'Nie udało się zapisać obecności' : 'Failed to save attendance');
    } finally {
      setIsSaving(false);
    }
  };

  const formatTime = (time: string) => time.substring(0, 5);

  const getStatusCounts = () => {
    const counts: Record<AttendanceStatus, number> = {
      present: 0,
      absent: 0,
      late: 0,
      excused: 0,
      unmarked: 0,
    };
    attendance.forEach((data) => {
      counts[data.status]++;
    });
    return counts;
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.secondary} />
      </View>
    );
  }

  if (!session) {
    return (
      <View style={styles.loadingContainer}>
        <Text style={styles.loadingText}>
          {language === 'pl' ? 'Nie znaleziono sesji' : 'Session not found'}
        </Text>
      </View>
    );
  }

  const counts = getStatusCounts();

  return (
    <View style={styles.container}>
      {/* Session Header */}
      <View style={styles.sessionHeader}>
        <View style={styles.sessionInfo}>
          <Text style={styles.sessionTitle}>{session.class_name}</Text>
          <Text style={styles.sessionMeta}>
            {safeFormatDate(session.session_date, 'EEEE, d MMMM yyyy', dateLocale)}
          </Text>
          <View style={styles.sessionDetails}>
            <View style={styles.sessionDetail}>
              <Ionicons name="time-outline" size={16} color={colors.textSecondary} />
              <Text style={styles.sessionDetailText}>
                {session.time_start?.substring(0, 5) || '-'} - {session.time_end?.substring(0, 5) || '-'}
              </Text>
            </View>
            <View style={styles.sessionDetail}>
              <Ionicons name="location-outline" size={16} color={colors.textSecondary} />
              <Text style={styles.sessionDetailText}>{session.facility_name}</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Quick Stats */}
      <View style={styles.statsRow}>
        <View style={[styles.statBadge, { backgroundColor: STATUS_CONFIG.present.bg }]}>
          <Text style={[styles.statBadgeText, { color: STATUS_CONFIG.present.color }]}>
            {counts.present} {language === 'pl' ? 'obecnych' : 'present'}
          </Text>
        </View>
        <View style={[styles.statBadge, { backgroundColor: STATUS_CONFIG.absent.bg }]}>
          <Text style={[styles.statBadgeText, { color: STATUS_CONFIG.absent.color }]}>
            {counts.absent} {language === 'pl' ? 'nieobecnych' : 'absent'}
          </Text>
        </View>
        <View style={[styles.statBadge, { backgroundColor: STATUS_CONFIG.unmarked.bg }]}>
          <Text style={[styles.statBadgeText, { color: STATUS_CONFIG.unmarked.color }]}>
            {counts.unmarked} {language === 'pl' ? 'bez statusu' : 'unmarked'}
          </Text>
        </View>
      </View>

      {/* Quick Actions */}
      <View style={styles.quickActions}>
        <TouchableOpacity style={styles.quickActionButton} onPress={markAllPresent}>
          <Ionicons name="checkmark-done" size={20} color={colors.secondary} />
          <Text style={styles.quickActionText}>{t.attendance.allPresent}</Text>
        </TouchableOpacity>
      </View>

      {/* Participants List */}
      <ScrollView style={styles.participantsList}>
        {session.participants.map((participant) => {
          const status = attendance.get(participant.enrollment_id)?.status || 'unmarked';
          const notes = attendance.get(participant.enrollment_id)?.notes || '';
          const isExpanded = expandedChild === participant.child_id;
          const statusConfig = STATUS_CONFIG[status];

          return (
            <View key={participant.enrollment_id} style={styles.participantCard}>
              <TouchableOpacity
                style={styles.participantMain}
                onPress={() => setExpandedChild(isExpanded ? null : participant.child_id)}
              >
                <View style={styles.participantAvatar}>
                  <Text style={styles.participantAvatarText}>
                    {participant.first_name[0]}{participant.last_name[0]}
                  </Text>
                </View>
                <View style={styles.participantInfo}>
                  <Text style={styles.participantName}>
                    {participant.first_name} {participant.last_name}
                  </Text>
                  {participant.swimming_level && (
                    <Text style={styles.participantLevel}>
                      {t.children.level}: {participant.swimming_level}
                    </Text>
                  )}
                </View>
                <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                  <Ionicons name={statusConfig.icon as any} size={16} color={statusConfig.color} />
                </View>
                <Ionicons
                  name={isExpanded ? 'chevron-up' : 'chevron-down'}
                  size={20}
                  color={colors.textTertiary}
                />
              </TouchableOpacity>

              {isExpanded && (
                <View style={styles.participantExpanded}>
                  {/* Medical Notes Warning */}
                  {participant.medical_notes && (
                    <View style={styles.medicalWarning}>
                      <Ionicons name="medical" size={16} color={colors.error} />
                      <Text style={styles.medicalWarningText}>{participant.medical_notes}</Text>
                    </View>
                  )}

                  {/* Status Buttons */}
                  <Text style={styles.statusLabel}>
                    {language === 'pl' ? 'Status obecności' : 'Attendance status'}:
                  </Text>
                  <View style={styles.statusButtons}>
                    {(['present', 'absent', 'late', 'excused'] as AttendanceStatus[]).map((s) => {
                      const config = STATUS_CONFIG[s];
                      const isActive = status === s;
                      return (
                        <TouchableOpacity
                          key={s}
                          style={[
                            styles.statusButton,
                            { borderColor: config.color },
                            isActive && { backgroundColor: config.bg },
                          ]}
                          onPress={() => updateAttendance(participant.enrollment_id, s)}
                        >
                          <Ionicons
                            name={config.icon as any}
                            size={18}
                            color={isActive ? config.color : colors.textTertiary}
                          />
                          <Text
                            style={[
                              styles.statusButtonText,
                              { color: isActive ? config.color : colors.textSecondary },
                            ]}
                          >
                            {config.label}
                          </Text>
                        </TouchableOpacity>
                      );
                    })}
                  </View>

                  {/* Notes Input */}
                  <Text style={styles.notesLabel}>{t.attendance.notes}:</Text>
                  <TextInput
                    style={styles.notesInput}
                    placeholder={t.attendance.addNote + '...'}
                    placeholderTextColor={colors.textTertiary}
                    value={notes}
                    onChangeText={(text) => updateNotes(participant.enrollment_id, text)}
                    multiline
                  />
                </View>
              )}
            </View>
          );
        })}
        <View style={{ height: 100 }} />
      </ScrollView>

      {/* Save Button */}
      <View style={styles.saveButtonContainer}>
        <TouchableOpacity
          style={[styles.saveButton, !hasChanges && styles.saveButtonDisabled]}
          onPress={saveAttendance}
          disabled={isSaving || !hasChanges}
        >
          {isSaving ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <>
              <Ionicons name="save" size={20} color="#fff" />
              <Text style={styles.saveButtonText}>{t.attendance.saveAttendance}</Text>
            </>
          )}
        </TouchableOpacity>
      </View>
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
    loadingText: {
      fontSize: 16,
      color: colors.textSecondary,
    },
    sessionHeader: {
      backgroundColor: colors.surface,
      padding: 16,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    sessionInfo: {},
    sessionTitle: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.text,
    },
    sessionMeta: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
      textTransform: 'capitalize',
    },
    sessionDetails: {
      flexDirection: 'row',
      marginTop: 8,
      gap: 16,
    },
    sessionDetail: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 4,
    },
    sessionDetailText: {
      fontSize: 13,
      color: colors.textSecondary,
    },
    statsRow: {
      flexDirection: 'row',
      padding: 12,
      gap: 8,
      backgroundColor: colors.surface,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    statBadge: {
      paddingHorizontal: 12,
      paddingVertical: 6,
      borderRadius: 20,
    },
    statBadgeText: {
      fontSize: 12,
      fontWeight: '600',
    },
    quickActions: {
      flexDirection: 'row',
      padding: 12,
      backgroundColor: colors.surface,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    quickActionButton: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.secondaryLight,
      paddingHorizontal: 16,
      paddingVertical: 10,
      borderRadius: 8,
      gap: 8,
    },
    quickActionText: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.secondary,
    },
    participantsList: {
      flex: 1,
      padding: 12,
    },
    participantCard: {
      backgroundColor: colors.surface,
      borderRadius: 12,
      marginBottom: 8,
      overflow: 'hidden',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
      elevation: 1,
    },
    participantMain: {
      flexDirection: 'row',
      alignItems: 'center',
      padding: 12,
    },
    participantAvatar: {
      width: 44,
      height: 44,
      borderRadius: 22,
      backgroundColor: colors.secondary,
      justifyContent: 'center',
      alignItems: 'center',
    },
    participantAvatarText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '600',
    },
    participantInfo: {
      flex: 1,
      marginLeft: 12,
    },
    participantName: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    participantLevel: {
      fontSize: 12,
      color: colors.textSecondary,
      marginTop: 2,
    },
    statusBadge: {
      width: 36,
      height: 36,
      borderRadius: 18,
      justifyContent: 'center',
      alignItems: 'center',
      marginRight: 8,
    },
    participantExpanded: {
      padding: 12,
      paddingTop: 0,
      borderTopWidth: 1,
      borderTopColor: colors.border,
    },
    medicalWarning: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.errorLight,
      padding: 10,
      borderRadius: 8,
      marginBottom: 12,
      gap: 8,
    },
    medicalWarningText: {
      flex: 1,
      fontSize: 13,
      color: colors.error,
    },
    statusLabel: {
      fontSize: 13,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 8,
      marginTop: 8,
    },
    statusButtons: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: 8,
    },
    statusButton: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: 12,
      paddingVertical: 8,
      borderRadius: 8,
      borderWidth: 1,
      gap: 6,
    },
    statusButtonText: {
      fontSize: 13,
      fontWeight: '500',
    },
    notesLabel: {
      fontSize: 13,
      fontWeight: '600',
      color: colors.text,
      marginTop: 16,
      marginBottom: 8,
    },
    notesInput: {
      backgroundColor: colors.background,
      borderRadius: 8,
      padding: 12,
      fontSize: 14,
      color: colors.text,
      minHeight: 60,
      textAlignVertical: 'top',
    },
    saveButtonContainer: {
      position: 'absolute',
      bottom: 0,
      left: 0,
      right: 0,
      padding: 16,
      backgroundColor: colors.surface,
      borderTopWidth: 1,
      borderTopColor: colors.border,
    },
    saveButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.secondary,
      borderRadius: 12,
      paddingVertical: 16,
      gap: 8,
    },
    saveButtonDisabled: {
      backgroundColor: colors.textTertiary,
    },
    saveButtonText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '600',
    },
  });
