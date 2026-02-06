import React, { useEffect, useRef } from 'react';
import { View, Text, StyleSheet, Animated, Dimensions } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useSettingsStore, useThemeColors } from '../store/settingsStore';

const { width } = Dimensions.get('window');

interface SplashScreenProps {
  onFinish?: () => void;
}

export default function SplashScreen({ onFinish }: SplashScreenProps) {
  const { isDark } = useSettingsStore();
  const colors = useThemeColors();

  const fadeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(0.8)).current;
  const waveAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Start animations
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }),
      Animated.spring(scaleAnim, {
        toValue: 1,
        tension: 50,
        friction: 7,
        useNativeDriver: true,
      }),
    ]).start();

    // Wave animation loop
    Animated.loop(
      Animated.sequence([
        Animated.timing(waveAnim, {
          toValue: 1,
          duration: 1500,
          useNativeDriver: true,
        }),
        Animated.timing(waveAnim, {
          toValue: 0,
          duration: 1500,
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, []);

  const waveTranslate = waveAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 10],
  });

  const styles = createStyles(colors, isDark);

  return (
    <View style={styles.container}>
      <Animated.View
        style={[
          styles.logoContainer,
          {
            opacity: fadeAnim,
            transform: [{ scale: scaleAnim }],
          },
        ]}
      >
        <View style={styles.iconCircle}>
          <Animated.View style={{ transform: [{ translateY: waveTranslate }] }}>
            <Ionicons name="water" size={60} color="#fff" />
          </Animated.View>
        </View>
        <Text style={styles.title}>Szkółka Pływania</Text>
        <Text style={styles.subtitle}>Swimming School Mobile</Text>
      </Animated.View>

      <View style={styles.loadingContainer}>
        <View style={styles.loadingDots}>
          <LoadingDot delay={0} colors={colors} />
          <LoadingDot delay={200} colors={colors} />
          <LoadingDot delay={400} colors={colors} />
        </View>
        <Text style={styles.loadingText}>Ładowanie...</Text>
      </View>

      <Text style={styles.version}>v1.0.0</Text>
    </View>
  );
}

interface LoadingDotProps {
  delay: number;
  colors: ReturnType<typeof useThemeColors>;
}

function LoadingDot({ delay, colors }: LoadingDotProps) {
  const opacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.timing(opacity, {
          toValue: 1,
          duration: 400,
          useNativeDriver: true,
        }),
        Animated.timing(opacity, {
          toValue: 0.3,
          duration: 400,
          useNativeDriver: true,
        }),
      ])
    );
    animation.start();
    return () => animation.stop();
  }, [delay]);

  return (
    <Animated.View
      style={[
        {
          width: 10,
          height: 10,
          borderRadius: 5,
          backgroundColor: colors.primary,
          marginHorizontal: 4,
          opacity,
        },
      ]}
    />
  );
}

const createStyles = (colors: ReturnType<typeof useThemeColors>, isDark: boolean) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
      justifyContent: 'center',
      alignItems: 'center',
    },
    logoContainer: {
      alignItems: 'center',
    },
    iconCircle: {
      width: 120,
      height: 120,
      borderRadius: 60,
      backgroundColor: colors.primary,
      justifyContent: 'center',
      alignItems: 'center',
      shadowColor: colors.primary,
      shadowOffset: { width: 0, height: 8 },
      shadowOpacity: 0.4,
      shadowRadius: 16,
      elevation: 10,
      marginBottom: 24,
    },
    title: {
      fontSize: 32,
      fontWeight: '700',
      color: colors.text,
      marginBottom: 8,
    },
    subtitle: {
      fontSize: 16,
      color: colors.textSecondary,
      letterSpacing: 1,
    },
    loadingContainer: {
      position: 'absolute',
      bottom: 120,
      alignItems: 'center',
    },
    loadingDots: {
      flexDirection: 'row',
      marginBottom: 12,
    },
    loadingText: {
      fontSize: 14,
      color: colors.textTertiary,
    },
    version: {
      position: 'absolute',
      bottom: 40,
      fontSize: 12,
      color: colors.textTertiary,
    },
  });
