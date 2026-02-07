import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { translations, Language, TranslationKeys } from '../i18n/translations';

export type ThemeMode = 'light' | 'dark' | 'system';

// Brand colors - Pasjaplywania.pl
export const brandColors = {
  blue: '#2563EB',        // Main brand blue
  blueDark: '#1D4ED8',    // Gradient darker blue
  blueDeep: '#1E40AF',    // Deep blue for headers
  blueLighter: '#3B82F6', // Lighter variant
  navy: '#1B3A6B',        // Dark navy from logo
  cyan: '#5BC8F0',        // Water splash cyan
  cyanLight: '#67D5EF',   // Light cyan drops
  orange: '#F97316',      // Orange accent from logo
  orangeLight: '#FFF7ED', // Light orange background
  white: '#FFFFFF',
};

// Theme colors
export const lightTheme = {
  mode: 'light' as const,
  colors: {
    // Backgrounds
    background: '#f9fafb',
    surface: '#ffffff',
    surfaceSecondary: '#f3f4f6',

    // Text
    text: '#111827',
    textSecondary: '#6b7280',
    textTertiary: '#9ca3af',

    // Primary (Brand blue)
    primary: brandColors.blue,
    primaryDark: brandColors.blueDark,
    primaryLight: '#DBEAFE',

    // Secondary (Instructor green)
    secondary: '#10b981',
    secondaryLight: '#ecfdf5',

    // Brand accent (orange)
    brandOrange: brandColors.orange,
    brandOrangeLight: brandColors.orangeLight,
    brandCyan: brandColors.cyan,

    // Accent colors
    warning: '#f59e0b',
    warningLight: '#fef3c7',
    error: '#ef4444',
    errorLight: '#fef2f2',
    success: '#10b981',
    successLight: '#ecfdf5',

    // Borders
    border: '#e5e7eb',
    borderLight: '#f3f4f6',

    // Shadows
    shadow: '#000000',

    // Tab bar
    tabBarBackground: '#ffffff',
    tabBarBorder: '#e5e7eb',
    tabBarInactive: '#6b7280',
  },
};

export const darkTheme = {
  mode: 'dark' as const,
  colors: {
    // Backgrounds
    background: '#111827',
    surface: '#1f2937',
    surfaceSecondary: '#374151',

    // Text
    text: '#f9fafb',
    textSecondary: '#d1d5db',
    textTertiary: '#9ca3af',

    // Primary (Brand blue - lighter for dark mode)
    primary: '#60a5fa',
    primaryDark: '#3B82F6',
    primaryLight: '#1e3a5f',

    // Secondary (Instructor green)
    secondary: '#34d399',
    secondaryLight: '#064e3b',

    // Brand accent (orange)
    brandOrange: '#FB923C',
    brandOrangeLight: '#431407',
    brandCyan: '#67E8F9',

    // Accent colors
    warning: '#fbbf24',
    warningLight: '#78350f',
    error: '#f87171',
    errorLight: '#7f1d1d',
    success: '#34d399',
    successLight: '#064e3b',

    // Borders
    border: '#374151',
    borderLight: '#4b5563',

    // Shadows
    shadow: '#000000',

    // Tab bar
    tabBarBackground: '#1f2937',
    tabBarBorder: '#374151',
    tabBarInactive: '#9ca3af',
  },
};

export type Theme = typeof lightTheme;

interface SettingsState {
  // Language
  language: Language;
  setLanguage: (language: Language) => void;
  t: TranslationKeys;

  // Theme
  themeMode: ThemeMode;
  setThemeMode: (mode: ThemeMode) => void;
  theme: Theme;
  isDark: boolean;
}

// Get system color scheme (simplified for now, would use Appearance API in real app)
const getSystemTheme = (): 'light' | 'dark' => {
  // In a real app, this would use Appearance.getColorScheme()
  return 'light';
};

export const useSettingsStore = create<SettingsState>()(
  persist(
    (set, get) => ({
      // Language
      language: 'pl',
      t: translations.pl,

      setLanguage: (language: Language) => {
        set({
          language,
          t: translations[language],
        });
      },

      // Theme
      themeMode: 'light',
      theme: lightTheme,
      isDark: false,

      setThemeMode: (mode: ThemeMode) => {
        const isDark = mode === 'dark' || (mode === 'system' && getSystemTheme() === 'dark');
        set({
          themeMode: mode,
          theme: isDark ? darkTheme : lightTheme,
          isDark,
        });
      },
    }),
    {
      name: 'ssm-settings',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({
        language: state.language,
        themeMode: state.themeMode,
      }),
      onRehydrateStorage: () => (state) => {
        if (state) {
          // Restore translations and theme after rehydration
          state.t = translations[state.language];
          const isDark = state.themeMode === 'dark' ||
            (state.themeMode === 'system' && getSystemTheme() === 'dark');
          state.theme = isDark ? darkTheme : lightTheme;
          state.isDark = isDark;
        }
      },
    }
  )
);

// Helper hook to get theme colors based on active role
export const useThemeColors = (activeRole?: 'parent' | 'instructor' | null) => {
  const { theme, isDark } = useSettingsStore();
  const accentColor = activeRole === 'instructor' ? theme.colors.secondary : theme.colors.primary;
  const accentLightColor = activeRole === 'instructor' ? theme.colors.secondaryLight : theme.colors.primaryLight;

  return {
    ...theme.colors,
    accent: accentColor,
    accentLight: accentLightColor,
    isDark,
  };
};
