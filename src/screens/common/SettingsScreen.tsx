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
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../store/authStore';

export default function SettingsScreen() {
  const { user } = useAuthStore();
  const isInstructor = user?.type === 'instructor';
  const primaryColor = isInstructor ? '#10b981' : '#3b82f6';

  const [pushEnabled, setPushEnabled] = useState(true);
  const [emailEnabled, setEmailEnabled] = useState(true);
  const [smsEnabled, setSmsEnabled] = useState(false);
  const [darkMode, setDarkMode] = useState(false);

  const handleClearCache = () => {
    Alert.alert(
      'Wyczyść pamięć podręczną',
      'Czy na pewno chcesz wyczyścić pamięć podręczną aplikacji?',
      [
        { text: 'Anuluj', style: 'cancel' },
        {
          text: 'Wyczyść',
          onPress: () => {
            Alert.alert('Sukces', 'Pamięć podręczna została wyczyszczona');
          },
        },
      ]
    );
  };

  const handleContact = () => {
    Linking.openURL('mailto:kontakt@szkolkaplywania.pl');
  };

  const handleWebsite = () => {
    Linking.openURL('https://woykow.pl');
  };

  return (
    <ScrollView style={styles.container}>
      {/* Notifications Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Powiadomienia</Text>

        <View style={styles.card}>
          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#fef2f2' }]}>
                <Ionicons name="notifications" size={20} color="#ef4444" />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>Powiadomienia push</Text>
                <Text style={styles.settingDescription}>
                  Otrzymuj powiadomienia o zajęciach
                </Text>
              </View>
            </View>
            <Switch
              value={pushEnabled}
              onValueChange={setPushEnabled}
              trackColor={{ false: '#d1d5db', true: primaryColor + '60' }}
              thumbColor={pushEnabled ? primaryColor : '#9ca3af'}
            />
          </View>

          <View style={styles.divider} />

          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#eff6ff' }]}>
                <Ionicons name="mail" size={20} color="#3b82f6" />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>Powiadomienia email</Text>
                <Text style={styles.settingDescription}>
                  Podsumowania i ważne informacje
                </Text>
              </View>
            </View>
            <Switch
              value={emailEnabled}
              onValueChange={setEmailEnabled}
              trackColor={{ false: '#d1d5db', true: primaryColor + '60' }}
              thumbColor={emailEnabled ? primaryColor : '#9ca3af'}
            />
          </View>

          <View style={styles.divider} />

          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#ecfdf5' }]}>
                <Ionicons name="chatbubble" size={20} color="#10b981" />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>Powiadomienia SMS</Text>
                <Text style={styles.settingDescription}>
                  Pilne informacje (mogą być płatne)
                </Text>
              </View>
            </View>
            <Switch
              value={smsEnabled}
              onValueChange={setSmsEnabled}
              trackColor={{ false: '#d1d5db', true: primaryColor + '60' }}
              thumbColor={smsEnabled ? primaryColor : '#9ca3af'}
            />
          </View>
        </View>
      </View>

      {/* Appearance Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Wygląd</Text>

        <View style={styles.card}>
          <View style={styles.settingRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#f3e8ff' }]}>
                <Ionicons name="moon" size={20} color="#a855f7" />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>Tryb ciemny</Text>
                <Text style={styles.settingDescription}>
                  Oszczędza baterię i oczy
                </Text>
              </View>
            </View>
            <Switch
              value={darkMode}
              onValueChange={setDarkMode}
              trackColor={{ false: '#d1d5db', true: primaryColor + '60' }}
              thumbColor={darkMode ? primaryColor : '#9ca3af'}
            />
          </View>
        </View>
      </View>

      {/* Storage Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Dane</Text>

        <View style={styles.card}>
          <TouchableOpacity style={styles.menuRow} onPress={handleClearCache}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#fef3c7' }]}>
                <Ionicons name="trash" size={20} color="#f59e0b" />
              </View>
              <View style={styles.settingInfo}>
                <Text style={styles.settingLabel}>Wyczyść pamięć podręczną</Text>
                <Text style={styles.settingDescription}>
                  Usuń tymczasowe pliki
                </Text>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>
        </View>
      </View>

      {/* Support Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Wsparcie</Text>

        <View style={styles.card}>
          <TouchableOpacity style={styles.menuRow} onPress={handleContact}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#eff6ff' }]}>
                <Ionicons name="help-circle" size={20} color="#3b82f6" />
              </View>
              <Text style={styles.menuLabel}>Kontakt z pomocą</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity style={styles.menuRow} onPress={handleWebsite}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#ecfdf5' }]}>
                <Ionicons name="globe" size={20} color="#10b981" />
              </View>
              <Text style={styles.menuLabel}>Nasza strona WWW</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity style={styles.menuRow}>
            <View style={styles.settingLeft}>
              <View style={[styles.icon, { backgroundColor: '#fef2f2' }]}>
                <Ionicons name="bug" size={20} color="#ef4444" />
              </View>
              <Text style={styles.menuLabel}>Zgłoś błąd</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#9ca3af" />
          </TouchableOpacity>
        </View>
      </View>

      {/* About Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>O aplikacji</Text>

        <View style={styles.card}>
          <View style={styles.aboutRow}>
            <Text style={styles.aboutLabel}>Wersja</Text>
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
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  section: {
    padding: 16,
    paddingBottom: 0,
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: '#6b7280',
    marginBottom: 12,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
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
    color: '#111827',
  },
  settingDescription: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 2,
  },
  divider: {
    height: 1,
    backgroundColor: '#f3f4f6',
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
    color: '#111827',
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
    color: '#6b7280',
  },
  aboutValue: {
    fontSize: 15,
    fontWeight: '500',
    color: '#111827',
  },
});
