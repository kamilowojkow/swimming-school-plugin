import React, { useEffect } from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import AppNavigator from './src/navigation/AppNavigator';
import { useAuthStore } from './src/store/authStore';
import api from './src/api/client';

// Konfiguracja powiadomień
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

export default function App() {
  const { checkAuth, isAuthenticated } = useAuthStore();

  useEffect(() => {
    // Sprawdź autentykację przy starcie
    checkAuth();
  }, []);

  useEffect(() => {
    // Rejestracja push notifications po zalogowaniu
    if (isAuthenticated) {
      registerForPushNotifications();
    }
  }, [isAuthenticated]);

  const registerForPushNotifications = async () => {
    try {
      const { status: existingStatus } = await Notifications.getPermissionsAsync();
      let finalStatus = existingStatus;

      if (existingStatus !== 'granted') {
        const { status } = await Notifications.requestPermissionsAsync();
        finalStatus = status;
      }

      if (finalStatus !== 'granted') {
        console.log('Push notification permission denied');
        return;
      }

      const token = await Notifications.getExpoPushTokenAsync({
        projectId: 'your-expo-project-id', // Zmień na swój project ID
      });

      const platform = Platform.OS as 'ios' | 'android';
      await api.registerPushToken(token.data, platform);

      console.log('Push token registered:', token.data);
    } catch (error) {
      console.error('Error registering push notifications:', error);
    }
  };

  return (
    <SafeAreaProvider>
      <StatusBar style="auto" />
      <AppNavigator />
    </SafeAreaProvider>
  );
}
