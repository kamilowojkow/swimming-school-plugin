import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { View, TouchableOpacity, Text, StyleSheet, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuthStore } from '../store/authStore';
import { useNotificationStore } from '../store/notificationStore';
import { useSettingsStore, useThemeColors } from '../store/settingsStore';
import RoleSwitcher from '../components/RoleSwitcher';

// Auth Screens
import LoginScreen from '../screens/auth/LoginScreen';
import ForgotPasswordScreen from '../screens/auth/ForgotPasswordScreen';

// Parent Screens
import ParentDashboard from '../screens/parent/DashboardScreen';
import ChildrenScreen from '../screens/parent/ChildrenScreen';
import ChildDetailsScreen from '../screens/parent/ChildDetailsScreen';
import ParentScheduleScreen from '../screens/parent/ScheduleScreen';
import PaymentsScreen from '../screens/parent/PaymentsScreen';
import AbsencesScreen from '../screens/parent/AbsencesScreen';

// Instructor Screens
import InstructorDashboard from '../screens/instructor/DashboardScreen';
import InstructorScheduleScreen from '../screens/instructor/ScheduleScreen';
import AttendanceScreen from '../screens/instructor/AttendanceScreen';
import SubstitutionsScreen from '../screens/instructor/SubstitutionsScreen';
import SalaryScreen from '../screens/instructor/SalaryScreen';

// Common Screens
import NotificationsScreen from '../screens/common/NotificationsScreen';
import ProfileScreen from '../screens/common/ProfileScreen';
import SettingsScreen from '../screens/common/SettingsScreen';
import SplashScreen from '../screens/SplashScreen';

// Types
export type RootStackParamList = {
  Auth: undefined;
  ParentTabs: undefined;
  InstructorTabs: undefined;
  ChildDetails: { childId: number };
  Payments: undefined;
  Attendance: { sessionId: number };
  Notifications: undefined;
  Profile: undefined;
  Settings: undefined;
};

export type AuthStackParamList = {
  Login: undefined;
  ForgotPassword: undefined;
};

export type ParentTabParamList = {
  Dashboard: undefined;
  Children: undefined;
  Schedule: undefined;
  Absences: undefined;
  More: undefined;
};

export type InstructorTabParamList = {
  Dashboard: undefined;
  Schedule: undefined;
  Substitutions: undefined;
  Salary: undefined;
  More: undefined;
};

const RootStack = createNativeStackNavigator<RootStackParamList>();
const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const ParentTabs = createBottomTabNavigator<ParentTabParamList>();
const InstructorTabs = createBottomTabNavigator<InstructorTabParamList>();

// ============================================
// AUTH NAVIGATOR
// ============================================

function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
      <AuthStack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
    </AuthStack.Navigator>
  );
}

// ============================================
// PARENT TAB NAVIGATOR
// ============================================

