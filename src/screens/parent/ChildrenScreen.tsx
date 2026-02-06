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
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

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
  'Zolwik': { bg: '#fef9c3', text: '#ca8a04' },
  'Delfinek': { bg: '#dbeafe', text: '#2563eb' },
  'Rekin': { bg: '#dcfce7', text: '#16a34a' },
  'Mistrz': { bg: '#f3e8ff', text: '#9333ea' },
};

export default function ChildrenScreen() {
  const navigation = useNavigation<any>();
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();

  const [children, setChildren] = useState<Child[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Normalize child data from API to handle different field names
  const normalizeChild = (data: any): Child => {
    return {
      id: data.id,
      first_name: data.first_name || data.firstName || data.name?.split(' ')[0] || '',
      last_name: data.last_name || data.lastName || data.name?.split(' ')[1] || '',
      birth_date: data.birth_date || data.birthDate || data.date_of_birth || data.dob || '',
      swimming_level: data.swimming_level || data.swimmingLevel || data.level || data.skill_level || data.skillLevel || '',
      active_courses: data.active_courses ?? data.activeCourses ?? data.courses_count ?? data.coursesCount ?? data.enrollments_count ?? 0,
      total_points: data.total_points ?? data.totalPoints ?? data.points ?? 0,
      achievements_count: data.achievements_count ?? data.achievementsCount ?? data.badges_count ?? data.badgesCount ?? data.achievements?.length ?? 0,
      medical_notes: data.medical_notes || data.medicalNotes || data.health_notes || undefined,
      next_session: data.next_session || data.nextSession || data.upcoming_session || undefined,
    };
  };

  const fetchChildren = async () => {
    try {
      const data = await api.getChildren();
      // Handle both array and object with children property
      const childrenArray = Array.isArray(data) ? data : (data.children || data.data || []);
      const normalizedChildren = childrenArray.map(normalizeChild);
      setChildren(normalizedChildren);
      console.log('Children data loaded:', normalizedChildren);
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
    return LEVEL_COLORS[level] || { bg: colors.surfaceSecondary, text: colors.textSecondary };
  };

  const styles = createStyles(colors, isDark);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
      }
    >
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>{t.children.title}</Text>
        <Text style={styles.headerSubtitle}>
          {children.length} {language === 'pl' ? 'zapisanych' : 'enrolled'}
        </Text>
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
                      {(child.first_name || '')[0] || '?'}{(child.last_name || '')[0] || '?'}
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
                    {child.first_name || ''} {child.last_name || ''}
                  </Text>
                  {child.birth_date ? (
                    <Text style={styles.childAge}>
                      {getAge(child.birth_date)} {language === 'pl' ? 'lat' : 'years'}
                    </Text>
                  ) : null}
                </View>
                {child.swimming_level ? (
                  <View style={[styles.levelBadge, { backgroundColor: levelStyle.bg }]}>
                    <Text style={[styles.levelText, { color: levelStyle.text }]}>
                      {child.swimming_level}
                    </Text>
                  </View>
                ) : null}
              </View>

              {/* Stats Row */}
              <View style={styles.statsRow}>
                <View style={styles.statItem}>
                  <View style={[styles.statIcon, { backgroundColor: colors.primaryLight }]}>
                    <Ionicons name="school" size={16} color={colors.primary} />
                  </View>
                  <View>
                    <Text style={styles.statValue}>{child.active_courses}</Text>
                    <Text style={styles.statLabel}>
                      {child.active_courses === 1
                        ? (language === 'pl' ? 'Kurs' : 'Course')
                        : (language === 'pl' ? 'Kursy' : 'Courses')}
                    </Text>
                  </View>
                </View>

                <View style={styles.statItem}>
                  <View style={[styles.statIcon, { backgroundColor: colors.warningLight }]}>
                    <Ionicons name="star" size={16} color={colors.warning} />
                  </View>
                  <View>
                    <Text style={styles.statValue}>{child.total_points}</Text>
                    <Text style={styles.statLabel}>
                      {language === 'pl' ? 'Punkty' : 'Points'}
                    </Text>
                  </View>
                </View>

                <View style={styles.statItem}>
                  <View style={[styles.statIcon, { backgroundColor: colors.successLight }]}>
                    <Ionicons name="trophy" size={16} color={colors.success} />
                  </View>
                  <View>
                    <Text style={styles.statValue}>{child.achievements_count}</Text>
                    <Text style={styles.statLabel}>
                      {language === 'pl' ? 'Odznaki' : 'Badges'}
                    </Text>
                  </View>
                </View>
              </View>

              {/* Next Session */}
              {child.next_session && (
                <View style={styles.nextSession}>
                  <Ionicons name="calendar-outline" size={16} color={colors.textSecondary} />
                  <Text style={styles.nextSessionText}>
                    {language === 'pl' ? 'Nastepne zajecia' : 'Next lesson'}: {child.next_session.date} {language === 'pl' ? 'o' : 'at'} {child.next_session.time}
                  </Text>
                </View>
              )}

              {/* Arrow */}
              <View style={styles.arrowContainer}>
                <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
              </View>
            </TouchableOpacity>
          );
        })}
      </View>

      {/* Empty State */}
      {children.length === 0 && (
        <View style={styles.emptyState}>
          <Ionicons name="people-outline" size={64} color={colors.textTertiary} />
          <Text style={styles.emptyTitle}>{t.children.noChildren}</Text>
          <Text style={styles.emptySubtitle}>
            {language === 'pl'
              ? 'Skontaktuj sie z biurem, aby zapisac dziecko na zajecia'
              : 'Contact the office to enroll your child in classes'}
          </Text>
        </View>
      )}

      <View style={{ height: 24 }} />
    </ScrollView>
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
    header: {
      padding: 20,
      paddingBottom: 8,
    },
    headerTitle: {
      fontSize: 28,
      fontWeight: '700',
      color: colors.text,
    },
    headerSubtitle: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
    },
    childrenList: {
      padding: 16,
      gap: 16,
    },
    childCard: {
      backgroundColor: colors.surface,
      borderRadius: 20,
      padding: 20,
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: isDark ? 0.3 : 0.06,
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
      backgroundColor: colors.primary,
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
      backgroundColor: colors.error,
      justifyContent: 'center',
      alignItems: 'center',
      borderWidth: 2,
      borderColor: colors.surface,
    },
    childMainInfo: {
      flex: 1,
      marginLeft: 14,
    },
    childName: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
    },
    childAge: {
      fontSize: 14,
      color: colors.textSecondary,
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
      borderTopColor: colors.border,
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
      color: colors.text,
    },
    statLabel: {
      fontSize: 11,
      color: colors.textSecondary,
    },
    nextSession: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      marginTop: 16,
      paddingTop: 16,
      borderTopWidth: 1,
      borderTopColor: colors.border,
    },
    nextSessionText: {
      fontSize: 13,
      color: colors.textSecondary,
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
      color: colors.text,
      marginTop: 16,
    },
    emptySubtitle: {
      fontSize: 14,
      color: colors.textSecondary,
      textAlign: 'center',
      marginTop: 8,
    },
  });
