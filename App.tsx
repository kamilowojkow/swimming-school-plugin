import React, { useEffect, useState } from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import AppNavigator from './src/navigation/AppNavigator';
import SplashScreen from './src/screens/SplashScreen';
import { useAuthStore } from './src/store/authStore';
import { useSettingsStore } from './src/store/settingsStore';

export default function App() {
  const { checkAuth, isLoading: authLoading } = useAuthStore();
  const { isDark } = useSettingsStore();
  const [showSplash, setShowSplash] = useState(true);

  useEffect(() => {
    const init = async () => {
      await checkAuth();
      // Show splash for at least 1.5s for smooth UX
      setTimeout(() => {
        setShowSplash(false);
      }, 1500);
    };
    init();
  }, []);

  return (
    <SafeAreaProvider>
      <StatusBar style={isDark ? 'light' : 'dark'} />
      {showSplash || authLoading ? <SplashScreen /> : <AppNavigator />}
    </SafeAreaProvider>
  );
}