function ParentTabNavigator() {
  const unreadCount = useNotificationStore((state) => state.unreadCount);
  const { t, isDark } = useSettingsStore();
  const colors = useThemeColors('parent');

  return (
    <ParentTabs.Navigator
      screenOptions={({ route }) => ({
        tabBarIcon: ({ focused, color, size }) => {
          let iconName: keyof typeof Ionicons.glyphMap;

          switch (route.name) {
            case 'Dashboard':
              iconName = focused ? 'home' : 'home-outline';
              break;
            case 'Children':
              iconName = focused ? 'people' : 'people-outline';
              break;
            case 'Schedule':
              iconName = focused ? 'calendar' : 'calendar-outline';
              break;
            case 'Absences':
              iconName = focused ? 'close-circle' : 'close-circle-outline';
              break;
            case 'More':
              iconName = focused ? 'menu' : 'menu-outline';
              break;
            default:
              iconName = 'ellipse';
          }

          return <Ionicons name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.tabBarInactive,
        tabBarStyle: {
          backgroundColor: colors.tabBarBackground,
          borderTopColor: colors.tabBarBorder,
        },
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: '#fff',
      })}
    >
      <ParentTabs.Screen
        name="Dashboard"
        component={ParentDashboard}
        options={{ title: t.nav.dashboard }}
      />
      <ParentTabs.Screen
        name="Children"
        component={ChildrenScreen}
        options={{ title: t.nav.children }}
      />
      <ParentTabs.Screen
        name="Schedule"
        component={ParentScheduleScreen}
        options={{ title: t.nav.schedule }}
      />
      <ParentTabs.Screen
        name="Absences"
        component={AbsencesScreen}
        options={{ title: t.absences?.title || 'Nieobecności' }}
      />
      <ParentTabs.Screen
        name="More"
        component={MoreParentScreen}
        options={{
          title: t.nav.more,
          tabBarBadge: unreadCount > 0 ? unreadCount : undefined,
        }}
      />
    </ParentTabs.Navigator>
  );
}

// ============================================
// INSTRUCTOR TAB NAVIGATOR
// ============================================

function InstructorTabNavigator() {
  const unreadCount = useNotificationStore((state) => state.unreadCount);
  const { t, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');

  return (
    <InstructorTabs.Navigator
      screenOptions={({ route }) => ({
        tabBarIcon: ({ focused, color, size }) => {
          let iconName: keyof typeof Ionicons.glyphMap;

          switch (route.name) {
            case 'Dashboard':
              iconName = focused ? 'home' : 'home-outline';
              break;
            case 'Schedule':
              iconName = focused ? 'calendar' : 'calendar-outline';
              break;
            case 'Substitutions':
              iconName = focused ? 'swap-horizontal' : 'swap-horizontal-outline';
              break;
            case 'Salary':
              iconName = focused ? 'wallet' : 'wallet-outline';
              break;
            case 'More':
              iconName = focused ? 'menu' : 'menu-outline';
              break;
            default:
              iconName = 'ellipse';
          }

          return <Ionicons name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: colors.secondary,
        tabBarInactiveTintColor: colors.tabBarInactive,
        tabBarStyle: {
          backgroundColor: colors.tabBarBackground,
          borderTopColor: colors.tabBarBorder,
        },
        headerStyle: { backgroundColor: colors.secondary },
        headerTintColor: '#fff',
      })}
    >
      <InstructorTabs.Screen
        name="Dashboard"
        component={InstructorDashboard}
        options={{ title: t.nav.dashboard }}
      />
      <InstructorTabs.Screen
        name="Schedule"
        component={InstructorScheduleScreen}
        options={{ title: t.nav.schedule }}
      />
      <InstructorTabs.Screen
        name="Substitutions"
        component={SubstitutionsScreen}
        options={{ title: t.nav.substitutions }}
      />
      <InstructorTabs.Screen
        name="Salary"
        component={SalaryScreen}
        options={{ title: t.nav.salary }}
      />
      <InstructorTabs.Screen
        name="More"
        component={MoreInstructorScreen}
        options={{
          title: t.nav.more,
          tabBarBadge: unreadCount > 0 ? unreadCount : undefined,
        }}
      />
    </InstructorTabs.Navigator>
  );
}

// ============================================
// MORE SCREENS (common menu)
// ============================================

function MoreParentScreen() {
  const navigation = useNavigation<any>();
  const logout = useAuthStore((state) => state.logout);
  const unreadCount = useNotificationStore((state) => state.unreadCount);
  const { t, isDark } = useSettingsStore();
  const colors = useThemeColors('parent');
  const styles = createMoreStyles(colors, isDark);

  return (
    <ScrollView style={styles.moreContainer}>
      <RoleSwitcher />

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Notifications')}
      >
        <Ionicons name="notifications-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.notifications}</Text>
        {unreadCount > 0 && (
          <View style={[styles.badge, { backgroundColor: colors.primary }]}>
            <Text style={styles.badgeText}>{unreadCount}</Text>
          </View>
        )}
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Profile')}
      >
        <Ionicons name="person-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.profile}</Text>
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Payments')}
      >
        <Ionicons name="card-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.payments}</Text>
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Settings')}
      >
        <Ionicons name="settings-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.settings}</Text>
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity style={[styles.menuItem, styles.logoutItem]} onPress={logout}>
        <Ionicons name="log-out-outline" size={24} color={colors.error} />
        <Text style={[styles.menuText, { color: colors.error }]}>{t.auth.logout}</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

function MoreInstructorScreen() {
  const navigation = useNavigation<any>();
  const logout = useAuthStore((state) => state.logout);
  const unreadCount = useNotificationStore((state) => state.unreadCount);
  const { t, isDark } = useSettingsStore();
  const colors = useThemeColors('instructor');
  const styles = createMoreStyles(colors, isDark);

  return (
    <ScrollView style={styles.moreContainer}>
      <RoleSwitcher />

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Notifications')}
      >
        <Ionicons name="notifications-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.notifications}</Text>
        {unreadCount > 0 && (
          <View style={[styles.badge, { backgroundColor: colors.secondary }]}>
            <Text style={styles.badgeText}>{unreadCount}</Text>
          </View>
        )}
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Profile')}
      >
        <Ionicons name="person-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.profile}</Text>
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Settings')}
      >
        <Ionicons name="settings-outline" size={24} color={colors.text} />
        <Text style={styles.menuText}>{t.nav.settings}</Text>
        <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
      </TouchableOpacity>

      <TouchableOpacity style={[styles.menuItem, styles.logoutItem]} onPress={logout}>
        <Ionicons name="log-out-outline" size={24} color={colors.error} />
        <Text style={[styles.menuText, { color: colors.error }]}>{t.auth.logout}</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const createMoreStyles = (colors: ReturnType<typeof useThemeColors>, isDark: boolean) =>
  StyleSheet.create({
    moreContainer: {
      flex: 1,
      backgroundColor: colors.background,
      paddingTop: 16,
    },
    menuItem: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      paddingVertical: 16,
      paddingHorizontal: 20,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    menuText: {
      flex: 1,
      marginLeft: 16,
      fontSize: 16,
      color: colors.text,
    },
    badge: {
      borderRadius: 12,
      paddingHorizontal: 8,
      paddingVertical: 2,
      marginRight: 8,
    },
    badgeText: {
      color: '#fff',
      fontSize: 12,
      fontWeight: '600',
    },
    logoutItem: {
      marginTop: 24,
    },
  });

// ============================================
// ROOT NAVIGATOR
// ============================================

export default function AppNavigator() {
  const { isAuthenticated, isLoading, activeRole } = useAuthStore();
  const { t } = useSettingsStore();
  const colors = useThemeColors(activeRole);

  if (isLoading) {
    return <SplashScreen />;
  }

  const accentColor = activeRole === 'instructor' ? colors.secondary : colors.primary;

  return (
    <NavigationContainer>
      <RootStack.Navigator screenOptions={{ headerShown: false }}>
        {!isAuthenticated ? (
          <RootStack.Screen name="Auth" component={AuthNavigator} />
        ) : activeRole === 'instructor' ? (
          <>
            <RootStack.Screen name="InstructorTabs" component={InstructorTabNavigator} />
            <RootStack.Screen
              name="Attendance"
              component={AttendanceScreen}
              options={{
                headerShown: true,
                title: t.nav.attendance,
                headerStyle: { backgroundColor: colors.secondary },
                headerTintColor: '#fff',
              }}
            />
          </>
        ) : (
          <>
            <RootStack.Screen name="ParentTabs" component={ParentTabNavigator} />
            <RootStack.Screen
              name="ChildDetails"
              component={ChildDetailsScreen}
              options={{
                headerShown: true,
                title: t.children.title,
                headerStyle: { backgroundColor: colors.primary },
                headerTintColor: '#fff',
              }}
            />
            <RootStack.Screen
              name="Payments"
              component={PaymentsScreen}
              options={{
                headerShown: true,
                title: t.nav.payments,
                headerStyle: { backgroundColor: colors.primary },
                headerTintColor: '#fff',
              }}
            />
          </>
        )}
        <RootStack.Screen
          name="Notifications"
          component={NotificationsScreen}
          options={{
            headerShown: true,
            title: t.nav.notifications,
            headerStyle: { backgroundColor: accentColor },
            headerTintColor: '#fff',
          }}
        />
        <RootStack.Screen
          name="Profile"
          component={ProfileScreen}
          options={{
            headerShown: true,
            title: t.nav.profile,
            headerStyle: { backgroundColor: accentColor },
            headerTintColor: '#fff',
          }}
        />
        <RootStack.Screen
          name="Settings"
          component={SettingsScreen}
          options={{
            headerShown: true,
            title: t.nav.settings,
            headerStyle: { backgroundColor: accentColor },
            headerTintColor: '#fff',
          }}
        />
      </RootStack.Navigator>
    </NavigationContainer>
  );
}
