import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Alert,
  Linking,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { format } from 'date-fns';
import { pl } from 'date-fns/locale';
import api from '../../api/client';

interface Payment {
  id: number;
  title: string;
  description?: string;
  total_amount: number;
  paid_amount: number;
  remaining_amount: number;
  due_date: string;
  status: 'pending' | 'partial' | 'paid' | 'overdue';
  child_name?: string;
  payment_url?: string;
  created_at: string;
}

interface PaymentHistory {
  id: number;
  amount: number;
  payment_date: string;
  payment_method: string;
  invoice_title: string;
}

const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string; icon: string }> = {
  pending: { label: 'Oczekuje', color: '#f59e0b', bg: '#fef3c7', icon: 'time' },
  partial: { label: 'Częściowa', color: '#3b82f6', bg: '#eff6ff', icon: 'pie-chart' },
  paid: { label: 'Opłacone', color: '#16a34a', bg: '#dcfce7', icon: 'checkmark-circle' },
  overdue: { label: 'Zaległe', color: '#dc2626', bg: '#fef2f2', icon: 'alert-circle' },
};

const safeFormatDate = (dateStr: string | undefined, formatStr: string): string => {
  if (!dateStr) return '-';
  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    return format(date, formatStr, { locale: pl });
  } catch {
    return '-';
  }
};

