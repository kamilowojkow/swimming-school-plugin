import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';

const BIOMETRIC_ENABLED_KEY = 'ssm_biometric_enabled';
const BIOMETRIC_CREDENTIALS_KEY = 'ssm_biometric_credentials';

export interface BiometricAuthResult {
  success: boolean;
  error?: string;
}

/**
 * Check if biometric authentication is available on device
 */
export async function isBiometricAvailable(): Promise<boolean> {
  try {
    const compatible = await LocalAuthentication.hasHardwareAsync();
    if (!compatible) return false;

    const enrolled = await LocalAuthentication.isEnrolledAsync();
    return enrolled;
  } catch (error) {
    console.error('Error checking biometric availability:', error);
    return false;
  }
}

/**
 * Get available biometric types
 */
export async function getBiometricTypes(): Promise<LocalAuthentication.AuthenticationType[]> {
  try {
    return await LocalAuthentication.supportedAuthenticationTypesAsync();
  } catch (error) {
    console.error('Error getting biometric types:', error);
    return [];
  }
}

/**
 * Get biometric type name for display
 */
export async function getBiometricTypeName(): Promise<string> {
  const types = await getBiometricTypes();

  if (types.includes(LocalAuthentication.AuthenticationType.FACIAL_RECOGNITION)) {
    return 'Face ID';
  }
  if (types.includes(LocalAuthentication.AuthenticationType.FINGERPRINT)) {
    return 'Touch ID';
  }
  if (types.includes(LocalAuthentication.AuthenticationType.IRIS)) {
    return 'Iris';
  }

  return 'Biometria';
}

/**
 * Check if biometric login is enabled by user
 */
export async function isBiometricEnabled(): Promise<boolean> {
  try {
    const enabled = await SecureStore.getItemAsync(BIOMETRIC_ENABLED_KEY);
    return enabled === 'true';
  } catch (error) {
    console.error('Error checking biometric enabled:', error);
    return false;
  }
}

/**
 * Enable biometric login and store credentials securely
 */
export async function enableBiometricLogin(email: string, password: string): Promise<boolean> {
  try {
    // Verify biometric first
    const authResult = await authenticateWithBiometric();
    if (!authResult.success) {
      return false;
    }

    // Store credentials securely
    await SecureStore.setItemAsync(
      BIOMETRIC_CREDENTIALS_KEY,
      JSON.stringify({ email, password })
    );
    await SecureStore.setItemAsync(BIOMETRIC_ENABLED_KEY, 'true');

    return true;
  } catch (error) {
    console.error('Error enabling biometric login:', error);
    return false;
  }
}

/**
 * Disable biometric login and clear stored credentials
 */
export async function disableBiometricLogin(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(BIOMETRIC_CREDENTIALS_KEY);
    await SecureStore.setItemAsync(BIOMETRIC_ENABLED_KEY, 'false');
  } catch (error) {
    console.error('Error disabling biometric login:', error);
  }
}

/**
 * Authenticate user with biometric
 */
export async function authenticateWithBiometric(): Promise<BiometricAuthResult> {
  try {
    const result = await LocalAuthentication.authenticateAsync({
      promptMessage: 'Zaloguj się za pomocą biometrii',
      fallbackLabel: 'Użyj hasła',
      disableDeviceFallback: false,
      cancelLabel: 'Anuluj',
    });

    if (result.success) {
      return { success: true };
    }

    return {
      success: false,
      error: result.error || 'Uwierzytelnianie nie powiodło się',
    };
  } catch (error) {
    console.error('Error during biometric authentication:', error);
    return {
      success: false,
      error: 'Wystąpił błąd podczas uwierzytelniania',
    };
  }
}

/**
 * Get stored credentials after biometric auth
 */
export async function getStoredCredentials(): Promise<{ email: string; password: string } | null> {
  try {
    const credentials = await SecureStore.getItemAsync(BIOMETRIC_CREDENTIALS_KEY);
    if (!credentials) return null;

    return JSON.parse(credentials);
  } catch (error) {
    console.error('Error getting stored credentials:', error);
    return null;
  }
}

/**
 * Perform biometric login - authenticate and return credentials
 */
export async function performBiometricLogin(): Promise<{ email: string; password: string } | null> {
  try {
    // Check if biometric is enabled
    const enabled = await isBiometricEnabled();
    if (!enabled) return null;

    // Authenticate
    const authResult = await authenticateWithBiometric();
    if (!authResult.success) return null;

    // Get stored credentials
    return await getStoredCredentials();
  } catch (error) {
    console.error('Error performing biometric login:', error);
    return null;
  }
}
