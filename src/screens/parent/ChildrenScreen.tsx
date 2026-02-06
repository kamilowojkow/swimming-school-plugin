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
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import api from '../../api/client';

interface Child {
  id: number;
  first_name: string;
  last_name: string;
  birth_date: string;
  swimming_level: string;
  active_courses: number;
  total_points: number;
  achievements_count: number;
  medical_notes?: string;
  next_session?: {
    date: string;
    time: string;
    class_name: string;
  };
}

const LEVEL_COLORS: Record<string, { bg: string; text: string }> = {
  'Żółwik': { bg: '#fef9c3', text: '#ca8a04' },
  'Delfinek': { bg: '#dbeafe', text: '#2563eb' },
  'Rekin': { bg: '#dcfce7', text: '#16a34a' },
  'Mistrz': { bg: '#f3e8ff', text: '#9333ea' },
};

export default function ChildrenScreen() {
  const navigation = useNavigation<any>();
  const [children, setChildren] = useState<Child[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchChildren = async () => {
    try {
      const data = await api.getChildren();
      setChildren(data);
    } catch (error) {
      console.error('Error fetching children:', error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchChildren();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchChildren();
    setRefreshing(false);
  };

  const getAge = (birthDate: string) => {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    return age;
  };

  const getLevelStyle = (level: string) => {
    return LEVEL_COLORS[level] || { bg: '#f3f4f6', text: '#6b7280' };
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Twoje dzieci</Text>
        <Text style={styles.headerSubtitle}>{children.length} zapisanych</Text>
      </View>

      {/* Children List */}
      <View style={styles.childrenList}>
        {children.map((child) => {
          const levelStyle = getLevelStyle(child.swimming_level);
          return (
            <TouchableOpacity
              key={child.id}
              style={styles.childCard}
              onPress={() => navigation.navigate('ChildDetails', { childId: child.id })}
              activeOpacity={0.7}
            >
              {/* Child Header */}
              <View style={styles.childHeader}>
                <View style={styles.avatarContainer}>
                  <View style={styles.avatar}>
                    <Text style={styles.avatarText}>
                      {child.first_name[0]}{child.last_name[0]}
                    </Text>
                  </View>
                  {child.medical_notes && (
                    <View style={styles.medicalBadge}>
                      <Ionicons name="medical" size={10} color="#fff" />
                    </View>
                  )}
                </View>
                <View style={styles.childMainInfo}>
                  <Text style={styles.childName}>
                    {child.first_name} {child.last_name}
                  </Text>
                  <Text style={styles.childAge}>{getAge(child.birth_date)} lat</Text>
                </View>
                <View style={[styles.levelBadge, { backgroundColor: levelStyle.bg }]}>
                  <Text style={[styles.levelText, { color: levelStyle.text }]}>
                    {child.swimming_level}
                  </Text>
                </View>
              </View>

              {/* Stats Row */}
              <View style={styles.statsRow}>
                <View style={styles.statItem}>
                  <View style={[styles.statIcon, { backgroundColor: '#eff6ff' }]}>
                    <Ionicons name="school" size={16} color="#3b82f6" />
                  </View>
                  <View>
                    <Text style={styles.statValue}>{child.active_courses}</Text>
                    <Text style={styles.statLabel}>
                      {child.active_courses === 1 ? 'Kurs' : 'Kursy'}
                    </Text>
                  </View>
                </View>

                <View style={styles.statItem}>
                  <View style={[styles.statIcon, { backgroundColor: '#fef3c7' }]}>
                    <Ionicons name="star" size={16} color="#f59e0b" />
                  </View>
                  <View>
                    <Text style={styles.statValue}>{child.total_points}</Text>
                    <Text style={styles.statLabel}>Punkty</Text>
                  </View>
                </View>

                <View style={styles.statItem}>
                  <View style={[styles.statIcon, { backgroundColor: '#dcfce7' }]}>
                    <Ionicons name="trophy" size={16} color="#16a34a" />
                  </View>
                  <View>
                    <Text style={styles.statValue}>{child.achievements_count}</Text>
                    <Text style={styles.statLabel}>Odznaki</Text>
                  </View>
                </View>
              </View>

              {/* Next Session */}
              {child.next_session && (
                <View style={styles.nextSession}>
                  <Ionicons name="calendar-outline" size={16} color="#6b7280" />
                  <Text style={styles.nextSessionText}>
                    Następne zajęcia: {child.next_session.date} o {child.next_session.time}
                  </Text>
                </View>
              )}

              {/* Arrow */}
              <View style={styles.arrowContainer}>
                <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
              </View>
            </TouchableOpacity>
          );
        })}
      </View>

      {/* Empty State */}
      {children.length === 0 && (
        <View style={styles.emptyState}>
          <Ionicons name="people-outline" size={64} color="#d1d5db" />
          <Text style={styles.emptyTitle}>Brak zapisanych dzieci</Text>
          <Text style={styles.emptySubtitle}>
            Skontaktuj się z biurem, aby zapisać dziecko na zajęcia
          </Text>
        </View>
      )}

      <View style={{ height: 24 }} />
    </ScrollView>
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
  header: {
    padding: 20,
    paddingBottom: 8,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: '700',
    color: '#111827',
  },
  headerSubtitle: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 4,
  },
  childrenList: {
    padding: 16,
    gap: 16,
  },
  childCard: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
  },
  childHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  avatarContainer: {
    position: 'relative',
  },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#3b82f6',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    color: '#fff',
    fontSize: 20,
    fontWeight: '700',
  },
  medicalBadge: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#ef4444',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#fff',
  },
  childMainInfo: {
    flex: 1,
    marginLeft: 14,
  },
  childName: {
    fontSize: 18,
    fontWeight: '600',
    color: '#111827',
  },
  childAge: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 2,
  },
  levelBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
  },
  levelText: {
    fontSize: 12,
    fontWeight: '600',
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#f3f4f6',
  },
  statItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statIcon: {
    width: 32,
    height: 32,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  statValue: {
    fontSize: 16,
    fontWeight: '700',
    color: '#111827',
  },
  statLabel: {
    fontSize: 11,
    color: '#6b7280',
  },
  nextSession: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#f3f4f6',
  },
  nextSessionText: {
    fontSize: 13,
    color: '#6b7280',
  },
  arrowContainer: {
    position: 'absolute',
    right: 16,
    top: '50%',
    marginTop: -10,
  },
  emptyState: {
    alignItems: 'center',
    padding: 48,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#374151',
    marginTop: 16,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#6b7280',
    textAlign: 'center',
    marginTop: 8,
  },
});
