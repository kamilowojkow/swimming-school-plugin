import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore, UserType } from '../store/authStore';
import { useSettingsStore, useThemeColors } from '../store/settingsStore';

export default function RoleSwitcher() {
  const { user, activeRole, switchRole } = useAuthStore();
  const { t, isDark } = useSettingsStore();
  const colors = useThemeColors(activeRole);

  // Only show if user has multiple roles
  if (!user || !user.roles || user.roles.length <= 1) {
    return null;
  }

  const otherRole = user.roles.find((role) => role !== activeRole);
  if (!otherRole) return null;

  const roleConfig: Record<UserType, { label: string; icon: keyof typeof Ionicons.glyphMap; color: string }> = {
    parent: {
      label: t.roles.parent,
      icon: 'people',
      color: colors.primary,
    },
    instructor: {
      label: t.roles.instructor,
      icon: 'fitness',
      color: colors.secondary,
    },
  };

  const currentConfig = roleConfig[activeRole || 'parent'];
  const otherConfig = roleConfig[otherRole];

  const handleSwitch = () => {
    switchRole(otherRole);
  };

  const styles = createStyles(colors, isDark);

  return (
    <View style={styles.container}>
      <View style={styles.currentRole}>
        <View style={[styles.roleIcon, { backgroundColor: currentConfig.color + '20' }]}>
          <Ionicons name={currentConfig.icon} size={20} color={currentConfig.color} />
        </View>
        <View style={styles.roleInfo}>
          <Text style={styles.roleLabel}>{t.roles.currentView}</Text>
          <Text style={styles.roleName}>{currentConfig.label}</Text>
        </View>
      </View>

      <TouchableOpacity
        style={[styles.switchButton, { backgroundColor: otherConfig.color }]}
        onPress={handleSwitch}
      >
        <Ionicons name="swap-horizontal" size={18} color="#fff" />
        <Text style={styles.switchButtonText}>
          {otherConfig.label}
        </Text>
      </TouchableOpacity>
    </View>
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>, isDark: boolean) =>
  StyleSheet.create({
    container: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      backgroundColor: colors.surface,
      paddingVertical: 16,
      paddingHorizontal: 20,
      marginBottom: 16,
      borderRadius: 12,
      marginHorizontal: 16,
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: isDark ? 0.3 : 0.1,
      shadowRadius: 3,
      elevation: 2,
    },
    currentRole: {
      flexDirection: 'row',
      alignItems: 'center',
      flex: 1,
    },
    roleIcon: {
      width: 44,
      height: 44,
      borderRadius: 22,
      justifyContent: 'center',
      alignItems: 'center',
    },
    roleInfo: {
      marginLeft: 12,
    },
    roleLabel: {
      fontSize: 12,
      color: colors.textSecondary,
    },
    roleName: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
      marginTop: 2,
    },
    switchButton: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingVertical: 10,
      paddingHorizontal: 14,
      borderRadius: 10,
      gap: 6,
    },
    switchButtonText: {
      color: '#fff',
      fontSize: 14,
      fontWeight: '600',
    },
  });
