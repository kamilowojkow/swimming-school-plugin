import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Switch,
  Alert,
  Linking,
  Modal,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../store/authStore';
import { useSettingsStore, ThemeMode, useThemeColors } from '../../store/settingsStore';
import { Language } from '../../i18n/translations';

const languageNames: Record<Language, string> = {
  pl: 'Polski',
  en: 'English',
};

const themeNames: Record<ThemeMode, { pl: string; en: string }> = {
  light: { pl: 'Jasny', en: 'Light' },
  dark: { pl: 'Ciemny', en: 'Dark' },
  system: { pl: 'Systemowy', en: 'System' },
};

export default function SettingsScreen() {
  const { activeRole } = useAuthStore();
  const { language, setLanguage, themeMode, setThemeMode, t, theme, isDark } = useSettingsStore();
  const colors = useThemeColors(activeRole);

  const [pushEnabled, setPushEnabled] = useState(true);
  const [emailEnabled, setEmailEnabled] = useState(true);
  const [smsEnabled, setSmsEnabled] = useState(false);

  const [languageModalVisible, setLanguageModalVisible] = useState(false);
  const [themeModalVisible, setThemeModalVisible] = useState(false);

  const handleClearCache = () => {
    Alert.alert(
      language === 'pl' ? 'Wyczyść pamięć podręczną' : 'Clear cache',
      language === 'pl'
        ? 'Czy na pewno chcesz wyczyścić pamięć podręczną aplikacji?'
        : 'Are you sure you want to clear the app cache?',
      [
        { text: t.common.cancel, style: 'cancel' },
        {
          text: language === 'pl' ? 'Wyczyść' : 'Clear',
          onPress: () => {
            Alert.alert(
              language === 'pl' ? 'Sukces' : 'Success',
              language === 'pl' ? 'Pamięć podręczna została wyczyszczona' : 'Cache has been cleared'
            );
          },
        },
      ]
    );
  };

  const handleContact = () => {
    Linking.openURL('mailto:kontakt@pasjaplywania.pl');
  };

  const handleWebsite = () => {
    Linking.openURL('https://pasjaplywania.pl');
  };

  const styles = createStyles(colors, isDark);

  return (
    <ScrollView style={styles.container}>
      {/* Language & Theme Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>{t.settings.language} & {t.settings.theme}</Text>

        <View style={styles.card}>
          {/* Language Selector */}
          <TouchableOpacity style={styles.menuRow} onPress={() => setLanguageModalVisible(true)}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="language" size={20} color={colors.primary} />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>{t.settings.language}</Text>
                <Text style={styles.settingDescription}>{languageNames[language]}</Text>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
          </TouchableOpacity>

          <View style={styles.divider} />

          {/* Theme Selector */}
          <TouchableOpacity style={styles.menuRow} onPress={() => setThemeModalVisible(true)}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#f3e8ff' }]}>
                <Ionicons name={isDark ? 'moon' : 'sunny'} size={20} color="#a855f7" />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>{t.settings.theme}</Text>
                <Text style={styles.settingDescription}>{themeNames[themeMode][language]}</Text>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
          </TouchableOpacity>
        </View>
      </View>

      {/* Notifications Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>{t.settings.notifications}</Text>

        <View style={styles.card}>
          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.errorLight }]}>
                <Ionicons name="notifications" size={20} color={colors.error} />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>{t.settings.pushNotifications}</Text>
                <Text style={styles.settingDescription}>
                  {language === 'pl' ? 'Otrzymuj powiadomienia o zajęciach' : 'Receive notifications about lessons'}
                </Text>
              </View>
            </View>
            <Switch
              value={pushEnabled}
              onValueChange={setPushEnabled}
              trackColor={{ false: colors.border, true: colors.accent + '60' }}
              thumbColor={pushEnabled ? colors.accent : colors.textTertiary}
            />
          </View>

          <View style={styles.divider} />

          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="mail" size={20} color={colors.primary} />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>{t.settings.emailNotifications}</Text>
                <Text style={styles.settingDescription}>
                  {language === 'pl' ? 'Podsumowania i ważne informacje' : 'Summaries and important information'}
                </Text>
              </View>
            </View>
            <Switch
              value={emailEnabled}
              onValueChange={setEmailEnabled}
              trackColor={{ false: colors.border, true: colors.accent + '60' }}
              thumbColor={emailEnabled ? colors.accent : colors.textTertiary}
            />
          </View>

          <View style={styles.divider} />

          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.secondaryLight }]}>
                <Ionicons name="chatbubble" size={20} color={colors.secondary} />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>
                  {language === 'pl' ? 'Powiadomienia SMS' : 'SMS notifications'}
                </Text>
                <Text style={styles.settingDescription}>
                  {language === 'pl' ? 'Pilne informacje (mogą być płatne)' : 'Urgent info (may have charges)'}
                </Text>
              </View>
            </View>
            <Switch
              value={smsEnabled}
              onValueChange={setSmsEnabled}
              trackColor={{ false: colors.border, true: colors.accent + '60' }}
              thumbColor={smsEnabled ? colors.accent : colors.textTertiary}
            />
          </View>
        </View>
      </View>

      {/* Storage Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>{language === 'pl' ? 'Dane' : 'Data'}</Text>

        <View style={styles.card}>
          <TouchableOpacity style={styles.menuRow} onPress={handleClearCache}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.warningLight }]}>
                <Ionicons name="trash" size={20} color={colors.warning} />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>
                  {language === 'pl' ? 'Wyczyść pamięć podręczną' : 'Clear cache'}
                </Text>
                <Text style={styles.settingDescription}>
                  {language === 'pl' ? 'Usuń tymczasowe pliki' : 'Remove temporary files'}
                </Text>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
          </TouchableOpacity>
        </View>
      </View>

      {/* Support Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>{language === 'pl' ? 'Wsparcie' : 'Support'}</Text>

        <View style={styles.card}>
          <TouchableOpacity style={styles.menuRow} onPress={handleContact}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.primaryLight }]}>
                <Ionicons name="help-circle" size={20} color={colors.primary} />
              </View>
              <Text style={styles.menuLabel}>
                {language === 'pl' ? 'Kontakt z pomocą' : 'Contact support'}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity style={styles.menuRow} onPress={handleWebsite}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.secondaryLight }]}>
                <Ionicons name="globe" size={20} color={colors.secondary} />
              </View>
              <Text style={styles.menuLabel}>
                {language === 'pl' ? 'Nasza strona WWW' : 'Our website'}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity style={styles.menuRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: colors.errorLight }]}>
                <Ionicons name="bug" size={20} color={colors.error} />
              </View>
              <Text style={styles.menuLabel}>
                {language === 'pl' ? 'Zgłoś błąd' : 'Report a bug'}
              </Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color={colors.textTertiary} />
          </TouchableOpacity>
        </View>
      </View>

      {/* About Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>{t.settings.about}</Text>

        <View style={styles.card}>
          <View style={styles.aboutRow}>
            <Text style={styles.aboutLabel}>{t.settings.version}</Text>
            <Text style={styles.aboutValue}>1.0.0</Text>
          </View>
          <View style={styles.divider} />
          <View style={styles.aboutRow}>
            <Text style={styles.aboutLabel}>Build</Text>
            <Text style={styles.aboutValue}>2026.02.06</Text>
          </View>
        </View>
      </View>

      <View style={{ height: 40 }} />

      {/* Language Modal */}
      <Modal
        visible={languageModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setLanguageModalVisible(false)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setLanguageModalVisible(false)}
        >
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>{t.settings.language}</Text>

            {(['pl', 'en'] as Language[]).map((lang) => (
              <TouchableOpacity
                key={lang}
                style={styles.modalOption}
                onPress={() => {
                  setLanguage(lang);
                  setLanguageModalVisible(false);
                }}
              >
                <Text style={[styles.modalOptionText, language === lang && styles.modalOptionSelected]}>
                  {languageNames[lang]}
                </Text>
                {language === lang && (
                  <Ionicons name="checkmark-circle" size={24} color={colors.accent} />
                )}
              </TouchableOpacity>
            ))}

            <TouchableOpacity
              style={styles.modalCancelButton}
              onPress={() => setLanguageModalVisible(false)}
            >
              <Text style={styles.modalCancelText}>{t.common.cancel}</Text>
            </TouchableOpacity>
          </View>
        </TouchableOpacity>
      </Modal>

      {/* Theme Modal */}
      <Modal
        visible={themeModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setThemeModalVisible(false)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setThemeModalVisible(false)}
        >
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>{t.settings.theme}</Text>

            {(['light', 'dark', 'system'] as ThemeMode[]).map((mode) => (
              <TouchableOpacity
                key={mode}
                style={styles.modalOption}
                onPress={() => {
                  setThemeMode(mode);
                  setThemeModalVisible(false);
                }}
              >
                <View style={styles.modalOptionLeft}>
                  <Ionicons
                    name={mode === 'light' ? 'sunny' : mode === 'dark' ? 'moon' : 'phone-portrait'}
                    size={20}
                    color={colors.textSecondary}
                    style={{ marginRight: 12 }}
                  />
                  <Text style={[styles.modalOptionText, themeMode === mode && styles.modalOptionSelected]}>
                    {themeNames[mode][language]}
                  </Text>
                </View>
                {themeMode === mode && (
                  <Ionicons name="checkmark-circle" size={24} color={colors.accent} />
                )}
              </TouchableOpacity>
            ))}

            <TouchableOpacity
              style={styles.modalCancelButton}
              onPress={() => setThemeModalVisible(false)}
            >
              <Text style={styles.modalCancelText}>{t.common.cancel}</Text>
            </TouchableOpacity>
          </View>
        </TouchableOpacity>
      </Modal>
    </ScrollView>
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>, isDark: boolean) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    section: {
      padding: 16,
      paddingBottom: 0,
    },
    sectionTitle: {
      fontSize: 13,
      fontWeight: '600',
      color: colors.textSecondary,
      marginBottom: 12,
      textTransform: 'uppercase',
      letterSpacing: 0.5,
    },
    card: {
      backgroundColor: colors.surface,
      borderRadius: 16,
      overflow: 'hidden',
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: isDark ? 0.3 : 0.05,
      shadowRadius: 2,
      elevation: 1,
    },
    settingRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: 14,
    },
    settingLeft: {
      flexDirection: 'row',
      alignItems: 'center',
      flex: 1,
    },
    icon: {
      width: 40,
      height: 40,
      borderRadius: 10,
      justifyContent: 'center',
      alignItems: 'center',
    },
    settingInfo: {
      marginLeft: 12,
      flex: 1,
    },
    settingLabel: {
      fontSize: 15,
      fontWeight: '500',
      color: colors.text,
    },
    settingDescription: {
      fontSize: 12,
      color: colors.textSecondary,
      marginTop: 2,
    },
    divider: {
      height: 1,
      backgroundColor: colors.borderLight,
      marginHorizontal: 14,
    },
    menuRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: 14,
    },
    menuLabel: {
      fontSize: 15,
      fontWeight: '500',
      color: colors.text,
      marginLeft: 12,
    },
    aboutRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      padding: 14,
    },
    aboutLabel: {
      fontSize: 15,
      color: colors.textSecondary,
    },
    aboutValue: {
      fontSize: 15,
      fontWeight: '500',
      color: colors.text,
    },
    // Modal styles
    modalOverlay: {
      flex: 1,
      backgroundColor: 'rgba(0,0,0,0.5)',
      justifyContent: 'center',
      alignItems: 'center',
      padding: 20,
    },
    modalContent: {
      backgroundColor: colors.surface,
      borderRadius: 20,
      width: '100%',
      maxWidth: 320,
      padding: 20,
    },
    modalTitle: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
      marginBottom: 16,
      textAlign: 'center',
    },
    modalOption: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingVertical: 14,
      borderBottomWidth: 1,
      borderBottomColor: colors.borderLight,
    },
    modalOptionLeft: {
      flexDirection: 'row',
      alignItems: 'center',
    },
    modalOptionText: {
      fontSize: 16,
      color: colors.text,
    },
    modalOptionSelected: {
      fontWeight: '600',
      color: colors.accent,
    },
    modalCancelButton: {
      marginTop: 16,
      alignItems: 'center',
      paddingVertical: 12,
    },
    modalCancelText: {
      fontSize: 16,
      color: colors.textSecondary,
      fontWeight: '500',
    },
  });
