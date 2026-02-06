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
import { pl, enUS } from 'date-fns/locale';
import api from '../../api/client';
import { useSettingsStore, useThemeColors } from '../../store/settingsStore';

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

export default function PaymentsScreen() {
  const { t, language, isDark } = useSettingsStore();
  const colors = useThemeColors();
  const dateLocale = language === 'pl' ? pl : enUS;

  const [payments, setPayments] = useState<Payment[]>([]);
  const [history, setHistory] = useState<PaymentHistory[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState<'pending' | 'history'>('pending');

  const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string; icon: string }> = {
    pending: { label: t.absences.pending, color: colors.warning, bg: colors.warningLight, icon: 'time' },
    partial: { label: language === 'pl' ? 'Czesciowa' : 'Partial', color: colors.primary, bg: colors.primaryLight, icon: 'pie-chart' },
    paid: { label: t.payments.paid, color: colors.success, bg: colors.successLight, icon: 'checkmark-circle' },
    overdue: { label: t.payments.overdue, color: colors.error, bg: colors.errorLight, icon: 'alert-circle' },
  };

  const safeFormatDate = (dateStr: string | undefined, formatStr: string): string => {
    if (!dateStr) return '-';
    try {
      const date = new Date(dateStr);
      if (isNaN(date.getTime())) return '-';
      return format(date, formatStr, { locale: dateLocale });
    } catch {
      return '-';
    }
  };

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
        language === 'pl' ? 'Platnosc online' : 'Online payment',
        language === 'pl'
          ? `Czy chcesz przejsc do platnosci ${payment.remaining_amount.toFixed(2)} zl?`
          : `Do you want to proceed to payment of ${payment.remaining_amount.toFixed(2)} PLN?`,
        [
          { text: t.common.cancel, style: 'cancel' },
          {
            text: t.payments.payNow,
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

  const styles = createStyles(colors, isDark);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Summary Card */}
      <View style={styles.summaryCard}>
        <View style={styles.summaryMain}>
          <Text style={styles.summaryLabel}>{t.payments.pending}</Text>
          <Text style={styles.summaryAmount}>{totalPending.toFixed(2)} zl</Text>
        </View>
        <View style={styles.summaryStats}>
          <View style={styles.summaryStat}>
            <Text style={styles.summaryStatValue}>{pendingPayments.length}</Text>
            <Text style={styles.summaryStatLabel}>{t.absences.pending.toLowerCase()}</Text>
          </View>
          {overdueCount > 0 && (
            <View style={[styles.summaryStat, styles.summaryStatDanger]}>
              <Text style={[styles.summaryStatValue, { color: colors.error }]}>{overdueCount}</Text>
              <Text style={[styles.summaryStatLabel, { color: colors.error }]}>{t.payments.overdue.toLowerCase()}</Text>
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
            {language === 'pl' ? 'Biezace' : 'Current'} ({pendingPayments.length})
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'history' && styles.tabActive]}
          onPress={() => setActiveTab('history')}
        >
          <Text style={[styles.tabText, activeTab === 'history' && styles.tabTextActive]}>
            {t.payments.history}
          </Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.content}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} />
        }
      >
        {activeTab === 'pending' ? (
          <>
            {pendingPayments.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="checkmark-circle" size={64} color={colors.success} />
                <Text style={styles.emptyTitle}>
                  {language === 'pl' ? 'Wszystko oplacone!' : 'All paid!'}
                </Text>
                <Text style={styles.emptySubtitle}>
                  {language === 'pl' ? 'Nie masz zadnych oczekujacych platnosci' : 'No pending payments'}
                </Text>
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
                        <Text style={styles.amountLabel}>
                          {language === 'pl' ? 'Kwota calkowita' : 'Total amount'}
                        </Text>
                        <Text style={styles.amountValue}>{payment.total_amount.toFixed(2)} zl</Text>
                      </View>
                      {payment.paid_amount > 0 && (
                        <View style={styles.amountRow}>
                          <Text style={styles.amountLabel}>
                            {language === 'pl' ? 'Wplacono' : 'Paid'}
                          </Text>
                          <Text style={[styles.amountValue, { color: colors.success }]}>
                            -{payment.paid_amount.toFixed(2)} zl
                          </Text>
                        </View>
                      )}
                      <View style={[styles.amountRow, styles.amountRowTotal]}>
                        <Text style={styles.amountLabelBold}>{t.payments.pending}</Text>
                        <Text style={styles.amountValueBold}>
                          {payment.remaining_amount.toFixed(2)} zl
                        </Text>
                      </View>
                    </View>

                    {payment.paid_amount > 0 && (
                      <View style={styles.progressContainer}>
                        <View style={styles.progressBar}>
                          <View style={[styles.progressFill, { width: `${progress}%` }]} />
                        </View>
                        <Text style={styles.progressText}>
                          {Math.round(progress)}% {language === 'pl' ? 'oplacone' : 'paid'}
                        </Text>
                      </View>
                    )}

                    <View style={styles.paymentFooter}>
                      <View style={styles.dueDate}>
                        <Ionicons name="calendar-outline" size={14} color={colors.textSecondary} />
                        <Text style={styles.dueDateText}>
                          {t.payments.dueDate}: {safeFormatDate(payment.due_date, 'd MMM yyyy')}
                        </Text>
                      </View>

                      {payment.payment_url && (
                        <TouchableOpacity
                          style={styles.payButton}
                          onPress={() => handlePayOnline(payment)}
                        >
                          <Ionicons name="card" size={16} color="#fff" />
                          <Text style={styles.payButtonText}>
                            {language === 'pl' ? 'Zaplac online' : 'Pay online'}
                          </Text>
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
                <Text style={styles.paidSectionTitle}>
                  {language === 'pl' ? 'Ostatnio oplacone' : 'Recently paid'}
                </Text>
                {paidPayments.slice(0, 5).map((payment) => (
                  <View key={payment.id} style={styles.paidCard}>
                    <View style={styles.paidIcon}>
                      <Ionicons name="checkmark-circle" size={24} color={colors.success} />
                    </View>
                    <View style={styles.paidInfo}>
                      <Text style={styles.paidTitle}>{payment.title}</Text>
                      <Text style={styles.paidDate}>
                        {safeFormatDate(payment.created_at, 'd MMM yyyy')}
                      </Text>
                    </View>
                    <Text style={styles.paidAmount}>{payment.total_amount.toFixed(2)} zl</Text>
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
                <Ionicons name="receipt-outline" size={64} color={colors.textTertiary} />
                <Text style={styles.emptyTitle}>
                  {language === 'pl' ? 'Brak historii' : 'No history'}
                </Text>
                <Text style={styles.emptySubtitle}>
                  {language === 'pl' ? 'Historia platnosci pojawi sie tutaj' : 'Payment history will appear here'}
                </Text>
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
                        <Ionicons name="receipt" size={20} color={colors.primary} />
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
                      <Text style={styles.historyAmount}>{item.amount?.toFixed(2) ?? '0.00'} zl</Text>
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

const createStyles = (colors: ReturnType<typeof useThemeColors>, isDark: boolean) =>
  StyleSheet.create({
    container: {
      flex: 1,
      backgroundColor: colors.background,
    },
    loadingContainer: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: colors.background,
    },
    summaryCard: {
      backgroundColor: colors.primary,
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
      backgroundColor: colors.surface,
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
      backgroundColor: colors.primary,
    },
    tabText: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.textSecondary,
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
      backgroundColor: colors.surface,
      borderRadius: 20,
    },
    emptyTitle: {
      fontSize: 18,
      fontWeight: '600',
      color: colors.text,
      marginTop: 16,
    },
    emptySubtitle: {
      fontSize: 14,
      color: colors.textSecondary,
      marginTop: 4,
      textAlign: 'center',
    },
    paymentCard: {
      backgroundColor: colors.surface,
      borderRadius: 20,
      padding: 20,
      marginBottom: 16,
      shadowColor: colors.shadow,
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: isDark ? 0.3 : 0.05,
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
      color: colors.text,
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
      color: colors.textSecondary,
      marginTop: 4,
    },
    paymentAmounts: {
      backgroundColor: colors.surfaceSecondary,
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
      borderTopColor: colors.border,
      marginTop: 6,
      paddingTop: 12,
    },
    amountLabel: {
      fontSize: 14,
      color: colors.textSecondary,
    },
    amountValue: {
      fontSize: 14,
      color: colors.text,
    },
    amountLabelBold: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
    amountValueBold: {
      fontSize: 18,
      fontWeight: '700',
      color: colors.text,
    },
    progressContainer: {
      marginBottom: 16,
    },
    progressBar: {
      height: 6,
      backgroundColor: colors.border,
      borderRadius: 3,
      overflow: 'hidden',
    },
    progressFill: {
      height: '100%',
      backgroundColor: colors.success,
      borderRadius: 3,
    },
    progressText: {
      fontSize: 12,
      color: colors.textSecondary,
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
      color: colors.textSecondary,
    },
    payButton: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.primary,
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
      color: colors.text,
      marginBottom: 12,
    },
    paidCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
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
      color: colors.text,
    },
    paidDate: {
      fontSize: 12,
      color: colors.textTertiary,
      marginTop: 2,
    },
    paidAmount: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.success,
    },
    // History Tab
    historyDateHeader: {
      paddingVertical: 8,
      marginBottom: 8,
    },
    historyDateText: {
      fontSize: 14,
      fontWeight: '600',
      color: colors.textSecondary,
      textTransform: 'capitalize',
    },
    historyCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: colors.surface,
      borderRadius: 12,
      padding: 14,
      marginBottom: 8,
    },
    historyIcon: {
      width: 40,
      height: 40,
      borderRadius: 10,
      backgroundColor: colors.primaryLight,
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
      color: colors.text,
    },
    historyMeta: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: 8,
      marginTop: 4,
    },
    historyDate: {
      fontSize: 12,
      color: colors.textTertiary,
    },
    historyMethod: {
      fontSize: 12,
      color: colors.textTertiary,
      backgroundColor: colors.surfaceSecondary,
      paddingHorizontal: 6,
      paddingVertical: 2,
      borderRadius: 4,
    },
    historyAmount: {
      fontSize: 15,
      fontWeight: '600',
      color: colors.text,
    },
  });
