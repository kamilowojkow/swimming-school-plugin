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

const STATUS_CONFIG: Record<AttendanceStatus, { label: string; color: string; bg: string; icon: string }> = {
  present: { label: 'Obecny', color: '#10b981', bg: '#ecfdf5', icon: 'checkmark-circle' },
  absent: { label: 'Nieobecny', color: '#ef4444', bg: '#fef2f2', icon: 'close-circle' },
  late: { label: 'Spóźniony', color: '#f59e0b', bg: '#fef3c7', icon: 'time' },
  excused: { label: 'Usprawiedliwiony', color: '#6366f1', bg: '#eef2ff', icon: 'document-text' },
  unmarked: { label: 'Nie oznaczono', color: '#6b7280', bg: '#f3f4f6', icon: 'help-circle' },
};

export default function AttendanceScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { sessionId } = route.params;

  const [session, setSession] = useState<SessionDetails | null>(null);
  const [attendance, setAttendance] = useState<Map<number, { status: AttendanceStatus; notes: string }>>(new Map());
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [expandedChild, setExpandedChild] = useState<number | null>(null);
  const [hasChanges, setHasChanges] = useState(false);

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
      Alert.alert('Błąd', 'Nie udało się pobrać danych sesji');
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
      const attendanceData = Array.from(attendance.entries()).map(([enrollment_id, data]) => ({
        enrollment_id,
        status: data.status === 'unmarked' ? 'absent' : data.status,
        notes: data.notes,
      }));

      await api.saveAttendance(sessionId, attendanceData);
      Alert.alert('Sukces', 'Obecność została zapisana', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (error) {
      console.error('Error saving attendance:', error);
      Alert.alert('Błąd', 'Nie udało się zapisać obecności');
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
        <ActivityIndicator size="large" color="#10b981" />
      </View>
    );
  }

  if (!session) {
    return (
      <View style={styles.loadingContainer}>
        <Text>Nie znaleziono sesji</Text>
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
            {safeFormatDate(session.session_date, 'EEEE, d MMMM yyyy')}
          </Text>
          <View style={styles.sessionDetails}>
            <View style={styles.sessionDetail}>
              <Ionicons name="time-outline" size={16} color="#6b7280" />
              <Text style={styles.sessionDetailText}>
                {session.time_start?.substring(0, 5) || '-'} - {session.time_end?.substring(0, 5) || '-'}
              </Text>
            </View>
            <View style={styles.sessionDetail}>
              <Ionicons name="location-outline" size={16} color="#6b7280" />
              <Text style={styles.sessionDetailText}>{session.facility_name}</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Quick Stats */}
      <View style={styles.statsRow}>
        <View style={[styles.statBadge, { backgroundColor: STATUS_CONFIG.present.bg }]}>
          <Text style={[styles.statBadgeText, { color: STATUS_CONFIG.present.color }]}>
            {counts.present} obecnych
          </Text>
        </View>
        <View style={[styles.statBadge, { backgroundColor: STATUS_CONFIG.absent.bg }]}>
          <Text style={[styles.statBadgeText, { color: STATUS_CONFIG.absent.color }]}>
            {counts.absent} nieobecnych
          </Text>
        </View>
        <View style={[styles.statBadge, { backgroundColor: STATUS_CONFIG.unmarked.bg }]}>
          <Text style={[styles.statBadgeText, { color: STATUS_CONFIG.unmarked.color }]}>
            {counts.unmarked} bez statusu
          </Text>
        </View>
      </View>

      {/* Quick Actions */}
      <View style={styles.quickActions}>
        <TouchableOpacity style={styles.quickActionButton} onPress={markAllPresent}>
          <Ionicons name="checkmark-done" size={20} color="#10b981" />
          <Text style={styles.quickActionText}>Wszyscy obecni</Text>
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
                      Poziom: {participant.swimming_level}
                    </Text>
                  )}
                </View>
                <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                  <Ionicons name={statusConfig.icon as any} size={16} color={statusConfig.color} />
                </View>
                <Ionicons
                  name={isExpanded ? 'chevron-up' : 'chevron-down'}
                  size={20}
                  color="#9ca3af"
                />
              </TouchableOpacity>

              {isExpanded && (
                <View style={styles.participantExpanded}>
                  {/* Medical Notes Warning */}
                  {participant.medical_notes && (
                    <View style={styles.medicalWarning}>
                      <Ionicons name="medical" size={16} color="#ef4444" />
                      <Text style={styles.medicalWarningText}>{participant.medical_notes}</Text>
                    </View>
                  )}

                  {/* Status Buttons */}
                  <Text style={styles.statusLabel}>Status obecności:</Text>
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
                            color={isActive ? config.color : '#9ca3af'}
                          />
                          <Text
                            style={[
                              styles.statusButtonText,
                              { color: isActive ? config.color : '#6b7280' },
                            ]}
                          >
                            {config.label}
                          </Text>
                        </TouchableOpacity>
                      );
                    })}
                  </View>

                  {/* Notes Input */}
                  <Text style={styles.notesLabel}>Notatki:</Text>
                  <TextInput
                    style={styles.notesInput}
                    placeholder="Dodaj notatkę (opcjonalnie)..."
                    placeholderTextColor="#9ca3af"
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
              <Text style={styles.saveButtonText}>Zapisz obecność</Text>
            </>
          )}
        </TouchableOpacity>
      </View>
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
  sessionHeader: {
    backgroundColor: '#fff',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  sessionInfo: {},
  sessionTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#111827',
  },
  sessionMeta: {
    fontSize: 14,
    color: '#6b7280',
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
    color: '#6b7280',
  },
  statsRow: {
    flexDirection: 'row',
    padding: 12,
    gap: 8,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
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
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  quickActionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ecfdf5',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
    gap: 8,
  },
  quickActionText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#10b981',
  },
  participantsList: {
    flex: 1,
    padding: 12,
  },
  participantCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    marginBottom: 8,
    overflow: 'hidden',
    shadowColor: '#000',
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
    backgroundColor: '#10b981',
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
    color: '#111827',
  },
  participantLevel: {
    fontSize: 12,
    color: '#6b7280',
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
    borderTopColor: '#e5e7eb',
  },
  medicalWarning: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef2f2',
    padding: 10,
    borderRadius: 8,
    marginBottom: 12,
    gap: 8,
  },
  medicalWarningText: {
    flex: 1,
    fontSize: 13,
    color: '#ef4444',
  },
  statusLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: '#374151',
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
    color: '#374151',
    marginTop: 16,
    marginBottom: 8,
  },
  notesInput: {
    backgroundColor: '#f9fafb',
    borderRadius: 8,
    padding: 12,
    fontSize: 14,
    color: '#111827',
    minHeight: 60,
    textAlignVertical: 'top',
  },
  saveButtonContainer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#e5e7eb',
  },
  saveButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#10b981',
    borderRadius: 12,
    paddingVertical: 16,
    gap: 8,
  },
  saveButtonDisabled: {
    backgroundColor: '#9ca3af',
  },
  saveButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
