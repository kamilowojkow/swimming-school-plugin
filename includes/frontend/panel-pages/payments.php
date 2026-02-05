<?php
/**
 * Panel Rodzica - Płatności (Fila Style)
 */
if (!defined('ABSPATH')) exit;

// Pobierz płatności klienta
$payments = $wpdb->get_results($wpdb->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payment_installments
            WHERE payment_id = p.id) as installments_count,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payment_installments
            WHERE payment_id = p.id AND status = 'paid') as paid_installments_count
    FROM {$wpdb->prefix}ssm_payments p
    WHERE p.client_id = %d
    ORDER BY p.created_at DESC, p.due_date ASC
", $client->id));

// Oblicz sumy
$total_to_pay = 0;
$total_paid = 0;
$total_overdue = 0;
$pending_count = 0;

foreach ($payments as $payment) {
    $total_to_pay += $payment->total_amount;
    $total_paid += $payment->paid_amount;

    if ($payment->status == 'pending' || $payment->status == 'partial') {
        $pending_count++;
    }

    if ($payment->status == 'pending' && $payment->due_date && $payment->due_date < date('Y-m-d')) {
        $total_overdue += ($payment->total_amount - $payment->paid_amount);
    }
}

$total_remaining = $total_to_pay - $total_paid;

// Pobierz saldo portfela
$wallet = ssm_get_or_create_wallet($client->id);

// Pobierz dane do przelewu
$bank_info = get_option('ssm_bank_transfer_info', '');
?>

