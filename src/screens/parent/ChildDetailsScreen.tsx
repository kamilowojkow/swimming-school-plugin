import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export default function ChildDetailsScreen() {
  return (
    <View style={styles.container}>
      <Text style={styles.text}>ChildDetailsScreen</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#f9fafb' },
  text: { fontSize: 18, color: '#374151' },
});
