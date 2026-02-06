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
} from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { format } from 'date-fns';
import { pl } from 'date-fns/locale';
import api from '../../api/client';

interface Achievement {
  id: number;
  name: string;
  description: string;
  icon: string;
  earned_at: string;
  points: number;
}

interface Course {
  id: number;
  name: string;
  instructor_name: string;
  day_of_week: string;
  time_start: string;
  time_end: string;
  facility_name: string;
  sessions_remaining: number;
  sessions_total: number;
}

interface AttendanceRecord {
  id: number;
  session_date: string;
  status: 'present' | 'absent' | 'late' | 'excused';
  class_name: string;
}

interface ChildDetails {
  id: number;
  first_name: string;
  last_name: string;
  birth_date: string;
  swimming_level: string;
  total_points: number;
  medical_notes?: string;
  achievements: Achievement[];
  courses: Course[];
  recent_attendance: AttendanceRecord[];
}

const LEVEL_COLORS: Record<string, { bg: string; text: string; icon: string }> = {
  'Żółwik': { bg: '#fef9c3', text: '#ca8a04', icon: '🐢' },
  'Delfinek': { bg: '#dbeafe', text: '#2563eb', icon: '🐬' },
  'Rekin': { bg: '#dcfce7', text: '#16a34a', icon: '🦈' },
  'Mistrz': { bg: '#f3e8ff', text: '#9333ea', icon: '🏆' },
};

const ATTENDANCE_STATUS: Record<string, { label: string; color: string; bg: string }> = {
  present: { label: 'Obecny', color: '#16a34a', bg: '#dcfce7' },
  absent: { label: 'Nieobecny', color: '#dc2626', bg: '#fef2f2' },
  late: { label: 'Spóźniony', color: '#f59e0b', bg: '#fef3c7' },
  excused: { label: 'Usprawiedliwiony', color: '#6b7280', bg: '#f3f4f6' },
};

