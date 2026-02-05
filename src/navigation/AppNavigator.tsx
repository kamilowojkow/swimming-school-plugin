import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../store/authStore';
import { useNotificationStore } from '../store/notificationStore';

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

// Types
export type RootStackParamList = {
  Auth: undefined;
  ParentTabs: undefined;
  InstructorTabs: undefined;
  ChildDetails: { childId: number };
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
  Payments: undefined;
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
            case 'Payments':
              iconName = focused ? 'card' : 'card-outline';
              break;
            case 'More':
              iconName = focused ? 'menu' : 'menu-outline';
              break;
            default:
              iconName = 'ellipse';
          }

          return <Ionicons name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: '#3b82f6',
        tabBarInactiveTintColor: '#6b7280',
        headerStyle: { backgroundColor: '#3b82f6' },
        headerTintColor: '#fff',
      })}
    >
      <ParentTabs.Screen
        name="Dashboard"
        component={ParentDashboard}
        options={{ title: 'Panel główny' }}
      />
      <ParentTabs.Screen
        name="Children"
        component={ChildrenScreen}
        options={{ title: 'Dzieci' }}
      />
      <ParentTabs.Screen
        name="Schedule"
        component={ParentScheduleScreen}
        options={{ title: 'Harmonogram' }}
      />
      <ParentTabs.Screen
        name="Payments"
        component={PaymentsScreen}
        options={{ title: 'Płatności' }}
      />
      <ParentTabs.Screen
        name="More"
        component={MoreParentScreen}
        options={{
          title: 'Więcej',
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
        tabBarActiveTintColor: '#10b981',
        tabBarInactiveTintColor: '#6b7280',
        headerStyle: { backgroundColor: '#10b981' },
        headerTintColor: '#fff',
      })}
    >
      <InstructorTabs.Screen
        name="Dashboard"
        component={InstructorDashboard}
        options={{ title: 'Panel główny' }}
      />
      <InstructorTabs.Screen
        name="Schedule"
        component={InstructorScheduleScreen}
        options={{ title: 'Harmonogram' }}
      />
      <InstructorTabs.Screen
        name="Substitutions"
        component={SubstitutionsScreen}
        options={{ title: 'Zastępstwa' }}
      />
      <InstructorTabs.Screen
        name="Salary"
        component={SalaryScreen}
        options={{ title: 'Wynagrodzenie' }}
      />
      <InstructorTabs.Screen
        name="More"
        component={MoreInstructorScreen}
        options={{
          title: 'Więcej',
          tabBarBadge: unreadCount > 0 ? unreadCount : undefined,
        }}
      />
    </InstructorTabs.Navigator>
  );
}

// ============================================
// MORE SCREENS (common menu)
// ============================================

import { View, TouchableOpacity, Text, StyleSheet } from 'react-native';
import { useNavigation } from '@react-navigation/native';

function MoreParentScreen() {
  const navigation = useNavigation<any>();
  const logout = useAuthStore((state) => state.logout);
  const unreadCount = useNotificationStore((state) => state.unreadCount);

  return (
    <View style={styles.moreContainer}>
      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Notifications')}
      >
        <Ionicons name="notifications-outline" size={24} color="#374151" />
        <Text style={styles.menuText}>Powiadomienia</Text>
        {unreadCount > 0 && (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{unreadCount}</Text>
          </View>
        )}
        <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Profile')}
      >
        <Ionicons name="person-outline" size={24} color="#374151" />
        <Text style={styles.menuText}>Profil</Text>
        <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Settings')}
      >
        <Ionicons name="settings-outline" size={24} color="#374151" />
        <Text style={styles.menuText}>Ustawienia</Text>
        <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
      </TouchableOpacity>

      <TouchableOpacity style={[styles.menuItem, styles.logoutItem]} onPress={logout}>
        <Ionicons name="log-out-outline" size={24} color="#ef4444" />
        <Text style={[styles.menuText, styles.logoutText]}>Wyloguj</Text>
      </TouchableOpacity>
    </View>
  );
}

function MoreInstructorScreen() {
  const navigation = useNavigation<any>();
  const logout = useAuthStore((state) => state.logout);
  const unreadCount = useNotificationStore((state) => state.unreadCount);

  return (
    <View style={styles.moreContainer}>
      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Notifications')}
      >
        <Ionicons name="notifications-outline" size={24} color="#374151" />
        <Text style={styles.menuText}>Powiadomienia</Text>
        {unreadCount > 0 && (
          <View style={[styles.badge, { backgroundColor: '#10b981' }]}>
            <Text style={styles.badgeText}>{unreadCount}</Text>
          </View>
        )}
        <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Profile')}
      >
        <Ionicons name="person-outline" size={24} color="#374151" />
        <Text style={styles.menuText}>Profil</Text>
        <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.menuItem}
        onPress={() => navigation.navigate('Settings')}
      >
        <Ionicons name="settings-outline" size={24} color="#374151" />
        <Text style={styles.menuText}>Ustawienia</Text>
        <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
      </TouchableOpacity>

      <TouchableOpacity style={[styles.menuItem, styles.logoutItem]} onPress={logout}>
        <Ionicons name="log-out-outline" size={24} color="#ef4444" />
        <Text style={[styles.menuText, styles.logoutText]}>Wyloguj</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  moreContainer: {
    flex: 1,
    backgroundColor: '#f9fafb',
    paddingTop: 16,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    paddingVertical: 16,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  menuText: {
    flex: 1,
    marginLeft: 16,
    fontSize: 16,
    color: '#374151',
  },
  badge: {
    backgroundColor: '#3b82f6',
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
  logoutText: {
    color: '#ef4444',
  },
});

// ============================================
// ROOT NAVIGATOR
// ============================================

export default function AppNavigator() {
  const { isAuthenticated, isLoading, user } = useAuthStore();

  if (isLoading) {
    return null; // Or a loading screen
  }

  return (
    <NavigationContainer>
      <RootStack.Navigator screenOptions={{ headerShown: false }}>
        {!isAuthenticated ? (
          <RootStack.Screen name="Auth" component={AuthNavigator} />
        ) : user?.type === 'instructor' ? (
          <>
            <RootStack.Screen name="InstructorTabs" component={InstructorTabNavigator} />
            <RootStack.Screen
              name="Attendance"
              component={AttendanceScreen}
              options={{ headerShown: true, title: 'Obecność' }}
            />
          </>
        ) : (
          <>
            <RootStack.Screen name="ParentTabs" component={ParentTabNavigator} />
            <RootStack.Screen
              name="ChildDetails"
              component={ChildDetailsScreen}
              options={{ headerShown: true, title: 'Dziecko' }}
            />
          </>
        )}
        <RootStack.Screen
          name="Notifications"
          component={NotificationsScreen}
          options={{ headerShown: true, title: 'Powiadomienia' }}
        />
        <RootStack.Screen
          name="Profile"
          component={ProfileScreen}
          options={{ headerShown: true, title: 'Profil' }}
        />
        <RootStack.Screen
          name="Settings"
          component={SettingsScreen}
          options={{ headerShown: true, title: 'Ustawienia' }}
        />
      </RootStack.Navigator>
    </NavigationContainer>
  );
}
