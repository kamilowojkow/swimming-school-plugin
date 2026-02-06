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
  Modal,
  TextInput,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

interface UserProfile {
  id: number;
  email: string;
  first_name: string;
  last_name: string;
  phone?: string;
  role: string;
  address?: string;
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
  const { logout, user, activeRole, updateUser } = useAuthStore();
  const { t, isDark } = useSettingsStore();
  const colors = useThemeColors(activeRole);

  // Edit mode states
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editData, setEditData] = useState({
    first_name: '',
    last_name: '',
    phone: '',
    address: '',
    bio: '',
  });

  // Password change states
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [passwordData, setPasswordData] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  });
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  const isInstructor = activeRole === 'instructor';
  const primaryColor = isInstructor ? colors.secondary : colors.primary;

  const fetchProfile = async () => {
    try {
      const data = await api.getProfile();
      setProfile(data);
      setEditData({
        first_name: data.first_name || '',
        last_name: data.last_name || '',
        phone: data.phone || '',
        address: data.address || '',
        bio: data.bio || '',
      });
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

  const handleSaveProfile = async () => {
    setIsSaving(true);
    try {
      await api.updateProfile(editData);
      // Update local state
      setProfile((prev) => (prev ? { ...prev, ...editData } : prev));
      updateUser({
        first_name: editData.first_name,
        last_name: editData.last_name,
        phone: editData.phone,
      });
      setIsEditing(false);
      Alert.alert(t.common.done, t.profile.saveChanges);
    } catch (error) {
      Alert.alert(t.common.error, 'Nie udało się zapisać zmian');
    } finally {
      setIsSaving(false);
    }
  };

  const handleChangePassword = async () => {
    if (!passwordData.currentPassword || !passwordData.newPassword) {
      Alert.alert(t.common.error, 'Wypełnij wszystkie pola');
      return;
    }
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      Alert.alert(t.common.error, 'Hasła nie są identyczne');
      return;
    }
    if (passwordData.newPassword.length < 6) {
      Alert.alert(t.common.error, 'Hasło musi mieć minimum 6 znaków');
      return;
    }

    setIsChangingPassword(true);
    try {
      await api.changePassword(passwordData.currentPassword, passwordData.newPassword);
      setShowPasswordModal(false);
      setPasswordData({ currentPassword: '', newPassword: '', confirmPassword: '' });
      Alert.alert(t.common.done, 'Hasło zostało zmienione');
    } catch (error: any) {
      const message = error.response?.data?.message || 'Nie udało się zmienić hasła';
      Alert.alert(t.common.error, message);
    } finally {
      setIsChangingPassword(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(t.auth.logout, 'Czy na pewno chcesz się wylogować?', [
      { text: t.common.cancel, style: 'cancel' },
      {
        text: t.auth.logout,
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
    ]);
  };

  const getInitials = () => {
    if (profile) {
      return `${profile.first_name.charAt(0)}${profile.last_name.charAt(0)}`.toUpperCase();
    }
    return '?';
  };

  const styles = createStyles(colors, isDark, primaryColor);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={primaryColor} />
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={{ flex: 1 }}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
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
            <View
              style={[
                styles.roleBadge,
                { backgroundColor: isInstructor ? colors.secondary : colors.primary },
              ]}
            >
              <Ionicons name={isInstructor ? 'school' : 'people'} size={12} color="#fff" />
            </View>
          </View>
          <Text style={styles.name}>
            {profile?.first_name} {profile?.last_name}
          </Text>
          <Text style={styles.role}>
            {isInstructor ? t.roles.instructor : t.roles.parent}
          </Text>

          {/* Edit Button */}
          <TouchableOpacity
            style={styles.editHeaderButton}
            onPress={() => setIsEditing(!isEditing)}
          >
            <Ionicons name={isEditing ? 'close' : 'create-outline'} size={20} color="#fff" />
            <Text style={styles.editHeaderButtonText}>
              {isEditing ? t.common.cancel : t.common.edit}
            </Text>
          </TouchableOpacity>
        </View>

        {/* Contact Info */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>{t.profile.title}</Text>

          <View style={styles.infoCard}>
            {isEditing ? (
              <>
                <View style={styles.inputRow}>
                  <Text style={styles.inputLabel}>{t.profile.firstName}</Text>
                  <TextInput
                    style={styles.input}
                    value={editData.first_name}
                    onChangeText={(text) => setEditData({ ...editData, first_name: text })}
                    placeholder={t.profile.firstName}
                    placeholderTextColor={colors.textTertiary}
                  />
                </View>
                <View style={styles.divider} />
                <View style={styles.inputRow}>
                  <Text style={styles.inputLabel}>{t.profile.lastName}</Text>
                  <TextInput
                    style={styles.input}
                    value={editData.last_name}
                    onChangeText={(text) => setEditData({ ...editData, last_name: text })}
                    placeholder={t.profile.lastName}
                    placeholderTextColor={colors.textTertiary}
                  />
                </View>
                <View style={styles.divider} />
                <View style={styles.inputRow}>
                  <Text style={styles.inputLabel}>{t.profile.phone}</Text>
                  <TextInput
                    style={styles.input}
                    value={editData.phone}
                    onChangeText={(text) => setEditData({ ...editData, phone: text })}
                    placeholder={t.profile.phone}
                    placeholderTextColor={colors.textTertiary}
                    keyboardType="phone-pad"
                  />
                </View>
                <View style={styles.divider} />
                <View style={styles.inputRow}>
                  <Text style={styles.inputLabel}>{t.profile.address}</Text>
                  <TextInput
                    style={styles.input}
                    value={editData.address}
                    onChangeText={(text) => setEditData({ ...editData, address: text })}
                    placeholder={t.profile.address}
                    placeholderTextColor={colors.textTertiary}
                  />
                </View>
              </>
            ) : (
              <>
                <View style={styles.infoRow}>
                  <View style={[styles.infoIcon, { backgroundColor: colors.primaryLight }]}>
                    <Ionicons name="mail" size={20} color={colors.primary} />
                  </View>
                  <View style={styles.infoContent}>
                    <Text style={styles.infoLabel}>{t.profile.email}</Text>
                    <Text style={styles.infoValue}>{profile?.email}</Text>
                  </View>
                </View>

                <View style={styles.divider} />

                <View style={styles.infoRow}>
                  <View style={[styles.infoIcon, { backgroundColor: colors.secondaryLight }]}>
                    <Ionicons name="call" size={20} color={colors.secondary} />
                  </View>
                  <View style={styles.infoContent}>
                    <Text style={styles.infoLabel}>{t.profile.phone}</Text>
                    <Text style={styles.infoValue}>{profile?.phone || t.common.noData}</Text>
                  </View>
                </View>
              </>
            )}
          </View>

          {isEditing && (
            <TouchableOpacity
              style={[styles.saveButton, { backgroundColor: primaryColor }]}
              onPress={handleSaveProfile}
              disabled={isSaving}
            >
              {isSaving ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <Ionicons name="checkmark" size={20} color="#fff" />
                  <Text style={styles.saveButtonText}>{t.profile.saveChanges}</Text>
                </>
              )}
            </TouchableOpacity>
          )}
        </View>

        {/* Instructor Work Info */}
        {isInstructor && !isEditing && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>{t.salary.title}</Text>

            <View style={styles.infoCard}>
              <View style={styles.infoRow}>
                <View style={[styles.infoIcon, { backgroundColor: colors.warningLight }]}>
                  <Ionicons name="cash" size={20} color={colors.warning} />
                </View>
                <View style={styles.infoContent}>
                  <Text style={styles.infoLabel}>{t.salary.hourlyRate}</Text>
                  <Text style={styles.infoValue}>
                    {profile?.hourly_rate ? `${profile.hourly_rate} zł/h` : t.common.noData}
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
            </View>
          </View>
        )}

        {/* Parent Family Info */}
        {!isInstructor && !isEditing && profile?.children_count !== undefined && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>{t.children.title}</Text>

            <View style={styles.infoCard}>
              <View style={styles.infoRow}>
                <View style={[styles.infoIcon, { backgroundColor: colors.warningLight }]}>
                  <Ionicons name="people" size={20} color={colors.warning} />
                </View>
                <View style={styles.infoContent}>
                  <Text style={styles.infoLabel}>{t.children.title}</Text>
                  <Text style={styles.infoValue}>
                    {profile.children_count}{' '}
                    {profile.children_count === 1 ? 'dziecko' : 'dzieci'}
                  </Text>
                </View>
              </View>
            </View>
          </View>
        )}

        {/* Settings */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>{t.settings.title}</Text>

          <View style={styles.infoCard}>
            <View style={styles.settingRow}>
              <View style={styles.settingLeft}>
                <View style={[styles.infoIcon, { backgroundColor: colors.errorLight }]}>
                  <Ionicons name="notifications" size={20} color={colors.error} />
                </View>
                <Text style={styles.settingLabel}>{t.settings.pushNotifications}</Text>
              </View>
              <Switch
                value={notificationsEnabled}
                onValueChange={setNotificationsEnabled}
                trackColor={{
                  false: colors.border,
                  true: isInstructor ? colors.secondaryLight : colors.primaryLight,
                }}
                thumbColor={notificationsEnabled ? primaryColor : colors.textTertiary}
              />
            </View>
          </View>
        </View>

        {/* Menu Items */}
        <View style={styles.section}>
          <View style={styles.menuCard}>
            <TouchableOpacity style={styles.menuItem} onPress={() => setShowPasswordModal(true)}>
              <View style={[styles.menuIcon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="key" size={20} color={colors.primary} />
              </View>
              <Text style={styles.menuLabel}>{t.profile.changePassword}</Text>
              <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
            </TouchableOpacity>

            <View style={styles.divider} />

            <TouchableOpacity style={styles.menuItem}>
              <View style={[styles.menuIcon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="help-circle" size={20} color={colors.primary} />
              </View>
              <Text style={styles.menuLabel}>{t.settings.about}</Text>
              <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
            </TouchableOpacity>

            <View style={styles.divider} />

            <TouchableOpacity style={styles.menuItem}>
              <View style={[styles.menuIcon, { backgroundColor: '#f3e8ff' }]}>
                <Ionicons name="document-text" size={20} color="#a855f7" />
              </View>
              <Text style={styles.menuLabel}>{t.settings.termsOfService}</Text>
              <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
            </TouchableOpacity>

            <View style={styles.divider} />

            <TouchableOpacity style={styles.menuItem}>
              <View style={[styles.menuIcon, { backgroundColor: colors.secondaryLight }]}>
                <Ionicons name="shield-checkmark" size={20} color={colors.secondary} />
              </View>
              <Text style={styles.menuLabel}>{t.settings.privacyPolicy}</Text>
              <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
            </TouchableOpacity>
          </View>
        </View>

        {/* Logout Button */}
        <View style={styles.section}>
          <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
            <Ionicons name="log-out" size={20} color={colors.error} />
            <Text style={styles.logoutText}>{t.auth.logout}</Text>
          </TouchableOpacity>
        </View>

        {/* App Version */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            {t.settings.version} 1.0.0
          </Text>
        </View>

        <View style={{ height: 24 }} />
      </ScrollView>

      {/* Password Change Modal */}
      <Modal
        visible={showPasswordModal}
        animationType="slide"
        transparent
        onRequestClose={() => setShowPasswordModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{t.profile.changePassword}</Text>
              <TouchableOpacity onPress={() => setShowPasswordModal(false)}>
                <Ionicons name="close" size={24} color={colors.text} />
              </TouchableOpacity>
            </View>

            <View style={styles.modalBody}>
              <View style={styles.modalInputContainer}>
                <Text style={styles.modalInputLabel}>{t.profile.currentPassword}</Text>
                <TextInput
                  style={styles.modalInput}
                  value={passwordData.currentPassword}
                  onChangeText={(text) =>
                    setPasswordData({ ...passwordData, currentPassword: text })
                  }
                  placeholder={t.profile.currentPassword}
                  placeholderTextColor={colors.textTertiary}
                  secureTextEntry
                />
              </View>

              <View style={styles.modalInputContainer}>
                <Text style={styles.modalInputLabel}>{t.profile.newPassword}</Text>
                <TextInput
                  style={styles.modalInput}
                  value={passwordData.newPassword}
                  onChangeText={(text) =>
                    setPasswordData({ ...passwordData, newPassword: text })
                  }
                  placeholder={t.profile.newPassword}
                  placeholderTextColor={colors.textTertiary}
                  secureTextEntry
                />
              </View>

              <View style={styles.modalInputContainer}>
                <Text style={styles.modalInputLabel}>{t.profile.confirmPassword}</Text>
                <TextInput
                  style={styles.modalInput}
                  value={passwordData.confirmPassword}
                  onChangeText={(text) =>
                    setPasswordData({ ...passwordData, confirmPassword: text })
                  }
                  placeholder={t.profile.confirmPassword}
                  placeholderTextColor={colors.textTertiary}
                  secureTextEntry
                />
              </View>
            </View>

            <View style={styles.modalFooter}>
              <TouchableOpacity
                style={styles.modalCancelButton}
                onPress={() => setShowPasswordModal(false)}
              >
                <Text style={styles.modalCancelButtonText}>{t.common.cancel}</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalSaveButton, { backgroundColor: primaryColor }]}
                onPress={handleChangePassword}
                disabled={isChangingPassword}
              >
                {isChangingPassword ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text style={styles.modalSaveButtonText}>{t.common.save}</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const createStyles = (
  colors: ReturnType<typeof useThemeColors>,
  isDark: boolean,
  primaryColor: string
) =>
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
      backgroundColor: colors.surface,
      justifyContent: 'center',
      alignItems: 'center',
      shadowColor: colors.shadow,
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
      borderColor: '#fff',
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
    editHeaderButton: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 16,
      paddingHorizontal: 16,
      paddingVertical: 8,
      backgroundColor: 'rgba(255,255,255,0.2)',
      borderRadius: 20,
      gap: 6,
    },
    editHeaderButtonText: {
      color: '#fff',
      fontWeight: '600',
      fontSize: 14,
    },
    section: {
      padding: 16,
      paddingBottom: 0,
    },
    sectionTitle: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.textSecondary,
      marginBottom: 12,
      textTransform: 'uppercase',
      letterSpacing: 0.5,
    },
    infoCard: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      padding: 4,
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: isDark ? 0.3 : 0.05,
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
      color: colors.textSecondary,
      marginBottom: 2,
    },
    infoValue: {
      fontSize: 15,
      color: colors.text,
      fontWeight: '500',
    },
    divider: {
      height: 1,
      backgroundColor: colors.border,
      marginHorizontal: 12,
    },
    inputRow: {
      padding: 12,
    },
    inputLabel: {
      fontSize: 12,
      color: colors.textSecondary,
      marginBottom: 6,
    },
    input: {
      fontSize: 15,
      color: colors.text,
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 8,
      paddingHorizontal: 12,
      paddingVertical: 10,
      backgroundColor: colors.background,
    },
    saveButton: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'center',
      padding: 14,
      borderRadius: 12,
      marginTop: 12,
      gap: 8,
    },
    saveButtonText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '600',
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
      color: colors.text,
      fontWeight: '500',
      marginLeft: 12,
    },
    menuCard: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      overflow: 'hidden',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: isDark ? 0.3 : 0.05,
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
      color: colors.text,
      fontWeight: '500',
      marginLeft: 12,
    },
    logoutButton: {
      backgroundColor: colors.errorLight,
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
      color: colors.error,
    },
    footer: {
      alignItems: 'center',
      paddingVertical: 20,
    },
    footerText: {
      fontSize: 12,
      color: colors.textTertiary,
    },
    // Modal styles
    modalOverlay: {
      flex: 1,
      backgroundColor: 'rgba(0,0,0,0.5)',
      justifyContent: 'flex-end',
    },
    modalContent: {
      backgroundColor: colors.surface,
      borderTopLeftRadius: 24,
      borderTopRightRadius: 24,
      paddingBottom: 34,
    },
    modalHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: 20,
      borderBottomWidth: 1,
      borderBottomColor: colors.border,
    },
    modalTitle: {
      fontSize: 18,
      fontWeight: '700',
      color: colors.text,
    },
    modalBody: {
      padding: 20,
    },
    modalInputContainer: {
      marginBottom: 16,
    },
    modalInputLabel: {
      fontSize: 14,
      fontWeight: '500',
      color: colors.textSecondary,
      marginBottom: 8,
    },
    modalInput: {
      borderWidth: 1,
      borderColor: colors.border,
      borderRadius: 12,
      paddingHorizontal: 16,
      paddingVertical: 14,
      fontSize: 16,
      color: colors.text,
      backgroundColor: colors.background,
    },
    modalFooter: {
      flexDirection: 'row',
      padding: 20,
      paddingTop: 0,
      gap: 12,
    },
    modalCancelButton: {
      flex: 1,
      padding: 14,
      borderRadius: 12,
      backgroundColor: colors.surfaceSecondary,
      alignItems: 'center',
    },
    modalCancelButtonText: {
      fontSize: 16,
      fontWeight: '600',
      color: colors.text,
    },
    modalSaveButton: {
      flex: 1,
      padding: 14,
      borderRadius: 12,
      alignItems: 'center',
    },
    modalSaveButtonText: {
      fontSize: 16,
      fontWeight: '600',
      color: '#fff',
    },
  });
