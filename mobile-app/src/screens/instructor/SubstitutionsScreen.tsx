import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export default function SubstitutionsScreen() {
  return (
    <View style={styles.container}>
      <Text style={styles.text}>SubstitutionsScreen</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#f9fafb' },
  text: { fontSize: 18, color: '#374151' },
});
