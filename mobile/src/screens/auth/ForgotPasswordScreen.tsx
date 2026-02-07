import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Dimensions,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import api from '../../api/client';
import { useSettingsStore, brandColors } from '../../store/settingsStore';

const { width } = Dimensions.get('window');

export default function ForgotPasswordScreen() {
  const navigation = useNavigation();
  const [email, setEmail] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const { t } = useSettingsStore();

  const validateEmail = (email: string) => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  };

  const handleReset = async () => {
    const trimmedEmail = email.trim();

    if (!trimmedEmail) {
      Alert.alert(t.common.error, t.auth.enterCredentials);
      return;
    }

    if (!validateEmail(trimmedEmail)) {
      Alert.alert(t.common.error, 'Podaj prawidłowy adres email');
      return;
    }

    setIsLoading(true);
    try {
      await api.requestPasswordReset(trimmedEmail);
      setSent(true);
    } catch (error) {
      // Still show success (security: don't reveal if email exists)
      setSent(true);
    } finally {
      setIsLoading(false);
    }
  };

  if (sent) {
    return (
      <LinearGradient
        colors={['#3B82F6', brandColors.blue, brandColors.blueDark, brandColors.blueDeep]}
        locations={[0, 0.3, 0.7, 1]}
        style={styles.gradient}
      >
        <View style={styles.waveTop}>
          <View style={styles.waveShape1} />
        </View>

        <View style={styles.centeredContent}>
          <View style={styles.card}>
            <View style={styles.successIcon}>
              <Ionicons name="mail" size={48} color={brandColors.blue} />
            </View>
            <Text style={styles.cardTitle}>Sprawdź email</Text>
            <Text style={styles.cardDescription}>
              Jeśli podany adres email istnieje w systemie, wysłaliśmy instrukcje resetowania hasła.
            </Text>
            <TouchableOpacity
              style={styles.primaryButton}
              onPress={() => navigation.goBack()}
            >
              <LinearGradient
                colors={[brandColors.blue, brandColors.blueDark]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.primaryButtonGradient}
              >
                <Ionicons name="arrow-back" size={20} color="#fff" />
                <Text style={styles.primaryButtonText}>{t.common.back}</Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.waveBottom}>
          <View style={styles.waveShape3} />
        </View>
      </LinearGradient>
    );
  }

  return (
    <LinearGradient
      colors={['#3B82F6', brandColors.blue, brandColors.blueDark, brandColors.blueDeep]}
      locations={[0, 0.3, 0.7, 1]}
      style={styles.gradient}
    >
      <View style={styles.waveTop}>
        <View style={styles.waveShape1} />
      </View>

      <KeyboardAvoidingView
        style={styles.container}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        {/* Back button */}
        <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color="#fff" />
        </TouchableOpacity>

        <View style={styles.centeredContent}>
          <View style={styles.card}>
            <View style={styles.keyIcon}>
              <Ionicons name="key-outline" size={40} color={brandColors.blue} />
            </View>

            <Text style={styles.cardTitle}>{t.auth.forgotPassword}</Text>
            <Text style={styles.cardDescription}>
              Wprowadź adres email powiązany z kontem, a wyślemy Ci instrukcje resetowania hasła.
            </Text>

            <View style={styles.inputContainer}>
              <Ionicons name="mail-outline" size={20} color="#9ca3af" style={styles.inputIcon} />
              <TextInput
                style={styles.input}
                placeholder={t.auth.email}
                placeholderTextColor="#9ca3af"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
              />
              {email.length > 0 && (
                <TouchableOpacity onPress={() => setEmail('')}>
                  <Ionicons name="close-circle" size={20} color="#9ca3af" />
                </TouchableOpacity>
              )}
            </View>

            <TouchableOpacity
              style={[styles.primaryButton, isLoading && styles.buttonDisabled]}
              onPress={handleReset}
              disabled={isLoading}
            >
              <LinearGradient
                colors={[brandColors.blue, brandColors.blueDark]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.primaryButtonGradient}
              >
                {isLoading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <>
                    <Ionicons name="send" size={20} color="#fff" />
                    <Text style={styles.primaryButtonText}>Wyślij</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.linkButton}
              onPress={() => navigation.goBack()}
            >
              <Ionicons name="arrow-back" size={16} color={brandColors.blue} />
              <Text style={styles.linkText}>Wróć do logowania</Text>
            </TouchableOpacity>
          </View>
        </View>
      </KeyboardAvoidingView>

      <View style={styles.waveBottom}>
        <View style={styles.waveShape3} />
      </View>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  gradient: {
    flex: 1,
  },
  container: {
    flex: 1,
  },
  centeredContent: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 24,
  },

  // Waves
  waveTop: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 120,
    overflow: 'hidden',
  },
  waveShape1: {
    position: 'absolute',
    top: -60,
    left: -20,
    width: width * 0.7,
    height: 120,
    borderRadius: 60,
    backgroundColor: 'rgba(255,255,255,0.05)',
    transform: [{ rotate: '-5deg' }],
  },
  waveBottom: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 80,
    overflow: 'hidden',
  },
  waveShape3: {
    position: 'absolute',
    bottom: -40,
    left: -20,
    right: -20,
    height: 80,
    borderTopLeftRadius: 300,
    borderTopRightRadius: 200,
    backgroundColor: 'rgba(0,0,0,0.08)',
  },

  // Back button
  backButton: {
    marginTop: 60,
    marginLeft: 20,
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
  },

  // Card
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 12,
  },
  keyIcon: {
    alignSelf: 'center',
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  successIcon: {
    alignSelf: 'center',
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 22,
    fontWeight: '700',
    color: '#1B3A6B',
    textAlign: 'center',
    marginBottom: 8,
  },
  cardDescription: {
    fontSize: 15,
    color: '#6b7280',
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 22,
  },

  // Input
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f9fafb',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e5e7eb',
    paddingHorizontal: 16,
    marginBottom: 20,
    height: 56,
  },
  inputIcon: {
    marginRight: 12,
  },
  input: {
    flex: 1,
    fontSize: 16,
    color: '#111827',
  },

  // Primary button
  primaryButton: {
    borderRadius: 12,
    overflow: 'hidden',
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  primaryButtonGradient: {
    height: 56,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 12,
    gap: 8,
  },
  primaryButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '700',
  },

  // Link button
  linkButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 20,
    gap: 6,
  },
  linkText: {
    fontSize: 15,
    fontWeight: '500',
    color: brandColors.blue,
  },
});