<div class="ssm-page-header">
    <h1><i class="ri-wallet-3-line"></i> <?php echo ssm_t('payments'); ?></h1>
    <p><?php echo ssm_t('payments_description'); ?></p>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid">
    <!-- Portfel cashback -->
    <div class="ssm-stat-card ssm-stat-wallet">
        <div class="ssm-stat-icon purple">
            <i class="ri-gift-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($wallet->balance, 2, ',', ' '); ?> zł</h3>
            <p><?php echo ssm_t('wallet_balance'); ?></p>
        </div>
        <?php if ($wallet->balance > 0): ?>
        <a href="?panel_page=referrals" class="ssm-stat-link">
            <i class="ri-arrow-right-line"></i>
        </a>
        <?php endif; ?>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon green">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($total_paid, 0, ',', ' '); ?> zł</h3>
            <p><?php echo ssm_t('total_paid'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon orange">
            <i class="ri-time-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($total_remaining, 0, ',', ' '); ?> zł</h3>
            <p><?php echo ssm_t('remaining_to_pay'); ?></p>
        </div>
    </div>

    <?php if ($total_overdue > 0): ?>
    <div class="ssm-stat-card ssm-stat-danger">
        <div class="ssm-stat-icon red">
            <i class="ri-error-warning-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($total_overdue, 0, ',', ' '); ?> zł</h3>
            <p><?php echo ssm_t('overdue'); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($total_overdue > 0): ?>
<div class="ssm-alert ssm-alert-danger">
    <i class="ri-error-warning-line"></i>
    <span><?php echo ssm_t('overdue_alert'); ?></span>
</div>
<?php endif; ?>

<!-- Dane do przelewu -->
<?php if ($bank_info): ?>
<div class="ssm-section ssm-section-bank">
    <div class="ssm-bank-header">
        <div class="ssm-bank-icon">
            <i class="ri-bank-line"></i>
        </div>
        <div class="ssm-bank-title">
            <h3><?php echo ssm_t('bank_transfer_info'); ?></h3>
            <p><?php echo ssm_t('bank_transfer_hint'); ?></p>
        </div>
    </div>
    <div class="ssm-bank-details">
        <?php echo wp_kses_post($bank_info); ?>
    </div>
</div>
<?php endif; ?>

<!-- Lista płatności -->
<?php if (empty($payments)): ?>
<div class="ssm-section">
    <div class="ssm-empty-state">
        <i class="ri-wallet-3-line"></i>
        <p><?php echo ssm_t('no_payments'); ?></p>
    </div>
</div>
<?php else: ?>

<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-file-list-3-line"></i>
            <?php echo ssm_t('payment_history'); ?>
        </h3>
        <span class="ssm-section-count"><?php echo count($payments); ?> <?php echo ssm_t('payments_count'); ?></span>
    </div>

    <div class="ssm-payments-list">
        <?php foreach ($payments as $payment):
            $is_overdue = ($payment->status == 'pending' && $payment->due_date && $payment->due_date < date('Y-m-d'));
            $remaining = $payment->total_amount - $payment->paid_amount;
            $progress = $payment->total_amount > 0 ? round(($payment->paid_amount / $payment->total_amount) * 100) : 0;

            $status_map = array(
                'pending' => array('class' => 'warning', 'icon' => 'ri-time-line', 'label' => ssm_t('status_pending')),
                'partial' => array('class' => 'info', 'icon' => 'ri-pie-chart-line', 'label' => ssm_t('status_partial')),
                'paid' => array('class' => 'success', 'icon' => 'ri-checkbox-circle-line', 'label' => ssm_t('status_paid')),
                'cancelled' => array('class' => 'muted', 'icon' => 'ri-close-circle-line', 'label' => ssm_t('status_cancelled'))
            );
            $status = $status_map[$payment->status] ?? $status_map['pending'];
        ?>

        <div class="ssm-payment-card <?php echo $is_overdue ? 'is-overdue' : ''; ?> <?php echo $payment->status == 'paid' ? 'is-paid' : ''; ?>">
            <div class="ssm-payment-header">
                <div class="ssm-payment-info">
                    <h4>
                        <?php echo esc_html($payment->title); ?>
                        <?php if ($is_overdue): ?>
                            <span class="ssm-badge ssm-badge-danger">
                                <i class="ri-error-warning-line"></i> <?php echo ssm_t('overdue'); ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($payment->description): ?>
                        <p class="ssm-payment-desc"><?php echo esc_html($payment->description); ?></p>
                    <?php endif; ?>
                </div>
                <div class="ssm-payment-status">
                    <span class="ssm-badge ssm-badge-<?php echo $status['class']; ?>">
                        <i class="<?php echo $status['icon']; ?>"></i>
                        <?php echo $status['label']; ?>
                    </span>
                </div>
            </div>

            <div class="ssm-payment-meta">
                <?php if ($payment->due_date): ?>
                <span class="ssm-meta-item">
                    <i class="ri-calendar-line"></i>
                    <?php echo ssm_t('due_date'); ?>: <strong><?php echo date('d.m.Y', strtotime($payment->due_date)); ?></strong>
                </span>
                <?php endif; ?>

                <?php if ($payment->installments_count > 1): ?>
                <span class="ssm-meta-item">
                    <i class="ri-pie-chart-line"></i>
                    <?php echo ssm_t('installments'); ?>: <strong><?php echo $payment->paid_installments_count; ?>/<?php echo $payment->installments_count; ?></strong>
                </span>
                <?php endif; ?>

                <?php if ($payment->invoice_number): ?>
                <span class="ssm-meta-item">
                    <i class="ri-file-text-line"></i>
                    <?php echo ssm_t('invoice'); ?>: <strong><?php echo esc_html($payment->invoice_number); ?></strong>
                </span>
                <?php endif; ?>
            </div>

            <!-- Kwoty -->
            <div class="ssm-payment-amounts">
                <div class="ssm-amount-item">
                    <span class="ssm-amount-label"><?php echo ssm_t('total_amount'); ?></span>
                    <span class="ssm-amount-value"><?php echo number_format($payment->total_amount, 2, ',', ' '); ?> zł</span>
                </div>
                <div class="ssm-amount-item ssm-amount-paid">
                    <span class="ssm-amount-label"><?php echo ssm_t('paid'); ?></span>
                    <span class="ssm-amount-value"><?php echo number_format($payment->paid_amount, 2, ',', ' '); ?> zł</span>
                </div>
                <?php if ($payment->status != 'paid' && $payment->status != 'cancelled'): ?>
                <div class="ssm-amount-item ssm-amount-remaining">
                    <span class="ssm-amount-label"><?php echo ssm_t('remaining'); ?></span>
                    <span class="ssm-amount-value"><?php echo number_format($remaining, 2, ',', ' '); ?> zł</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Progress bar -->
            <?php if ($payment->status != 'cancelled'): ?>
            <div class="ssm-payment-progress">
                <div class="ssm-progress-bar">
                    <div class="ssm-progress-fill <?php echo $payment->status == 'paid' ? 'complete' : ''; ?>" style="width: <?php echo $progress; ?>%;"></div>
                </div>
                <span class="ssm-progress-text"><?php echo $progress; ?>%</span>
            </div>
            <?php endif; ?>

            <!-- Raty -->
            <?php if ($payment->installments_count > 1):
                $installments = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ssm_payment_installments
                     WHERE payment_id = %d
                     ORDER BY installment_number",
                    $payment->id
                ));
            ?>
            <div class="ssm-installments">
                <div class="ssm-installments-header">
                    <i class="ri-list-check"></i>
                    <?php echo ssm_t('payment_plan'); ?>
                </div>
                <div class="ssm-installments-list">
                    <?php foreach ($installments as $installment):
                        $inst_is_overdue = ($installment->status == 'pending' && $installment->due_date < date('Y-m-d'));
                        $inst_class = $installment->status == 'paid' ? 'paid' : ($inst_is_overdue ? 'overdue' : 'pending');
                    ?>
                    <div class="ssm-installment-item ssm-installment-<?php echo $inst_class; ?>">
                        <div class="ssm-installment-number">
                            <?php echo $installment->installment_number; ?>
                        </div>
                        <div class="ssm-installment-info">
                            <span class="ssm-installment-date">
                                <i class="ri-calendar-line"></i>
                                <?php echo date('d.m.Y', strtotime($installment->due_date)); ?>
                            </span>
                        </div>
                        <div class="ssm-installment-amount">
                            <?php echo number_format($installment->amount, 2, ',', ' '); ?> zł
                        </div>
                        <div class="ssm-installment-status">
                            <?php if ($installment->status == 'paid'): ?>
                                <i class="ri-checkbox-circle-fill"></i>
                            <?php elseif ($inst_is_overdue): ?>
                                <i class="ri-error-warning-fill"></i>
                            <?php else: ?>
                                <i class="ri-time-line"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Akcje -->
            <?php if ($payment->invoice_url): ?>
            <div class="ssm-payment-actions">
                <a href="<?php echo esc_url($payment->invoice_url); ?>" target="_blank" class="ssm-btn ssm-btn-outline">
                    <i class="ri-file-download-line"></i>
                    <?php echo ssm_t('download_invoice'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

<style>
/* ========================================
   PAYMENTS PAGE - FILA STYLE
   ======================================== */

/* Stat card wallet special */
.ssm-stat-wallet {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
}

.ssm-stat-wallet .ssm-stat-icon {
    background: rgba(255,255,255,0.2) !important;
    color: white !important;
}

.ssm-stat-wallet .ssm-stat-content h3,
.ssm-stat-wallet .ssm-stat-content p {
    color: white !important;
}

.ssm-stat-wallet .ssm-stat-content p {
    opacity: 0.9;
}

.ssm-stat-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: all 0.2s ease;
}

