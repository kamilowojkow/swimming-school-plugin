import React, { useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Animated,
  Dimensions,
  Image,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { brandColors } from '../store/settingsStore';

const { width } = Dimensions.get('window');

interface SplashScreenProps {
  onFinish?: () => void;
}

export default function SplashScreen({ onFinish }: SplashScreenProps) {
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const logoScale = useRef(new Animated.Value(0.8)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 500,
        useNativeDriver: true,
      }),
      Animated.spring(logoScale, {
        toValue: 1,
        friction: 8,
        tension: 40,
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  return (
    <Animated.View style={[styles.container, { opacity: fadeAnim }]}>
      <LinearGradient
        colors={['#3B82F6', brandColors.blue, brandColors.blueDark, brandColors.blueDeep]}
        locations={[0, 0.3, 0.7, 1]}
        style={styles.gradient}
      >
        {/* Top wave decoration */}
        <View style={styles.waveTop}>
          <View style={styles.waveShape1} />
          <View style={styles.waveShape2} />
        </View>

        {/* Logo */}
        <View style={styles.logoArea}>
          <Animated.View style={[styles.logoContainer, { transform: [{ scale: logoScale }] }]}>
            <Image
              source={require('../../assets/icon.png')}
              style={styles.logoImage}
              resizeMode="contain"
            />
          </Animated.View>

          <Text style={styles.brandName}>Pasjaplywania.pl</Text>
          <View style={styles.brandDotRow}>
            <View style={styles.brandDot} />
          </View>
        </View>

        {/* Loading indicator */}
        <View style={styles.loadingContainer}>
          <View style={styles.loadingDots}>
            <LoadingDot delay={0} />
            <LoadingDot delay={150} />
            <LoadingDot delay={300} />
          </View>
        </View>

        {/* Bottom wave decoration */}
        <View style={styles.waveBottom}>
          <View style={styles.waveShape3} />
          <View style={styles.waveShape4} />
        </View>
      </LinearGradient>
    </Animated.View>
  );
}

interface LoadingDotProps {
  delay: number;
}

function LoadingDot({ delay }: LoadingDotProps) {
  const opacity = useRef(new Animated.Value(0.3)).current;
  const scale = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.parallel([
          Animated.timing(opacity, {
            toValue: 1,
            duration: 400,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1.2,
            duration: 400,
            useNativeDriver: true,
          }),
        ]),
        Animated.parallel([
          Animated.timing(opacity, {
            toValue: 0.3,
            duration: 400,
            useNativeDriver: true,
          }),
          Animated.timing(scale, {
            toValue: 1,
            duration: 400,
            useNativeDriver: true,
          }),
        ]),
      ])
    );
    animation.start();
    return () => animation.stop();
  }, [delay]);

  return (
    <Animated.View
      style={[
        styles.dot,
        {
          opacity,
          transform: [{ scale }],
        },
      ]}
    />
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  gradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },

  // Waves
  waveTop: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 150,
    overflow: 'hidden',
  },
  waveShape1: {
    position: 'absolute',
    top: -80,
    left: -30,
    width: width * 1.2,
    height: 150,
    borderBottomLeftRadius: 300,
    borderBottomRightRadius: 200,
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  waveShape2: {
    position: 'absolute',
    top: -50,
    right: -50,
    width: width * 0.6,
    height: 120,
    borderBottomLeftRadius: 200,
    backgroundColor: 'rgba(255,255,255,0.03)',
  },
  waveBottom: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 120,
    overflow: 'hidden',
  },
  waveShape3: {
    position: 'absolute',
    bottom: -60,
    left: -30,
    right: -30,
    height: 120,
    borderTopLeftRadius: 400,
    borderTopRightRadius: 250,
    backgroundColor: 'rgba(0,0,0,0.06)',
  },
  waveShape4: {
    position: 'absolute',
    bottom: -80,
    left: -20,
    right: -20,
    height: 100,
    borderTopLeftRadius: 200,
    borderTopRightRadius: 350,
    backgroundColor: 'rgba(0,0,0,0.04)',
  },

  // Logo area
  logoArea: {
    alignItems: 'center',
  },
  logoContainer: {
    width: 140,
    height: 140,
    borderRadius: 32,
    overflow: 'hidden',
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
    elevation: 10,
  },
  logoImage: {
    width: 140,
    height: 140,
  },
  brandName: {
    fontSize: 26,
    fontWeight: '800',
    color: '#ffffff',
    letterSpacing: 0.5,
  },
  brandDotRow: {
    marginTop: 6,
    alignItems: 'center',
  },
  brandDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: brandColors.orange,
  },

  // Loading
  loadingContainer: {
    position: 'absolute',
    bottom: 140,
    left: 0,
    right: 0,
    alignItems: 'center',
  },
  loadingDots: {
    flexDirection: 'row',
    gap: 8,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#fff',
  },
});