export default function PaymentsScreen() {
  const [payments, setPayments] = useState<Payment[]>([]);
  const [history, setHistory] = useState<PaymentHistory[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<'pending' | 'history'>('pending');

  const fetchPayments = async () => {
    try {
      const [paymentsData, historyData] = await Promise.all([
        api.getPayments(),
        api.getPaymentHistory(),
      ]);
      // Handle both array and object with payments key
      const paymentsArray = Array.isArray(paymentsData) ? paymentsData : (paymentsData?.payments || []);
      const historyArray = Array.isArray(historyData) ? historyData : (historyData?.history || []);
      setPayments(paymentsArray);
      setHistory(historyArray);
    } catch (error) {
      console.error('Error fetching payments:', error);
      setPayments([]);
      setHistory([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchPayments();
    setRefreshing(false);
  };

  const handlePayOnline = (payment: Payment) => {
    if (payment.payment_url) {
      Alert.alert(
        'Płatność online',
        `Czy chcesz przejść do płatności ${payment.remaining_amount.toFixed(2)} zł?`,
        [
          { text: 'Anuluj', style: 'cancel' },
          {
            text: 'Zapłać',
            onPress: () => Linking.openURL(payment.payment_url!),
          },
        ]
      );
    }
  };

  const pendingPayments = payments.filter((p) => p.status !== 'paid');
  const paidPayments = payments.filter((p) => p.status === 'paid');

  const totalPending = pendingPayments.reduce((sum, p) => sum + p.remaining_amount, 0);
  const overdueCount = pendingPayments.filter((p) => p.status === 'overdue').length;

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#3b82f6" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Summary Card */}
      <View style={styles.summaryCard}>
        <View style={styles.summaryMain}>
          <Text style={styles.summaryLabel}>Do zapłaty</Text>
          <Text style={styles.summaryAmount}>{totalPending.toFixed(2)} zł</Text>
        </View>
        <View style={styles.summaryStats}>
          <View style={styles.summaryStat}>
            <Text style={styles.summaryStatValue}>{pendingPayments.length}</Text>
            <Text style={styles.summaryStatLabel}>oczekujących</Text>
          </View>
          {overdueCount > 0 && (
            <View style={[styles.summaryStat, styles.summaryStatDanger]}>
              <Text style={[styles.summaryStatValue, { color: '#dc2626' }]}>{overdueCount}</Text>
              <Text style={[styles.summaryStatLabel, { color: '#dc2626' }]}>zaległych</Text>
            </View>
          )}
        </View>
      </View>

      {/* Tabs */}
      <View style={styles.tabsContainer}>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'pending' && styles.tabActive]}
          onPress={() => setActiveTab('pending')}
        >
          <Text style={[styles.tabText, activeTab === 'pending' && styles.tabTextActive]}>
            Bieżące ({pendingPayments.length})
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'history' && styles.tabActive]}
          onPress={() => setActiveTab('history')}
        >
          <Text style={[styles.tabText, activeTab === 'history' && styles.tabTextActive]}>
            Historia
          </Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {activeTab === 'pending' ? (
          <>
            {pendingPayments.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="checkmark-circle" size={64} color="#16a34a" />
                <Text style={styles.emptyTitle}>Wszystko opłacone!</Text>
                <Text style={styles.emptySubtitle}>Nie masz żadnych oczekujących płatności</Text>
              </View>
            ) : (
              pendingPayments.map((payment) => {
                const statusConfig = STATUS_CONFIG[payment.status];
                const progress = (payment.paid_amount / payment.total_amount) * 100;

                return (
                  <View key={payment.id} style={styles.paymentCard}>
                    <View style={styles.paymentHeader}>
                      <View style={styles.paymentTitleRow}>
                        <Text style={styles.paymentTitle}>{payment.title}</Text>
                        <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                          <Ionicons
                            name={statusConfig.icon as any}
                            size={12}
                            color={statusConfig.color}
                          />
                          <Text style={[styles.statusText, { color: statusConfig.color }]}>
                            {statusConfig.label}
                          </Text>
                        </View>
                      </View>
                      {payment.child_name && (
                        <Text style={styles.paymentChild}>{payment.child_name}</Text>
                      )}
                    </View>

                    <View style={styles.paymentAmounts}>
                      <View style={styles.amountRow}>
                        <Text style={styles.amountLabel}>Kwota całkowita</Text>
                        <Text style={styles.amountValue}>{payment.total_amount.toFixed(2)} zł</Text>
                      </View>
                      {payment.paid_amount > 0 && (
                        <View style={styles.amountRow}>
                          <Text style={styles.amountLabel}>Wpłacono</Text>
                          <Text style={[styles.amountValue, { color: '#16a34a' }]}>
                            -{payment.paid_amount.toFixed(2)} zł
                          </Text>
                        </View>
                      )}
                      <View style={[styles.amountRow, styles.amountRowTotal]}>
                        <Text style={styles.amountLabelBold}>Do zapłaty</Text>
                        <Text style={styles.amountValueBold}>
                          {payment.remaining_amount.toFixed(2)} zł
                        </Text>
                      </View>
                    </View>

                    {payment.paid_amount > 0 && (
                      <View style={styles.progressContainer}>
                        <View style={styles.progressBar}>
                          <View style={[styles.progressFill, { width: `${progress}%` }]} />
                        </View>
                        <Text style={styles.progressText}>{Math.round(progress)}% opłacone</Text>
                      </View>
                    )}

                    <View style={styles.paymentFooter}>
                      <View style={styles.dueDate}>
                        <Ionicons name="calendar-outline" size={14} color="#6b7280" />
                        <Text style={styles.dueDateText}>
                          Termin: {safeFormatDate(payment.due_date, 'd MMM yyyy')}
                        </Text>
                      </View>

                      {payment.payment_url && (
                        <TouchableOpacity
                          style={styles.payButton}
                          onPress={() => handlePayOnline(payment)}
                        >
                          <Ionicons name="card" size={16} color="#fff" />
                          <Text style={styles.payButtonText}>Zapłać online</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                );
              })
            )}

            {/* Paid payments section */}
            {paidPayments.length > 0 && (
              <View style={styles.paidSection}>
                <Text style={styles.paidSectionTitle}>Ostatnio opłacone</Text>
                {paidPayments.slice(0, 5).map((payment) => (
                  <View key={payment.id} style={styles.paidCard}>
                    <View style={styles.paidIcon}>
                      <Ionicons name="checkmark-circle" size={24} color="#16a34a" />
                    </View>
                    <View style={styles.paidInfo}>
                      <Text style={styles.paidTitle}>{payment.title}</Text>
                      <Text style={styles.paidDate}>
                        {safeFormatDate(payment.created_at, 'd MMM yyyy')}
                      </Text>
                    </View>
                    <Text style={styles.paidAmount}>{payment.total_amount.toFixed(2)} zł</Text>
                  </View>
                ))}
              </View>
            )}
          </>
        ) : (
          // History Tab
          <>
            {history.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="receipt-outline" size={64} color="#d1d5db" />
                <Text style={styles.emptyTitle}>Brak historii</Text>
                <Text style={styles.emptySubtitle}>Historia płatności pojawi się tutaj</Text>
              </View>
            ) : (
              history.map((item, index) => {
                const currentMonth = safeFormatDate(item.payment_date, 'yyyy-MM');
                const prevMonth = index > 0 ? safeFormatDate(history[index - 1].payment_date, 'yyyy-MM') : '';
                const showDateHeader = index === 0 || currentMonth !== prevMonth;

                return (
                  <View key={item.id}>
                    {showDateHeader && (
                      <View style={styles.historyDateHeader}>
                        <Text style={styles.historyDateText}>
                          {safeFormatDate(item.payment_date, 'LLLL yyyy')}
                        </Text>
                      </View>
                    )}
                    <View style={styles.historyCard}>
                      <View style={styles.historyIcon}>
                        <Ionicons name="receipt" size={20} color="#3b82f6" />
                      </View>
                      <View style={styles.historyInfo}>
                        <Text style={styles.historyTitle}>{item.invoice_title}</Text>
                        <View style={styles.historyMeta}>
                          <Text style={styles.historyDate}>
                            {safeFormatDate(item.payment_date, 'd MMM')}
                          </Text>
                          <Text style={styles.historyMethod}>{item.payment_method}</Text>
                        </View>
                      </View>
                      <Text style={styles.historyAmount}>{item.amount?.toFixed(2) ?? '0.00'} zł</Text>
                    </View>
                  </View>
                );
              })
            )}
          </>
        )}

        <View style={{ height: 24 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  summaryCard: {
    backgroundColor: '#3b82f6',
    margin: 16,
    borderRadius: 20,
    padding: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryMain: {},
  summaryLabel: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
  },
  summaryAmount: {
    fontSize: 32,
    fontWeight: '700',
    color: '#fff',
    marginTop: 4,
  },
  summaryStats: {
    flexDirection: 'row',
    gap: 16,
  },
  summaryStat: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.15)',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
  },
  summaryStatDanger: {
    backgroundColor: 'rgba(220, 38, 38, 0.2)',
  },
  summaryStatValue: {
    fontSize: 20,
    fontWeight: '700',
    color: '#fff',
  },
  summaryStatLabel: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.8)',
  },
  tabsContainer: {
    flexDirection: 'row',
    marginHorizontal: 16,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 4,
  },
  tab: {
    flex: 1,
    paddingVertical: 12,
    alignItems: 'center',
    borderRadius: 10,
  },
  tabActive: {
    backgroundColor: '#3b82f6',
  },
  tabText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
  },
  tabTextActive: {
    color: '#fff',
  },
  content: {
    flex: 1,
    padding: 16,
  },
  emptyState: {
    alignItems: 'center',
    padding: 48,
    backgroundColor: '#fff',
    borderRadius: 20,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#374151',
    marginTop: 16,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#6b7280',
    marginTop: 4,
    textAlign: 'center',
  },
  paymentCard: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  paymentHeader: {
    marginBottom: 16,
  },
  paymentTitleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  paymentTitle: {
    fontSize: 17,
    fontWeight: '600',
    color: '#111827',
    flex: 1,
    marginRight: 12,
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
    gap: 4,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  paymentChild: {
    fontSize: 13,
    color: '#6b7280',
    marginTop: 4,
  },
  paymentAmounts: {
    backgroundColor: '#f9fafb',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
  },
  amountRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  amountRowTotal: {
    borderTopWidth: 1,
    borderTopColor: '#e5e7eb',
    marginTop: 6,
    paddingTop: 12,
  },
  amountLabel: {
    fontSize: 14,
    color: '#6b7280',
  },
  amountValue: {
    fontSize: 14,
    color: '#374151',
  },
  amountLabelBold: {
    fontSize: 15,
    fontWeight: '600',
    color: '#111827',
  },
  amountValueBold: {
    fontSize: 18,
    fontWeight: '700',
    color: '#111827',
  },
  progressContainer: {
    marginBottom: 16,
  },
  progressBar: {
    height: 6,
    backgroundColor: '#e5e7eb',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#16a34a',
    borderRadius: 3,
  },
  progressText: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 6,
    textAlign: 'right',
  },
  paymentFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  dueDate: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  dueDateText: {
    fontSize: 13,
    color: '#6b7280',
  },
  payButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#3b82f6',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
    gap: 6,
  },
  payButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#fff',
  },
  paidSection: {
    marginTop: 24,
  },
  paidSectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 12,
  },
  paidCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    marginBottom: 8,
  },
  paidIcon: {
    marginRight: 12,
  },
  paidInfo: {
    flex: 1,
  },
  paidTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#374151',
  },
  paidDate: {
    fontSize: 12,
    color: '#9ca3af',
    marginTop: 2,
  },
  paidAmount: {
    fontSize: 15,
    fontWeight: '600',
    color: '#16a34a',
  },
  // History Tab
  historyDateHeader: {
    paddingVertical: 8,
    marginBottom: 8,
  },
  historyDateText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
    textTransform: 'capitalize',
  },
  historyCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    marginBottom: 8,
  },
  historyIcon: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: '#eff6ff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  historyInfo: {
    flex: 1,
    marginLeft: 12,
  },
  historyTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#374151',
  },
  historyMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 4,
  },
  historyDate: {
    fontSize: 12,
    color: '#9ca3af',
  },
  historyMethod: {
    fontSize: 12,
    color: '#9ca3af',
    backgroundColor: '#f3f4f6',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  historyAmount: {
    fontSize: 15,
    fontWeight: '600',
    color: '#374151',
  },
});