.ssm-stat-link:hover {
    background: rgba(255,255,255,0.3);
    transform: translateX(2px);
}

.ssm-stat-danger {
    border-left: 4px solid var(--ssm-danger) !important;
}

.ssm-stat-icon.red {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

/* Alert danger */
.ssm-alert-danger {
    background: var(--ssm-danger-light);
    color: #991b1b;
    border-left: 4px solid var(--ssm-danger);
}

/* Bank section */
.ssm-section-bank {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    border: none;
}

.ssm-bank-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.ssm-bank-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.ssm-bank-title h3 {
    margin: 0 0 4px 0;
    font-size: 18px;
    font-weight: 600;
}

.ssm-bank-title p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.ssm-bank-details {
    background: rgba(255,255,255,0.15);
    padding: 20px;
    border-radius: var(--ssm-radius);
    font-family: monospace;
    font-size: 14px;
    line-height: 1.8;
    white-space: pre-line;
}

/* Section count */
.ssm-section-count {
    font-size: 13px;
    color: var(--ssm-text-muted);
    background: var(--ssm-bg);
    padding: 4px 12px;
    border-radius: 20px;
}

/* Payments list */
.ssm-payments-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ssm-payment-card {
    background: var(--ssm-bg-white);
    border: 1px solid var(--ssm-border);
    border-radius: var(--ssm-radius-lg);
    padding: 20px;
    transition: all 0.2s ease;
}

.ssm-payment-card:hover {
    box-shadow: var(--ssm-shadow-md);
}

.ssm-payment-card.is-overdue {
    border-left: 4px solid var(--ssm-danger);
}

.ssm-payment-card.is-paid {
    opacity: 0.8;
}

/* Payment header */
.ssm-payment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.ssm-payment-info h4 {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text);
    display: flex;
    align-items: center;
    gap: 10px;
}

