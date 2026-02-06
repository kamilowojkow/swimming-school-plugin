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
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

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
  // Without Polish diacritics
  'Zolwik': { bg: '#fef9c3', text: '#ca8a04', icon: '🐢' },
  'Delfinek': { bg: '#dbeafe', text: '#2563eb', icon: '🐬' },
  'Rekin': { bg: '#dcfce7', text: '#16a34a', icon: '🦈' },
  'Mistrz': { bg: '#f3e8ff', text: '#9333ea', icon: '🏆' },
  // With Polish diacritics
  'Żółwik': { bg: '#fef9c3', text: '#ca8a04', icon: '🐢' },
  'Żołwik': { bg: '#fef9c3', text: '#ca8a04', icon: '🐢' },
};

export default function ChildDetailsScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { childId } = route.params;

  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();
  const dateLocale = language === 'pl' ? pl : enUS;

  const [child, setChild] = useState<ChildDetails | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<'info' | 'courses' | 'achievements' | 'attendance'>('info');

  const ATTENDANCE_STATUS: Record<string, { label: string; color: string; bg: string }> = {
    present: { label: t.attendance.present, color: colors.success, bg: colors.successLight },
    absent: { label: t.attendance.absent, color: colors.error, bg: colors.errorLight },
    late: { label: t.attendance.late, color: colors.warning, bg: colors.warningLight },
    excused: { label: t.attendance.excused, color: colors.textSecondary, bg: colors.surfaceSecondary },
  };

  // Normalize course data from API
  const normalizeCourse = (data: any): Course => ({
    id: data.id || 0,
    name: data.name || data.course_name || data.courseName || data.class_name || data.className || '',
    instructor_name: data.instructor_name || data.instructorName || data.instructor || data.teacher || data.teacher_name || '',
    day_of_week: data.day_of_week || data.dayOfWeek || data.day || data.weekday || '',
    time_start: data.time_start || data.timeStart || data.start_time || data.startTime || data.start || '',
    time_end: data.time_end || data.timeEnd || data.end_time || data.endTime || data.end || '',
    facility_name: data.facility_name || data.facilityName || data.facility || data.location || data.pool || '',
    sessions_remaining: data.sessions_remaining ?? data.sessionsRemaining ?? data.remaining ?? data.lessons_left ?? 0,
    sessions_total: data.sessions_total ?? data.sessionsTotal ?? data.total ?? data.total_lessons ?? 0,
  });

  // Normalize achievement data from API
  const normalizeAchievement = (data: any): Achievement => ({
    id: data.id || 0,
    name: data.name || data.title || data.badge_name || data.badgeName || '',
    description: data.description || data.desc || '',
    icon: data.icon || data.emoji || data.badge_icon || '🏅',
    earned_at: data.earned_at || data.earnedAt || data.date || data.awarded_at || data.created_at || '',
    points: data.points ?? data.value ?? data.score ?? 0,
  });

  // Normalize attendance record from API
  const normalizeAttendance = (data: any): AttendanceRecord => ({
    id: data.id || 0,
    session_date: data.session_date || data.sessionDate || data.date || '',
    status: data.status || 'present',
    class_name: data.class_name || data.className || data.course_name || data.courseName || data.name || '',
  });

  // Normalize child details from API
  const normalizeChildDetails = (data: any): ChildDetails => {
    const coursesRaw = data.courses || data.enrollments || data.classes || [];
    const achievementsRaw = data.achievements || data.badges || data.awards || [];
    const attendanceRaw = data.recent_attendance || data.recentAttendance || data.attendance || data.attendance_history || [];

    return {
      id: data.id,
      first_name: data.first_name || data.firstName || data.name?.split(' ')[0] || '',
      last_name: data.last_name || data.lastName || data.name?.split(' ')[1] || '',
      birth_date: data.birth_date || data.birthDate || data.date_of_birth || data.dob || '',
      swimming_level: data.swimming_level || data.swimmingLevel || data.level || data.skill_level || data.skillLevel || '',
      total_points: data.total_points ?? data.totalPoints ?? data.points ?? 0,
      medical_notes: data.medical_notes || data.medicalNotes || data.health_notes || undefined,
      courses: Array.isArray(coursesRaw) ? coursesRaw.map(normalizeCourse) : [],
      achievements: Array.isArray(achievementsRaw) ? achievementsRaw.map(normalizeAchievement) : [],
      recent_attendance: Array.isArray(attendanceRaw) ? attendanceRaw.map(normalizeAttendance) : [],
    };
  };

  const fetchChildDetails = async () => {
    try {
      const data = await api.getChildDetails(childId);
      const normalizedChild = normalizeChildDetails(data);
      setChild(normalizedChild);
      console.log('Child details loaded:', normalizedChild);
    } catch (error) {
      console.error('Error fetching child details:', error);
      Alert.alert(t.common.error, language === 'pl' ? 'Nie udalo sie pobrac danych dziecka' : 'Failed to load child data');
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
    return LEVEL_COLORS[level] || { bg: colors.surfaceSecondary, text: colors.textSecondary, icon: '🏊' };
  };

  const getDayName = (day: string | undefined) => {
    if (!day) return '';
    const days: Record<string, { pl: string; en: string }> = {
      monday: { pl: 'Poniedzialek', en: 'Monday' },
      tuesday: { pl: 'Wtorek', en: 'Tuesday' },
      wednesday: { pl: 'Sroda', en: 'Wednesday' },
      thursday: { pl: 'Czwartek', en: 'Thursday' },
      friday: { pl: 'Piatek', en: 'Friday' },
      saturday: { pl: 'Sobota', en: 'Saturday' },
      sunday: { pl: 'Niedziela', en: 'Sunday' },
    };
    const dayInfo = days[day.toLowerCase()];
    return dayInfo ? dayInfo[language] : day;
  };

  const styles = createStyles(colors, isDark);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  if (!child) {
    return (
      <View style={styles.loadingContainer}>
        <Text style={styles.errorText}>
          {language === 'pl' ? 'Nie znaleziono dziecka' : 'Child not found'}
        </Text>
      </View>
    );
  }

  const levelStyle = getLevelStyle(child.swimming_level);

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
      }
    >
      {/* Header Card */}
      <View style={styles.headerCard}>
        <View style={styles.avatarLarge}>
          <Text style={styles.avatarLargeText}>
            {(child.first_name || '')[0] || '?'}{(child.last_name || '')[0] || '?'}
          </Text>
        </View>
        <Text style={styles.childName}>{child.first_name || ''} {child.last_name || ''}</Text>
        {child.birth_date && (
          <Text style={styles.childAge}>
            {getAge(child.birth_date)} {language === 'pl' ? 'lat' : 'years'}
          </Text>
        )}

        {child.swimming_level && (
          <View style={[styles.levelBadgeLarge, { backgroundColor: levelStyle.bg }]}>
            <Text style={styles.levelIcon}>{levelStyle.icon}</Text>
            <Text style={[styles.levelTextLarge, { color: levelStyle.text }]}>
              {child.swimming_level}
            </Text>
          </View>
        )}

        <View style={styles.pointsContainer}>
          <Ionicons name="star" size={20} color={colors.warning} />
          <Text style={styles.pointsText}>
            {child.total_points || 0} {language === 'pl' ? 'punktow' : 'points'}
          </Text>
        </View>
      </View>

      {/* Medical Notes Warning */}
      {child.medical_notes && (
        <View style={styles.medicalWarning}>
          <Ionicons name="medical" size={20} color={colors.error} />
          <View style={styles.medicalContent}>
            <Text style={styles.medicalTitle}>{t.attendance.medicalNotes}</Text>
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
            {t.children.courses}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'achievements' && styles.tabActive]}
          onPress={() => setActiveTab('achievements')}
        >
          <Text style={[styles.tabText, activeTab === 'achievements' && styles.tabTextActive]}>
            {language === 'pl' ? 'Odznaki' : 'Badges'}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'attendance' && styles.tabActive]}
          onPress={() => setActiveTab('attendance')}
        >
          <Text style={[styles.tabText, activeTab === 'attendance' && styles.tabTextActive]}>
            {language === 'pl' ? 'Obecnosc' : 'Attendance'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* Tab Content */}
      <View style={styles.tabContent}>
        {/* Info Tab */}
        {activeTab === 'info' && (
          <View style={styles.infoTab}>
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="calendar" size={20} color={colors.primary} />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>
                  {language === 'pl' ? 'Data urodzenia' : 'Birth date'}
                </Text>
                <Text style={styles.infoValue}>
                  {format(new Date(child.birth_date), 'd MMMM yyyy', { locale: dateLocale })}
                </Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: colors.successLight }]}>
                <Ionicons name="water" size={20} color={colors.success} />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>{t.children.level}</Text>
                <Text style={styles.infoValue}>{child.swimming_level}</Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: colors.warningLight }]}>
                <Ionicons name="school" size={20} color={colors.warning} />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>
                  {language === 'pl' ? 'Aktywne kursy' : 'Active courses'}
                </Text>
                <Text style={styles.infoValue}>{child.courses.length}</Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#f3e8ff' }]}>
                <Ionicons name="trophy" size={20} color="#9333ea" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>
                  {language === 'pl' ? 'Zdobyte odznaki' : 'Earned badges'}
                </Text>
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
                <Ionicons name="school-outline" size={48} color={colors.textTertiary} />
                <Text style={styles.emptyText}>
                  {language === 'pl' ? 'Brak aktywnych kursow' : 'No active courses'}
                </Text>
              </View>
            ) : (
              child.courses.map((course) => (
                <View key={course.id} style={styles.courseCard}>
                  <View style={styles.courseHeader}>
                    <Text style={styles.courseName}>{course.name || (language === 'pl' ? 'Kurs' : 'Course')}</Text>
                    {typeof course.sessions_remaining === 'number' && typeof course.sessions_total === 'number' && (
                      <View style={styles.sessionsBadge}>
                        <Text style={styles.sessionsText}>
                          {course.sessions_remaining}/{course.sessions_total}
                        </Text>
                      </View>
                    )}
                  </View>

                  <View style={styles.courseDetails}>
                    {course.instructor_name && (
                      <View style={styles.courseDetail}>
                        <Ionicons name="person" size={14} color={colors.textSecondary} />
                        <Text style={styles.courseDetailText}>{course.instructor_name}</Text>
                      </View>
                    )}
                    {course.day_of_week && (
                      <View style={styles.courseDetail}>
                        <Ionicons name="calendar" size={14} color={colors.textSecondary} />
                        <Text style={styles.courseDetailText}>{getDayName(course.day_of_week)}</Text>
                      </View>
                    )}
                    {(course.time_start || course.time_end) && (
                      <View style={styles.courseDetail}>
                        <Ionicons name="time" size={14} color={colors.textSecondary} />
                        <Text style={styles.courseDetailText}>
                          {course.time_start?.substring(0, 5) || '--:--'} - {course.time_end?.substring(0, 5) || '--:--'}
                        </Text>
                      </View>
                    )}
                    {course.facility_name && (
                      <View style={styles.courseDetail}>
                        <Ionicons name="location" size={14} color={colors.textSecondary} />
                        <Text style={styles.courseDetailText}>{course.facility_name}</Text>
                      </View>
                    )}
                  </View>

                  {/* Progress Bar */}
                  {typeof course.sessions_total === 'number' && course.sessions_total > 0 && (
                    <View style={styles.progressContainer}>
                      <View style={styles.progressBar}>
                        <View
                          style={[
                            styles.progressFill,
                            {
                              width: `${(((course.sessions_total || 0) - (course.sessions_remaining || 0)) / (course.sessions_total || 1)) * 100}%`,
                            },
                          ]}
                        />
                      </View>
                      <Text style={styles.progressText}>
                        {(course.sessions_total || 0) - (course.sessions_remaining || 0)} / {course.sessions_total || 0} {language === 'pl' ? 'zajec' : 'sessions'}
                      </Text>
                    </View>
                  )}
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
                <Ionicons name="trophy-outline" size={48} color={colors.textTertiary} />
                <Text style={styles.emptyText}>
                  {language === 'pl' ? 'Jeszcze brak odznak' : 'No badges yet'}
                </Text>
                <Text style={styles.emptySubtext}>
                  {language === 'pl' ? 'Odznaki pojawia sie tu gdy dziecko je zdobedzie' : 'Badges will appear here when earned'}
                </Text>
              </View>
            ) : (
              <View style={styles.achievementsGrid}>
                {child.achievements.map((achievement) => (
                  <View key={achievement.id} style={styles.achievementCard}>
                    <Text style={styles.achievementIcon}>{achievement.icon || '🏅'}</Text>
                    <Text style={styles.achievementName}>{achievement.name || ''}</Text>
                    <Text style={styles.achievementDesc} numberOfLines={2}>
                      {achievement.description || ''}
                    </Text>
                    <View style={styles.achievementFooter}>
                      <View style={styles.achievementPoints}>
                        <Ionicons name="star" size={12} color={colors.warning} />
                        <Text style={styles.achievementPointsText}>+{achievement.points || 0}</Text>
                      </View>
                      <Text style={styles.achievementDate}>
                        {achievement.earned_at ? format(new Date(achievement.earned_at), 'd MMM', { locale: dateLocale }) : ''}
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
                <Ionicons name="checkmark-circle-outline" size={48} color={colors.textTertiary} />
                <Text style={styles.emptyText}>
                  {language === 'pl' ? 'Brak historii obecnosci' : 'No attendance history'}
                </Text>
              </View>
            ) : (
              child.recent_attendance.map((record) => {
                const statusInfo = ATTENDANCE_STATUS[record.status] || ATTENDANCE_STATUS.present;
                const sessionDate = record.session_date ? new Date(record.session_date) : new Date();
                return (
                  <View key={record.id} style={styles.attendanceRow}>
                    <View style={styles.attendanceDate}>
                      <Text style={styles.attendanceDateDay}>
                        {format(sessionDate, 'd', { locale: dateLocale })}
                      </Text>
                      <Text style={styles.attendanceDateMonth}>
                        {format(sessionDate, 'MMM', { locale: dateLocale })}
                      </Text>
                    </View>
                    <View style={styles.attendanceInfo}>
                      <Text style={styles.attendanceClass}>{record.class_name || ''}</Text>
                      <Text style={styles.attendanceDayName}>
                        {format(sessionDate, 'EEEE', { locale: dateLocale })}
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
    errorText: {
      fontSize: 16,
      color: colors.textSecondary,
    },
    headerCard: {
      backgroundColor: colors.primary,
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
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.15,
      shadowRadius: 8,
      elevation: 4,
    },
    avatarLargeText: {
      fontSize: 36,
      fontWeight: '700',
      color: colors.primary,
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
      backgroundColor: colors.errorLight,
      margin: 16,
      padding: 16,
      borderRadius: 16,
      borderWidth: 1,
      borderColor: isDark ? colors.error : '#fecaca',
    },
    medicalContent: {
      flex: 1,
      marginLeft: 12,
    },
    medicalTitle: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.error,
    },
    medicalText: {
      fontSize: 14,
      color: isDark ? colors.error : '#7f1d1d',
      marginTop: 4,
    },
    tabsContainer: {
      flexDirection: 'row',
      marginHorizontal: 16,
      marginTop: 16,
      backgroundColor: colors.surface,
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
      backgroundColor: colors.primary,
    },
    tabText: {
      fontSize: 13,
      fontWeight: '600',
      color: colors.textSecondary,
    },
    tabTextActive: {
      color: '#fff',
    },
    tabContent: {
      padding: 16,
    },
    // Info Tab
    infoTab: {
      backgroundColor: colors.surface,
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
      color: colors.textSecondary,
    },
    infoValue: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
      marginTop: 2,
    },
    // Courses Tab
    coursesTab: {
      gap: 16,
    },
    courseCard: {
      backgroundColor: colors.surface,
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
      color: colors.text,
      flex: 1,
    },
    sessionsBadge: {
      backgroundColor: colors.primaryLight,
      paddingHorizontal: 10,
      paddingVertical: 4,
      borderRadius: 12,
    },
    sessionsText: {
      fontSize: 12,
      fontWeight: '600',
      color: colors.primary,
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
      color: colors.textSecondary,
    },
    progressContainer: {
      marginTop: 16,
    },
    progressBar: {
      height: 6,
      backgroundColor: colors.border,
      borderRadius: 3,
      overflow: 'hidden',
    },
    progressFill: {
      height: '100%',
      backgroundColor: colors.primary,
      borderRadius: 3,
    },
    progressText: {
      fontSize: 12,
      color: colors.textSecondary,
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
      backgroundColor: colors.surface,
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
      color: colors.text,
      textAlign: 'center',
    },
    achievementDesc: {
      fontSize: 12,
      color: colors.textSecondary,
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
      borderTopColor: colors.border,
    },
    achievementPoints: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 4,
    },
    achievementPointsText: {
      fontSize: 12,
      fontWeight: '600',
      color: colors.warning,
    },
    achievementDate: {
      fontSize: 12,
      color: colors.textTertiary,
    },
    // Attendance Tab
    attendanceTab: {
      gap: 8,
    },
    attendanceRow: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 12,
    },
    attendanceDate: {
      width: 48,
      height: 48,
      backgroundColor: colors.primaryLight,
      borderRadius: 12,
      justifyContent: 'center',
      alignItems: 'center',
    },
    attendanceDateDay: {
      fontSize: 18,
      fontWeight: '700',
      color: colors.primary,
    },
    attendanceDateMonth: {
      fontSize: 11,
      color: colors.primary,
      textTransform: 'uppercase',
    },
    attendanceInfo: {
      flex: 1,
      marginLeft: 12,
    },
    attendanceClass: {
      fontSize: 15,
      fontWeight: '500',
      color: colors.text,
    },
    attendanceDayName: {
      fontSize: 13,
      color: colors.textSecondary,
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
      backgroundColor: colors.surface,
      borderRadius: 16,
    },
    emptyText: {
      fontSize: 16,
      fontWeight: '500',
      color: colors.textSecondary,
      marginTop: 12,
    },
    emptySubtext: {
      fontSize: 14,
      color: colors.textTertiary,
      marginTop: 4,
      textAlign: 'center',
    },
  });
