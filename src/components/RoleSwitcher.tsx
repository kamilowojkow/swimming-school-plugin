import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore, UserType } from '../store/authStore';

const roleConfig: Record<UserType, { label: string; icon: keyof typeof Ionicons.glyphMap; color: string }> = {
  parent: {
    label: 'Rodzic',
    icon: 'people',
    color: '#3b82f6',
  },
  instructor: {
    label: 'Instruktor',
    icon: 'fitness',
    color: '#10b981',
  },
};

export default function RoleSwitcher() {
  const { user, activeRole, switchRole } = useAuthStore();

  // Only show if user has multiple roles
  if (!user || !user.roles || user.roles.length <= 1) {
    return null;
  }

  const otherRole = user.roles.find((role) => role !== activeRole);
  if (!otherRole) return null;

  const currentConfig = roleConfig[activeRole || 'parent'];
  const otherConfig = roleConfig[otherRole];

  const handleSwitch = () => {
    switchRole(otherRole);
  };

  return (
    <View style={styles.container}>
      <View style={styles.currentRole}>
        <View style={[styles.roleIcon, { backgroundColor: currentConfig.color + '20' }]}>
          <Ionicons name={currentConfig.icon} size={20} color={currentConfig.color} />
        </View>
        <View style={styles.roleInfo}>
          <Text style={styles.roleLabel}>Aktualny widok</Text>
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

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#fff',
    paddingVertical: 16,
    paddingHorizontal: 20,
    marginBottom: 16,
    borderRadius: 12,
    marginHorizontal: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
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
    color: '#6b7280',
  },
  roleName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
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