.ssm-payment-desc {
    margin: 0;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

/* Badge styles */
.ssm-badge-warning {
    background: var(--ssm-warning-light);
    color: #92400e;
}

.ssm-badge-info {
    background: var(--ssm-info-light);
    color: #0e7490;
}

.ssm-badge-success {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-badge-muted {
    background: var(--ssm-border-light);
    color: var(--ssm-text-muted);
}

.ssm-badge-danger {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

/* Payment meta */
.ssm-payment-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-meta-item i {
    color: var(--ssm-text-light);
}

/* Payment amounts */
.ssm-payment-amounts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    padding: 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
    margin-bottom: 16px;
}

.ssm-amount-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ssm-amount-label {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-amount-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--ssm-text);
}

.ssm-amount-paid .ssm-amount-value {
    color: var(--ssm-success);
}

.ssm-amount-remaining .ssm-amount-value {
    color: var(--ssm-warning);
}

/* Progress bar */
.ssm-payment-progress {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.ssm-progress-bar {
    flex: 1;
    height: 8px;
    background: var(--ssm-border-light);
    border-radius: 4px;
    overflow: hidden;
}

.ssm-progress-fill {
    height: 100%;
    background: var(--ssm-warning);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.ssm-progress-fill.complete {
    background: var(--ssm-success);
}

.ssm-progress-text {
    font-size: 13px;
    font-weight: 600;
    color: var(--ssm-text-muted);
    min-width: 40px;
}

/* Installments */
.ssm-installments {
    border-top: 1px solid var(--ssm-border);
    padding-top: 16px;
    margin-top: 8px;
}

.ssm-installments-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
    margin-bottom: 12px;
}

.ssm-installments-header i {
    color: var(--ssm-primary);
}

.ssm-installments-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ssm-installment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
}

.ssm-installment-item.ssm-installment-paid {
    background: var(--ssm-success-light);
}

.ssm-installment-item.ssm-installment-overdue {
    background: var(--ssm-danger-light);
}

.ssm-installment-number {
    width: 28px;
    height: 28px;
    background: var(--ssm-bg-white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    color: var(--ssm-text);
    flex-shrink: 0;
}

.ssm-installment-info {
    flex: 1;
}

.ssm-installment-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-installment-amount {
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-installment-status {
    font-size: 18px;
}

.ssm-installment-paid .ssm-installment-status {
    color: var(--ssm-success);
}

.ssm-installment-overdue .ssm-installment-status {
    color: var(--ssm-danger);
}

.ssm-installment-pending .ssm-installment-status {
    color: var(--ssm-warning);
}

/* Payment actions */
.ssm-payment-actions {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--ssm-border);
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-payment-header {
        flex-direction: column;
    }

    .ssm-payment-amounts {
        grid-template-columns: 1fr;
    }

    .ssm-payment-meta {
        flex-direction: column;
        gap: 8px;
    }

    .ssm-installment-item {
        flex-wrap: wrap;
    }
}
</style>
