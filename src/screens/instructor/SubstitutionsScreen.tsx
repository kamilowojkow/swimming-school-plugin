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
  const [activeTab, setActiveTab] = useState<TabType>('available');
  const [substitutions, setSubstitutions] = useState<Substitution[]>([]);
  const [mySessions, setMySessions] = useState<Session[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [isRequestModalVisible, setRequestModalVisible] = useState(false);
  const [selectedSession, setSelectedSession] = useState<Session | null>(null);
  const [requestReason, setRequestReason] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

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
      'Potwierdzenie',
      'Czy na pewno chcesz wziąć to zastępstwo?',
      [
        { text: 'Anuluj', style: 'cancel' },
        {
          text: 'Tak, weź zastępstwo',
          onPress: async () => {
            try {
              await api.takeSubstitution(substitutionId);
              Alert.alert('Sukces', 'Zastępstwo zostało przyjęte');
              fetchData();
            } catch (error) {
              Alert.alert('Błąd', 'Nie udało się przyjąć zastępstwa');
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
      Alert.alert('Sukces', 'Prośba o zastępstwo została wysłana');
      setRequestModalVisible(false);
      setSelectedSession(null);
      setRequestReason('');
      setActiveTab('my_requests');
    } catch (error) {
      Alert.alert('Błąd', 'Nie udało się wysłać prośby o zastępstwo');
    } finally {
      setIsSubmitting(false);
    }
  };

  const formatTime = (time: string) => time.substring(0, 5);

  const tabs: { key: TabType; label: string; icon: string }[] = [
    { key: 'available', label: 'Dostępne', icon: 'hand-left-outline' },
    { key: 'my_requests', label: 'Moje prośby', icon: 'paper-plane-outline' },
    { key: 'my_taken', label: 'Przyjęte', icon: 'checkmark-done-outline' },
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
              {safeFormatDate(item.session_date, 'd')}
            </Text>
            <Text style={styles.cardDateMonth}>
              {safeFormatDate(item.session_date, 'MMM')}
            </Text>
          </View>
          <View style={styles.cardInfo}>
            <Text style={styles.cardTitle}>{item.class_name}</Text>
            <View style={styles.cardMeta}>
              <Ionicons name="time-outline" size={14} color="#6b7280" />
              <Text style={styles.cardMetaText}>
                {item.time_start?.substring(0, 5) || '-'} - {item.time_end?.substring(0, 5) || '-'}
              </Text>
            </View>
            <View style={styles.cardMeta}>
              <Ionicons name="location-outline" size={14} color="#6b7280" />
              <Text style={styles.cardMetaText}>{item.facility_name}</Text>
            </View>
            {isAvailable && item.instructor_name && (
              <View style={styles.cardMeta}>
                <Ionicons name="person-outline" size={14} color="#6b7280" />
                <Text style={styles.cardMetaText}>Za: {item.instructor_name}</Text>
              </View>
            )}
            {isMyRequest && item.replacement_name && (
              <View style={[styles.cardMeta, styles.cardMetaSuccess]}>
                <Ionicons name="checkmark-circle" size={14} color="#10b981" />
                <Text style={[styles.cardMetaText, { color: '#10b981' }]}>
                  Przyjął: {item.replacement_name}
                </Text>
              </View>
            )}
            {isTaken && item.original_instructor_name && (
              <View style={styles.cardMeta}>
                <Ionicons name="person-outline" size={14} color="#6b7280" />
                <Text style={styles.cardMetaText}>Zastępujesz: {item.original_instructor_name}</Text>
              </View>
            )}
          </View>
        </View>

        {item.reason && (
          <View style={styles.reasonContainer}>
            <Text style={styles.reasonLabel}>Powód:</Text>
            <Text style={styles.reasonText}>{item.reason}</Text>
          </View>
        )}

        {isAvailable && (
          <TouchableOpacity
            style={styles.takeButton}
            onPress={() => handleTakeSubstitution(item.id)}
          >
            <Ionicons name="hand-right" size={18} color="#fff" />
            <Text style={styles.takeButtonText}>Weź zastępstwo</Text>
          </TouchableOpacity>
        )}

        {isMyRequest && !item.replacement_name && (
          <View style={styles.pendingBadge}>
            <Ionicons name="time" size={16} color="#f59e0b" />
            <Text style={styles.pendingBadgeText}>Oczekuje na zastępcę</Text>
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
              color={activeTab === tab.key ? '#10b981' : '#6b7280'}
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
          <Text style={styles.requestButtonText}>Zgłoś potrzebę zastępstwa</Text>
        </TouchableOpacity>
      )}

      {/* List */}
      <ScrollView
        style={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {isLoading ? (
          <ActivityIndicator size="large" color="#10b981" style={{ marginTop: 40 }} />
        ) : substitutions.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons
              name={activeTab === 'available' ? 'hand-left-outline' : 'document-outline'}
              size={64}
              color="#d1d5db"
            />
            <Text style={styles.emptyStateTitle}>
              {activeTab === 'available'
                ? 'Brak dostępnych zastępstw'
                : activeTab === 'my_requests'
                ? 'Nie masz żadnych próśb o zastępstwo'
                : 'Nie przyjąłeś żadnych zastępstw'}
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
              <Text style={styles.modalTitle}>Zgłoś potrzebę zastępstwa</Text>
              <TouchableOpacity onPress={() => setRequestModalVisible(false)}>
                <Ionicons name="close" size={24} color="#6b7280" />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalLabel}>Wybierz zajęcia:</Text>
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
                      {safeFormatDate(session.session_date, 'd')}
                    </Text>
                    <Text style={styles.sessionOptionMonth}>
                      {safeFormatDate(session.session_date, 'MMM')}
                    </Text>
                  </View>
                  <View style={styles.sessionOptionInfo}>
                    <Text style={styles.sessionOptionTitle}>{session.class_name}</Text>
                    <Text style={styles.sessionOptionTime}>
                      {session.time_start?.substring(0, 5) || '-'} •{' '}
                      {safeFormatDate(session.session_date, 'EEEE')}
                    </Text>
                  </View>
                  {selectedSession?.id === session.id && (
                    <Ionicons name="checkmark-circle" size={24} color="#10b981" />
                  )}
                </TouchableOpacity>
              ))}
            </ScrollView>

            <Text style={styles.modalLabel}>Powód (opcjonalnie):</Text>
            <TextInput
              style={styles.reasonInput}
              placeholder="Np. choroba, wyjazd służbowy..."
              placeholderTextColor="#9ca3af"
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
                  <Text style={styles.submitButtonText}>Wyślij prośbę</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  tabs: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
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
    borderBottomColor: '#10b981',
  },
  tabText: {
    fontSize: 13,
    fontWeight: '500',
    color: '#6b7280',
  },
  tabTextActive: {
    color: '#10b981',
  },
  requestButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#10b981',
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
    color: '#6b7280',
    marginTop: 16,
    textAlign: 'center',
  },
  card: {
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
  cardHeader: {
    flexDirection: 'row',
    padding: 16,
  },
  cardDate: {
    width: 50,
    alignItems: 'center',
    backgroundColor: '#f3f4f6',
    borderRadius: 10,
    paddingVertical: 8,
    marginRight: 12,
  },
  cardDateDay: {
    fontSize: 20,
    fontWeight: '700',
    color: '#111827',
  },
  cardDateMonth: {
    fontSize: 12,
    color: '#6b7280',
    textTransform: 'uppercase',
  },
  cardInfo: {
    flex: 1,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
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
    color: '#6b7280',
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
    color: '#6b7280',
    marginBottom: 2,
  },
  reasonText: {
    fontSize: 13,
    color: '#374151',
    fontStyle: 'italic',
  },
  takeButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#10b981',
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
    backgroundColor: '#fef3c7',
    margin: 12,
    marginTop: 4,
    paddingVertical: 10,
    borderRadius: 10,
    gap: 6,
  },
  pendingBadgeText: {
    color: '#f59e0b',
    fontSize: 13,
    fontWeight: '500',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff',
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
    color: '#111827',
  },
  modalLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
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
    borderColor: '#e5e7eb',
    marginBottom: 8,
  },
  sessionOptionSelected: {
    borderColor: '#10b981',
    backgroundColor: '#ecfdf5',
  },
  sessionOptionDate: {
    width: 44,
    alignItems: 'center',
    marginRight: 12,
  },
  sessionOptionDay: {
    fontSize: 18,
    fontWeight: '700',
    color: '#111827',
  },
  sessionOptionMonth: {
    fontSize: 11,
    color: '#6b7280',
    textTransform: 'uppercase',
  },
  sessionOptionInfo: {
    flex: 1,
  },
  sessionOptionTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#111827',
  },
  sessionOptionTime: {
    fontSize: 13,
    color: '#6b7280',
    marginTop: 2,
    textTransform: 'capitalize',
  },
  reasonInput: {
    backgroundColor: '#f9fafb',
    borderRadius: 12,
    padding: 14,
    fontSize: 15,
    color: '#111827',
    minHeight: 80,
    textAlignVertical: 'top',
    marginBottom: 20,
  },
  submitButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#10b981',
    paddingVertical: 16,
    borderRadius: 12,
    gap: 8,
  },
  submitButtonDisabled: {
    backgroundColor: '#9ca3af',
  },
  submitButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