export default function ChildDetailsScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { childId } = route.params;

  const [child, setChild] = useState<ChildDetails | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<'info' | 'courses' | 'achievements' | 'attendance'>('info');

  const fetchChildDetails = async () => {
    try {
      const data = await api.getChildDetails(childId);
      setChild(data);
    } catch (error) {
      console.error('Error fetching child details:', error);
      Alert.alert('Błąd', 'Nie udało się pobrać danych dziecka');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchChildDetails();
  }, [childId]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchChildDetails();
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
    return LEVEL_COLORS[level] || { bg: '#f3f4f6', text: '#6b7280', icon: '🏊' };
  };

  const getDayName = (day: string) => {
    const days: Record<string, string> = {
      monday: 'Poniedziałek',
      tuesday: 'Wtorek',
      wednesday: 'Środa',
      thursday: 'Czwartek',
      friday: 'Piątek',
      saturday: 'Sobota',
      sunday: 'Niedziela',
    };
    return days[day.toLowerCase()] || day;
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
      </View>
    );
  }

  if (!child) {
    return (
      <View style={styles.loadingContainer}>
        <Text style={styles.errorText}>Nie znaleziono dziecka</Text>
      </View>
    );
  }

  const levelStyle = getLevelStyle(child.swimming_level);

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Header Card */}
      <View style={styles.headerCard}>
        <View style={styles.avatarLarge}>
          <Text style={styles.avatarLargeText}>
            {child.first_name[0]}{child.last_name[0]}
          </Text>
        </View>
        <Text style={styles.childName}>{child.first_name} {child.last_name}</Text>
        <Text style={styles.childAge}>{getAge(child.birth_date)} lat</Text>

        <View style={[styles.levelBadgeLarge, { backgroundColor: levelStyle.bg }]}>
          <Text style={styles.levelIcon}>{levelStyle.icon}</Text>
          <Text style={[styles.levelTextLarge, { color: levelStyle.text }]}>
            {child.swimming_level}
          </Text>
        </View>

        <View style={styles.pointsContainer}>
          <Ionicons name="star" size={20} color="#f59e0b" />
          <Text style={styles.pointsText}>{child.total_points} punktów</Text>
        </View>
      </View>

      {/* Medical Notes Warning */}
      {child.medical_notes && (
        <View style={styles.medicalWarning}>
          <Ionicons name="medical" size={20} color="#dc2626" />
          <View style={styles.medicalContent}>
            <Text style={styles.medicalTitle}>Uwagi medyczne</Text>
            <Text style={styles.medicalText}>{child.medical_notes}</Text>
          </View>
        </View>
      )}

      {/* Tabs */}
      <View style={styles.tabsContainer}>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'info' && styles.tabActive]}
          onPress={() => setActiveTab('info')}
        >
          <Text style={[styles.tabText, activeTab === 'info' && styles.tabTextActive]}>
            Info
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'courses' && styles.tabActive]}
          onPress={() => setActiveTab('courses')}
        >
          <Text style={[styles.tabText, activeTab === 'courses' && styles.tabTextActive]}>
            Kursy
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'achievements' && styles.tabActive]}
          onPress={() => setActiveTab('achievements')}
        >
          <Text style={[styles.tabText, activeTab === 'achievements' && styles.tabTextActive]}>
            Odznaki
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'attendance' && styles.tabActive]}
          onPress={() => setActiveTab('attendance')}
        >
          <Text style={[styles.tabText, activeTab === 'attendance' && styles.tabTextActive]}>
            Obecność
          </Text>
        </TouchableOpacity>
      </View>

      {/* Tab Content */}
      <View style={styles.tabContent}>
        {/* Info Tab */}
        {activeTab === 'info' && (
          <View style={styles.infoTab}>
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#eff6ff' }]}>
                <Ionicons name="calendar" size={20} color="#3b82f6" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Data urodzenia</Text>
                <Text style={styles.infoValue}>
                  {format(new Date(child.birth_date), 'd MMMM yyyy', { locale: pl })}
                </Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#dcfce7' }]}>
                <Ionicons name="water" size={20} color="#16a34a" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Poziom pływania</Text>
                <Text style={styles.infoValue}>{child.swimming_level}</Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#fef3c7' }]}>
                <Ionicons name="school" size={20} color="#f59e0b" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Aktywne kursy</Text>
                <Text style={styles.infoValue}>{child.courses.length}</Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#f3e8ff' }]}>
                <Ionicons name="trophy" size={20} color="#9333ea" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Zdobyte odznaki</Text>
                <Text style={styles.infoValue}>{child.achievements.length}</Text>
              </View>
            </View>
          </View>
        )}

        {/* Courses Tab */}
        {activeTab === 'courses' && (
          <View style={styles.coursesTab}>
            {child.courses.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="school-outline" size={48} color="#d1d5db" />
                <Text style={styles.emptyText}>Brak aktywnych kursów</Text>
              </View>
            ) : (
              child.courses.map((course) => (
                <View key={course.id} style={styles.courseCard}>
                  <View style={styles.courseHeader}>
                    <Text style={styles.courseName}>{course.name}</Text>
                    <View style={styles.sessionsBadge}>
                      <Text style={styles.sessionsText}>
                        {course.sessions_remaining}/{course.sessions_total}
                      </Text>
                    </View>
                  </View>

                  <View style={styles.courseDetails}>
                    <View style={styles.courseDetail}>
                      <Ionicons name="person" size={14} color="#6b7280" />
                      <Text style={styles.courseDetailText}>{course.instructor_name}</Text>
                    </View>
                    <View style={styles.courseDetail}>
                      <Ionicons name="calendar" size={14} color="#6b7280" />
                      <Text style={styles.courseDetailText}>{getDayName(course.day_of_week)}</Text>
                    </View>
                    <View style={styles.courseDetail}>
                      <Ionicons name="time" size={14} color="#6b7280" />
                      <Text style={styles.courseDetailText}>
                        {course.time_start.substring(0, 5)} - {course.time_end.substring(0, 5)}
                      </Text>
                    </View>
                    <View style={styles.courseDetail}>
                      <Ionicons name="location" size={14} color="#6b7280" />
                      <Text style={styles.courseDetailText}>{course.facility_name}</Text>
                    </View>
                  </View>

                  {/* Progress Bar */}
                  <View style={styles.progressContainer}>
                    <View style={styles.progressBar}>
                      <View
                        style={[
                          styles.progressFill,
                          {
                            width: `${((course.sessions_total - course.sessions_remaining) / course.sessions_total) * 100}%`,
                          },
                        ]}
                      />
                    </View>
                    <Text style={styles.progressText}>
                      {course.sessions_total - course.sessions_remaining} / {course.sessions_total} zajęć
                    </Text>
                  </View>
                </View>
              ))
            )}
          </View>
        )}

        {/* Achievements Tab */}
        {activeTab === 'achievements' && (
          <View style={styles.achievementsTab}>
            {child.achievements.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="trophy-outline" size={48} color="#d1d5db" />
                <Text style={styles.emptyText}>Jeszcze brak odznak</Text>
                <Text style={styles.emptySubtext}>Odznaki pojawią się tu gdy dziecko je zdobędzie</Text>
              </View>
            ) : (
              <View style={styles.achievementsGrid}>
                {child.achievements.map((achievement) => (
                  <View key={achievement.id} style={styles.achievementCard}>
                    <Text style={styles.achievementIcon}>{achievement.icon}</Text>
                    <Text style={styles.achievementName}>{achievement.name}</Text>
                    <Text style={styles.achievementDesc} numberOfLines={2}>
                      {achievement.description}
                    </Text>
                    <View style={styles.achievementFooter}>
                      <View style={styles.achievementPoints}>
                        <Ionicons name="star" size={12} color="#f59e0b" />
                        <Text style={styles.achievementPointsText}>+{achievement.points}</Text>
                      </View>
                      <Text style={styles.achievementDate}>
                        {format(new Date(achievement.earned_at), 'd MMM', { locale: pl })}
                      </Text>
                    </View>
                  </View>
                ))}
              </View>
            )}
          </View>
        )}

        {/* Attendance Tab */}
        {activeTab === 'attendance' && (
          <View style={styles.attendanceTab}>
            {child.recent_attendance.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="checkmark-circle-outline" size={48} color="#d1d5db" />
                <Text style={styles.emptyText}>Brak historii obecności</Text>
              </View>
            ) : (
              child.recent_attendance.map((record) => {
                const statusInfo = ATTENDANCE_STATUS[record.status];
                return (
                  <View key={record.id} style={styles.attendanceRow}>
                    <View style={styles.attendanceDate}>
                      <Text style={styles.attendanceDateDay}>
                        {format(new Date(record.session_date), 'd', { locale: pl })}
                      </Text>
                      <Text style={styles.attendanceDateMonth}>
                        {format(new Date(record.session_date), 'MMM', { locale: pl })}
                      </Text>
                    </View>
                    <View style={styles.attendanceInfo}>
                      <Text style={styles.attendanceClass}>{record.class_name}</Text>
                      <Text style={styles.attendanceDayName}>
                        {format(new Date(record.session_date), 'EEEE', { locale: pl })}
                      </Text>
                    </View>
                    <View style={[styles.attendanceStatus, { backgroundColor: statusInfo.bg }]}>
                      <Text style={[styles.attendanceStatusText, { color: statusInfo.color }]}>
                        {statusInfo.label}
                      </Text>
                    </View>
                  </View>
                );
              })
            )}
          </View>
        )}
      </View>

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
  errorText: {
    fontSize: 16,
    color: '#6b7280',
  },
  headerCard: {
    backgroundColor: '#3b82f6',
    paddingTop: 24,
    paddingBottom: 32,
    alignItems: 'center',
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
  },
  avatarLarge: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 4,
  },
  avatarLargeText: {
    fontSize: 36,
    fontWeight: '700',
    color: '#3b82f6',
  },
  childName: {
    fontSize: 26,
    fontWeight: '700',
    color: '#fff',
  },
  childAge: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 4,
  },
  levelBadgeLarge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 24,
    marginTop: 16,
    gap: 8,
  },
  levelIcon: {
    fontSize: 20,
  },
  levelTextLarge: {
    fontSize: 16,
    fontWeight: '600',
  },
  pointsContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 12,
    gap: 6,
  },
  pointsText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#fff',
  },
  medicalWarning: {
    flexDirection: 'row',
    backgroundColor: '#fef2f2',
    margin: 16,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#fecaca',
  },
  medicalContent: {
    flex: 1,
    marginLeft: 12,
  },
  medicalTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#dc2626',
  },
  medicalText: {
    fontSize: 14,
    color: '#7f1d1d',
    marginTop: 4,
  },
  tabsContainer: {
    flexDirection: 'row',
    marginHorizontal: 16,
    marginTop: 16,
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 4,
  },
  tab: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
    borderRadius: 12,
  },
  tabActive: {
    backgroundColor: '#3b82f6',
  },
  tabText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#6b7280',
  },
  tabTextActive: {
    color: '#fff',
  },
  tabContent: {
    padding: 16,
  },
  // Info Tab
  infoTab: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 8,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
  },
  infoIcon: {
    width: 44,
    height: 44,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  infoContent: {
    marginLeft: 14,
  },
  infoLabel: {
    fontSize: 12,
    color: '#6b7280',
  },
  infoValue: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
    marginTop: 2,
  },
  // Courses Tab
  coursesTab: {
    gap: 16,
  },
  courseCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
  },
  courseHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  courseName: {
    fontSize: 17,
    fontWeight: '600',
    color: '#111827',
    flex: 1,
  },
  sessionsBadge: {
    backgroundColor: '#eff6ff',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  sessionsText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#3b82f6',
  },
  courseDetails: {
    gap: 8,
  },
  courseDetail: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  courseDetailText: {
    fontSize: 14,
    color: '#6b7280',
  },
  progressContainer: {
    marginTop: 16,
  },
  progressBar: {
    height: 6,
    backgroundColor: '#e5e7eb',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#3b82f6',
    borderRadius: 3,
  },
  progressText: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 6,
    textAlign: 'right',
  },
  // Achievements Tab
  achievementsTab: {},
  achievementsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  achievementCard: {
    width: '47%',
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
  },
  achievementIcon: {
    fontSize: 40,
    marginBottom: 8,
  },
  achievementName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#111827',
    textAlign: 'center',
  },
  achievementDesc: {
    fontSize: 12,
    color: '#6b7280',
    textAlign: 'center',
    marginTop: 4,
  },
  achievementFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: '100%',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: '#f3f4f6',
  },
  achievementPoints: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  achievementPointsText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#f59e0b',
  },
  achievementDate: {
    fontSize: 12,
    color: '#9ca3af',
  },
  // Attendance Tab
  attendanceTab: {
    gap: 8,
  },
  attendanceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
  },
  attendanceDate: {
    width: 48,
    height: 48,
    backgroundColor: '#eff6ff',
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  attendanceDateDay: {
    fontSize: 18,
    fontWeight: '700',
    color: '#3b82f6',
  },
  attendanceDateMonth: {
    fontSize: 11,
    color: '#3b82f6',
    textTransform: 'uppercase',
  },
  attendanceInfo: {
    flex: 1,
    marginLeft: 12,
  },
  attendanceClass: {
    fontSize: 15,
    fontWeight: '500',
    color: '#111827',
  },
  attendanceDayName: {
    fontSize: 13,
    color: '#6b7280',
    marginTop: 2,
    textTransform: 'capitalize',
  },
  attendanceStatus: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 12,
  },
  attendanceStatusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  // Empty State
  emptyState: {
    alignItems: 'center',
    padding: 32,
    backgroundColor: '#fff',
    borderRadius: 16,
  },
  emptyText: {
    fontSize: 16,
    fontWeight: '500',
    color: '#6b7280',
    marginTop: 12,
  },
  emptySubtext: {
    fontSize: 14,
    color: '#9ca3af',
    marginTop: 4,
    textAlign: 'center',
  },
});
