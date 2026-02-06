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
  Switch,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../../api/client';
import { useAuthStore } from '../../store/authStore';

interface UserProfile {
  id: number;
  email: string;
  first_name: string;
  last_name: string;
  phone?: string;
  role: string;
  // Instructor-specific
  hourly_rate?: number;
  specializations?: string[];
  bio?: string;
  avatar_url?: string;
  // Parent-specific
  children_count?: number;
}

export default function ProfileScreen() {
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [notificationsEnabled, setNotificationsEnabled] = useState(true);
  const { logout, user } = useAuthStore();

  const isInstructor = user?.type === 'instructor';
  const primaryColor = isInstructor ? '#10b981' : '#3b82f6';

  const fetchProfile = async () => {
    try {
      const data = await api.getProfile();
      setProfile(data);
    } catch (error) {
      console.error('Error fetching profile:', error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchProfile();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchProfile();
    setRefreshing(false);
  };

  const handleLogout = () => {
    Alert.alert(
      'Wylogowanie',
      'Czy na pewno chcesz się wylogować?',
      [
        { text: 'Anuluj', style: 'cancel' },
        {
          text: 'Wyloguj',
          style: 'destructive',
          onPress: async () => {
            try {
              await api.logout();
            } catch (error) {
              console.error('Logout error:', error);
            }
            logout();
          },
        },
      ]
    );
  };

  const getInitials = () => {
    if (profile) {
      return `${profile.first_name.charAt(0)}${profile.last_name.charAt(0)}`.toUpperCase();
    }
    return '?';
  };

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={primaryColor} />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Profile Header */}
      <View style={[styles.header, { backgroundColor: primaryColor }]}>
        <View style={styles.avatarContainer}>
          <View style={styles.avatar}>
            <Text style={[styles.avatarText, { color: primaryColor }]}>{getInitials()}</Text>
          </View>
          <View style={[styles.roleBadge, { backgroundColor: isInstructor ? '#059669' : '#2563eb', borderColor: primaryColor }]}>
            <Ionicons name={isInstructor ? 'school' : 'people'} size={12} color="#fff" />
          </View>
        </View>
        <Text style={styles.name}>
          {profile?.first_name} {profile?.last_name}
        </Text>
        <Text style={styles.role}>{isInstructor ? 'Instruktor' : 'Rodzic'}</Text>
      </View>

      {/* Contact Info */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Dane kontaktowe</Text>

        <View style={styles.infoCard}>
          <View style={styles.infoRow}>
            <View style={[styles.infoIcon, { backgroundColor: '#eff6ff' }]}>
              <Ionicons name="mail" size={20} color="#3b82f6" />
            </View>
            <View style={styles.infoContent}>
              <Text style={styles.infoLabel}>Email</Text>
              <Text style={styles.infoValue}>{profile?.email}</Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <View style={[styles.infoIcon, { backgroundColor: '#ecfdf5' }]}>
              <Ionicons name="call" size={20} color="#10b981" />
            </View>
            <View style={styles.infoContent}>
              <Text style={styles.infoLabel}>Telefon</Text>
              <Text style={styles.infoValue}>{profile?.phone || 'Nie podano'}</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Instructor Work Info */}
      {isInstructor && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Informacje o pracy</Text>

          <View style={styles.infoCard}>
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#fef3c7' }]}>
                <Ionicons name="cash" size={20} color="#f59e0b" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Stawka godzinowa</Text>
                <Text style={styles.infoValue}>
                  {profile?.hourly_rate ? `${profile.hourly_rate} zł/h` : 'Nie określono'}
                </Text>
              </View>
            </View>

            {profile?.specializations && profile.specializations.length > 0 && (
              <>
                <View style={styles.divider} />
                <View style={styles.infoRow}>
                  <View style={[styles.infoIcon, { backgroundColor: '#fce7f3' }]}>
                    <Ionicons name="ribbon" size={20} color="#ec4899" />
                  </View>
                  <View style={styles.infoContent}>
                    <Text style={styles.infoLabel}>Specjalizacje</Text>
                    <View style={styles.tags}>
                      {profile.specializations.map((spec, index) => (
                        <View key={index} style={styles.tag}>
                          <Text style={styles.tagText}>{spec}</Text>
                        </View>
                      ))}
                    </View>
                  </View>
                </View>
              </>
            )}

            {profile?.bio && (
              <>
                <View style={styles.divider} />
                <View style={styles.infoRow}>
                  <View style={[styles.infoIcon, { backgroundColor: '#f3e8ff' }]}>
                    <Ionicons name="person" size={20} color="#a855f7" />
                  </View>
                  <View style={styles.infoContent}>
                    <Text style={styles.infoLabel}>O mnie</Text>
                    <Text style={styles.infoValue}>{profile.bio}</Text>
                  </View>
                </View>
              </>
            )}
          </View>
        </View>
      )}

      {/* Parent Family Info */}
      {!isInstructor && profile?.children_count !== undefined && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Rodzina</Text>

          <View style={styles.infoCard}>
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: '#fef3c7' }]}>
                <Ionicons name="people" size={20} color="#f59e0b" />
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Zapisane dzieci</Text>
                <Text style={styles.infoValue}>
                  {profile.children_count} {profile.children_count === 1 ? 'dziecko' : 'dzieci'}
                </Text>
              </View>
            </View>
          </View>
        </View>
      )}

      {/* Settings */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Ustawienia</Text>

        <View style={styles.infoCard}>
          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.infoIcon, { backgroundColor: '#fef2f2' }]}>
                <Ionicons name="notifications" size={20} color="#ef4444" />
              </View>
              <Text style={styles.settingLabel}>Powiadomienia push</Text>
            </View>
            <Switch
              value={notificationsEnabled}
              onValueChange={setNotificationsEnabled}
              trackColor={{ false: '#d1d5db', true: isInstructor ? '#86efac' : '#93c5fd' }}
              thumbColor={notificationsEnabled ? primaryColor : '#9ca3af'}
            />
          </View>
        </View>
      </View>

      {/* Menu Items */}
      <View style={styles.section}>
        <View style={styles.menuCard}>
          <TouchableOpacity style={styles.menuItem}>
            <View style={[styles.menuIcon, { backgroundColor: '#eff6ff' }]}>
              <Ionicons name="help-circle" size={20} color="#3b82f6" />
            </View>
            <Text style={styles.menuLabel}>Pomoc i wsparcie</Text>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity style={styles.menuItem}>
            <View style={[styles.menuIcon, { backgroundColor: '#f3e8ff' }]}>
              <Ionicons name="document-text" size={20} color="#a855f7" />
            </View>
            <Text style={styles.menuLabel}>Regulamin</Text>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity style={styles.menuItem}>
            <View style={[styles.menuIcon, { backgroundColor: '#ecfdf5' }]}>
              <Ionicons name="shield-checkmark" size={20} color="#10b981" />
            </View>
            <Text style={styles.menuLabel}>Polityka prywatności</Text>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>
        </View>
      </View>

      {/* Logout Button */}
      <View style={styles.section}>
        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
          <Ionicons name="log-out" size={20} color="#ef4444" />
          <Text style={styles.logoutText}>Wyloguj się</Text>
        </TouchableOpacity>
      </View>

      {/* App Version */}
      <View style={styles.footer}>
        <Text style={styles.footerText}>Szkółka Pływania v1.0.0</Text>
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
  header: {
    paddingTop: 40,
    paddingBottom: 30,
    alignItems: 'center',
  },
  avatarContainer: {
    position: 'relative',
    marginBottom: 12,
  },
  avatar: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 4,
  },
  avatarText: {
    fontSize: 36,
    fontWeight: '700',
  },
  roleBadge: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 28,
    height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
  },
  name: {
    fontSize: 24,
    fontWeight: '700',
    color: '#fff',
  },
  role: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 4,
  },
  section: {
    padding: 16,
    paddingBottom: 0,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
    marginBottom: 12,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  infoCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    padding: 12,
  },
  infoIcon: {
    width: 40,
    height: 40,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  infoContent: {
    flex: 1,
    marginLeft: 12,
  },
  infoLabel: {
    fontSize: 12,
    color: '#6b7280',
    marginBottom: 2,
  },
  infoValue: {
    fontSize: 15,
    color: '#111827',
    fontWeight: '500',
  },
  divider: {
    height: 1,
    backgroundColor: '#f3f4f6',
    marginHorizontal: 12,
  },
  tags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: 4,
  },
  tag: {
    backgroundColor: '#fce7f3',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  tagText: {
    fontSize: 12,
    color: '#ec4899',
    fontWeight: '500',
  },
  settingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 12,
  },
  settingLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  settingLabel: {
    fontSize: 15,
    color: '#111827',
    fontWeight: '500',
    marginLeft: 12,
  },
  menuCard: {
    backgroundColor: '#fff',
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
  },
  menuIcon: {
    width: 40,
    height: 40,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  menuLabel: {
    flex: 1,
    fontSize: 15,
    color: '#111827',
    fontWeight: '500',
    marginLeft: 12,
  },
  logoutButton: {
    backgroundColor: '#fef2f2',
    borderRadius: 16,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  logoutText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#ef4444',
  },
  footer: {
    alignItems: 'center',
    paddingVertical: 20,
  },
  footerText: {
    fontSize: 12,
    color: '#9ca3af',
  },
});
