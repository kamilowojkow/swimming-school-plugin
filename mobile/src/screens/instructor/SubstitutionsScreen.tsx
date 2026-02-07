import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
  Alert,
  ActivityIndicator,
  Modal,
  TextInput,
} from 'react-native';
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

type TabType = 'available' | 'my_requests' | 'my_taken';

interface Substitution {
  id: number;
  session_id: number;
  session_date: string;
  time_start: string;
  time_end: string;
  class_name: string;
  facility_name: string;
  instructor_name?: string;
  original_instructor_name?: string;
  replacement_name?: string;
  reason?: string;
  created_at: string;
}

interface Session {
  id: number;
  session_date: string;
  time_start: string;
  class_name: string;
}

export default function SubstitutionsScreen() {
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');

  const [activeTab, setActiveTab] = useState<TabType>('available');
  const [substitutions, setSubstitutions] = useState<Substitution[]>([]);
  const [mySessions, setMySessions] = useState<Session[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [isRequestModalVisible, setRequestModalVisible] = useState(false);
  const [selectedSession, setSelectedSession] = useState<Session | null>(null);
  const [requestReason, setRequestReason] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const dateLocale = language === 'pl' ? pl : enUS;
  const styles = createStyles(colors);

  const fetchData = useCallback(async () => {
    try {
      const data = await api.getSubstitutions(activeTab);
      // Handle both array and object API responses
      const substitutionsArray = Array.isArray(data) ? data : (data?.substitutions || []);
      setSubstitutions(substitutionsArray);

      // Fetch my sessions for request modal
      if (activeTab === 'available') {
        const scheduleData = await api.getInstructorSchedule();
        const sessionsArray = Array.isArray(scheduleData) ? scheduleData : (scheduleData?.sessions || []);
        setMySessions(sessionsArray.filter((s: Session) => {
          try {
            return new Date(s.session_date) >= new Date();
          } catch {
            return false;
          }
        }));
      }
    } catch (error) {
      console.error('Error fetching substitutions:', error);
    } finally {
      setIsLoading(false);
    }
  }, [activeTab]);

  useEffect(() => {
    setIsLoading(true);
    fetchData();
  }, [fetchData]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const handleTakeSubstitution = async (substitutionId: number) => {
    Alert.alert(
      t.common.confirm,
      language === 'pl' ? 'Czy na pewno chcesz wziąć to zastępstwo?' : 'Are you sure you want to take this substitution?',
      [
        { text: t.common.cancel, style: 'cancel' },
        {
          text: language === 'pl' ? 'Tak, weź zastępstwo' : 'Yes, take substitution',
          onPress: async () => {
            try {
              await api.takeSubstitution(substitutionId);
              Alert.alert(
                language === 'pl' ? 'Sukces' : 'Success',
                t.substitutions.substitutionTaken
              );
              fetchData();
            } catch (error) {
              Alert.alert(t.common.error, language === 'pl' ? 'Nie udało się przyjąć zastępstwa' : 'Failed to take substitution');
            }
          },
        },
      ]
    );
  };

  const handleRequestSubstitution = async () => {
    if (!selectedSession) return;

    setIsSubmitting(true);
    try {
      await api.requestSubstitution(selectedSession.id, requestReason);
      Alert.alert(
        language === 'pl' ? 'Sukces' : 'Success',
        t.substitutions.substitutionRequested
      );
      setRequestModalVisible(false);
      setSelectedSession(null);
      setRequestReason('');
      setActiveTab('my_requests');
    } catch (error) {
      Alert.alert(t.common.error, language === 'pl' ? 'Nie udało się wysłać prośby o zastępstwo' : 'Failed to send substitution request');
    } finally {
      setIsSubmitting(false);
    }
  };

  const formatTime = (time: string) => time.substring(0, 5);

  const tabs: { key: TabType; label: string; icon: string }[] = [
    { key: 'available', label: t.substitutions.available, icon: 'hand-left-outline' },
    { key: 'my_requests', label: t.substitutions.myRequests, icon: 'paper-plane-outline' },
    { key: 'my_taken', label: t.substitutions.myTaken, icon: 'checkmark-done-outline' },
  ];

  const renderSubstitution = (item: Substitution) => {
    const isAvailable = activeTab === 'available';
    const isMyRequest = activeTab === 'my_requests';
    const isTaken = activeTab === 'my_taken';

    return (
      <View key={item.id} style={styles.card}>
        <View style={styles.cardHeader}>
          <View style={styles.cardDate}>
            <Text style={styles.cardDateDay}>
              {safeFormatDate(item.session_date, 'd', dateLocale)}
            </Text>
            <Text style={styles.cardDateMonth}>
              {safeFormatDate(item.session_date, 'MMM', dateLocale)}
            </Text>
          </View>
          <View style={styles.cardInfo}>
            <Text style={styles.cardTitle}>{item.class_name}</Text>
            <View style={styles.cardMeta}>
              <Ionicons name="time-outline" size={14} color={colors.textSecondary} />
              <Text style={styles.cardMetaText}>
                {item.time_start?.substring(0, 5) || '-'} - {item.time_end?.substring(0, 5) || '-'}
              </Text>
            </View>
            <View style={styles.cardMeta}>
              <Ionicons name="location-outline" size={14} color={colors.textSecondary} />
              <Text style={styles.cardMetaText}>{item.facility_name}</Text>
            </View>
            {isAvailable && item.instructor_name && (
              <View style={styles.cardMeta}>
                <Ionicons name="person-outline" size={14} color={colors.textSecondary} />
                <Text style={styles.cardMetaText}>{t.instructorDashboard.forInstructor}: {item.instructor_name}</Text>
              </View>
            )}
            {isMyRequest && item.replacement_name && (
              <View style={[styles.cardMeta, styles.cardMetaSuccess]}>
                <Ionicons name="checkmark-circle" size={14} color={colors.secondary} />
                <Text style={[styles.cardMetaText, { color: colors.secondary }]}>
                  {language === 'pl' ? 'Przyjął' : 'Taken by'}: {item.replacement_name}
                </Text>
              </View>
            )}
            {isTaken && item.original_instructor_name && (
              <View style={styles.cardMeta}>
                <Ionicons name="person-outline" size={14} color={colors.textSecondary} />
                <Text style={styles.cardMetaText}>
                  {language === 'pl' ? 'Zastępujesz' : 'Replacing'}: {item.original_instructor_name}
                </Text>
              </View>
            )}
          </View>
        </View>

        {item.reason && (
          <View style={styles.reasonContainer}>
            <Text style={styles.reasonLabel}>{t.substitutions.reason}:</Text>
            <Text style={styles.reasonText}>{item.reason}</Text>
          </View>
        )}

        {isAvailable && (
          <TouchableOpacity
            style={styles.takeButton}
            onPress={() => handleTakeSubstitution(item.id)}
          >
            <Ionicons name="hand-right" size={18} color="#fff" />
            <Text style={styles.takeButtonText}>{t.substitutions.takeSubstitution}</Text>
          </TouchableOpacity>
        )}

        {isMyRequest && !item.replacement_name && (
          <View style={styles.pendingBadge}>
            <Ionicons name="time" size={16} color={colors.warning} />
            <Text style={styles.pendingBadgeText}>
              {language === 'pl' ? 'Oczekuje na zastępcę' : 'Waiting for replacement'}
            </Text>
          </View>
        )}
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Tabs */}
      <View style={styles.tabs}>
        {tabs.map((tab) => (
          <TouchableOpacity
            key={tab.key}
            style={[styles.tab, activeTab === tab.key && styles.tabActive]}
            onPress={() => setActiveTab(tab.key)}
          >
            <Ionicons
              name={tab.icon as any}
              size={18}
              color={activeTab === tab.key ? colors.secondary : colors.textSecondary}
            />
            <Text style={[styles.tabText, activeTab === tab.key && styles.tabTextActive]}>
              {tab.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Request Button */}
      {activeTab === 'available' && (
        <TouchableOpacity
          style={styles.requestButton}
          onPress={() => setRequestModalVisible(true)}
        >
          <Ionicons name="add-circle" size={20} color="#fff" />
          <Text style={styles.requestButtonText}>{t.substitutions.requestSubstitution}</Text>
        </TouchableOpacity>
      )}

      {/* List */}
      <ScrollView
        style={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.secondary} />}
      >
        {isLoading ? (
          <ActivityIndicator size="large" color={colors.secondary} style={{ marginTop: 40 }} />
        ) : substitutions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons
              name={activeTab === 'available' ? 'hand-left-outline' : 'document-outline'}
              size={64}
              color={colors.border}
            />
            <Text style={styles.emptyStateTitle}>
              {t.substitutions.noSubstitutions}
            </Text>
          </View>
        ) : (
          substitutions.map(renderSubstitution)
        )}
        <View style={{ height: 24 }} />
      </ScrollView>

      {/* Request Modal */}
      <Modal
        visible={isRequestModalVisible}
        animationType="slide"
        transparent
        onRequestClose={() => setRequestModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{t.substitutions.requestSubstitution}</Text>
              <TouchableOpacity onPress={() => setRequestModalVisible(false)}>
                <Ionicons name="close" size={24} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalLabel}>{t.substitutions.selectSession}:</Text>
            <ScrollView style={styles.sessionPicker}>
              {mySessions.map((session) => (
                <TouchableOpacity
                  key={session.id}
                  style={[
                    styles.sessionOption,
                    selectedSession?.id === session.id && styles.sessionOptionSelected,
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
                    <Text style={styles.sessionOptionTime}>
                      {session.time_start?.substring(0, 5) || '-'} •{' '}
                      {safeFormatDate(session.session_date, 'EEEE', dateLocale)}
                    </Text>
                  </View>
                  {selectedSession?.id === session.id && (
                    <Ionicons name="checkmark-circle" size={24} color={colors.secondary} />
                  )}
                </TouchableOpacity>
              ))}
            </ScrollView>

            <Text style={styles.modalLabel}>
              {t.substitutions.reason} ({language === 'pl' ? 'opcjonalnie' : 'optional'}):
            </Text>
            <TextInput
              style={styles.reasonInput}
              placeholder={language === 'pl' ? 'Np. choroba, wyjazd służbowy...' : 'E.g. illness, business trip...'}
              placeholderTextColor={colors.textTertiary}
              value={requestReason}
              onChangeText={setRequestReason}
              multiline
            />

            <TouchableOpacity
              style={[styles.submitButton, (!selectedSession || isSubmitting) && styles.submitButtonDisabled]}
              onPress={handleRequestSubstitution}
              disabled={!selectedSession || isSubmitting}
            >
              {isSubmitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <Ionicons name="paper-plane" size={20} color="#fff" />
                  <Text style={styles.submitButtonText}>{t.substitutions.submit}</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    tabs: {
      flexDirection: 'row',
      backgroundColor: colors.surface,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    tab: {
      flex: 1,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: 14,
      gap: 6,
      borderBottomWidth: 2,
      borderBottomColor: 'transparent',
    },
    tabActive: {
      borderBottomColor: colors.secondary,
    },
    tabText: {
      fontSize: 13,
      fontWeight: '500',
      color: colors.textSecondary,
    },
    tabTextActive: {
      color: colors.secondary,
    },
    requestButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.secondary,
      margin: 16,
      marginBottom: 8,
      paddingVertical: 14,
      borderRadius: 12,
      gap: 8,
    },
    requestButtonText: {
      color: '#fff',
      fontSize: 15,
      fontWeight: '600',
    },
    list: {
      flex: 1,
      padding: 16,
      paddingTop: 8,
    },
    emptyState: {
      alignItems: 'center',
      paddingVertical: 60,
    },
    emptyStateTitle: {
      fontSize: 16,
      color: colors.textSecondary,
      marginTop: 16,
      textAlign: 'center',
    },
    card: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      marginBottom: 12,
      overflow: 'hidden',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
      elevation: 1,
    },
    cardHeader: {
      flexDirection: 'row',
      padding: 16,
    },
    cardDate: {
      width: 50,
      alignItems: 'center',
      backgroundColor: colors.surfaceSecondary,
      borderRadius: 10,
      paddingVertical: 8,
      marginRight: 12,
    },
    cardDateDay: {
      fontSize: 20,
      fontWeight: '700',
      color: colors.text,
    },
    cardDateMonth: {
      fontSize: 12,
      color: colors.textSecondary,
      textTransform: 'uppercase',
    },
    cardInfo: {
      flex: 1,
    },
    cardTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 6,
    },
    cardMeta: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 4,
      gap: 4,
    },
    cardMetaText: {
      fontSize: 13,
      color: colors.textSecondary,
    },
    cardMetaSuccess: {
      marginTop: 8,
    },
    reasonContainer: {
      paddingHorizontal: 16,
      paddingBottom: 12,
    },
    reasonLabel: {
      fontSize: 12,
      fontWeight: '600',
      color: colors.textSecondary,
      marginBottom: 2,
    },
    reasonText: {
      fontSize: 13,
      color: colors.text,
      fontStyle: 'italic',
    },
    takeButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.secondary,
      margin: 12,
      marginTop: 4,
      paddingVertical: 12,
      borderRadius: 10,
      gap: 8,
    },
    takeButtonText: {
      color: '#fff',
      fontSize: 14,
      fontWeight: '600',
    },
    pendingBadge: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.warningLight,
      margin: 12,
      marginTop: 4,
      paddingVertical: 10,
      borderRadius: 10,
      gap: 6,
    },
    pendingBadgeText: {
      color: colors.warning,
      fontSize: 13,
      fontWeight: '500',
    },
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
    sessionPicker: {
      maxHeight: 200,
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
      borderColor: colors.secondary,
      backgroundColor: colors.secondaryLight,
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
    sessionOptionTime: {
      fontSize: 13,
      color: colors.textSecondary,
      marginTop: 2,
      textTransform: 'capitalize',
    },
    reasonInput: {
      backgroundColor: colors.background,
      borderRadius: 12,
      padding: 14,
      fontSize: 15,
      color: colors.text,
      minHeight: 80,
      textAlignVertical: 'top',
      marginBottom: 20,
    },
    submitButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: colors.secondary,
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
