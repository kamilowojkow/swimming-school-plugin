<?php
/**
 * Panel Rodzica - Płatności
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

foreach ($payments as $payment) {
    $total_to_pay += $payment->total_amount;
    $total_paid += $payment->paid_amount;
    
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
    <h1>💰 Płatności</h1>
    <p>Historia płatności i informacje o saldzie</p>
</div>

<!-- Podsumowanie -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
    
    <!-- NOWE: Portfel -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4); color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">💎 Portfel cashback</div>
        <div style="font-size: 28px; font-weight: 700;"><?php echo number_format($wallet->balance, 2, ',', ' '); ?> zł</div>
        <?php if ($wallet->balance > 0): ?>
        <a href="?panel_page=referrals" style="font-size: 12px; color: white; text-decoration: underline; opacity: 0.9;">Zobacz szczegóły</a>
        <?php endif; ?>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #2271b1;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Wszystkie płatności</div>
        <div style="font-size: 32px; font-weight: 700; color: #2271b1;"><?php echo count($payments); ?></div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Zapłacono</div>
        <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?php echo number_format($total_paid, 2, ',', ' '); ?> PLN</div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Do zapłaty</div>
        <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?php echo number_format($total_remaining, 2, ',', ' '); ?> PLN</div>
    </div>
    
    <?php if ($total_overdue > 0): ?>
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #dc2626;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Zaległe</div>
        <div style="font-size: 28px; font-weight: 700; color: #dc2626;"><?php echo number_format($total_overdue, 2, ',', ' '); ?> PLN</div>
    </div>
    <?php endif; ?>
</div>

<!-- Dane do przelewu -->
<?php if ($bank_info): ?>
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; margin-bottom: 30px; color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
    <h3 style="margin: 0 0 15px 0; color: white;">📋 Dane do przelewu</h3>
    <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; font-family: monospace; white-space: pre-line; line-height: 1.8;">
        <?php echo wp_kses_post($bank_info); ?>
    </div>
    <p style="margin: 15px 0 0 0; font-size: 14px; opacity: 0.9;">
        💡 W tytule przelewu wpisz numer płatności lub swoje imię i nazwisko
    </p>
</div>
<?php endif; ?>

<!-- Lista płatności -->
<?php if (empty($payments)): ?>
    <div style="background: white; padding: 40px; text-align: center; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <p style="margin: 0; color: #666; font-size: 16px;">
            💰 Brak płatności
        </p>
    </div>
<?php else: ?>
    
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <?php foreach ($payments as $payment): 
            $is_overdue = ($payment->status == 'pending' && $payment->due_date && $payment->due_date < date('Y-m-d'));
            $remaining = $payment->total_amount - $payment->paid_amount;
            
            $status_colors = array(
                'pending' => array('bg' => '#fef3c7', 'text' => '#d97706', 'label' => 'Oczekująca'),
                'partial' => array('bg' => '#dbeafe', 'text' => '#2563eb', 'label' => 'Częściowo opłacona'),
                'paid' => array('bg' => '#d1fae5', 'text' => '#059669', 'label' => '✓ Opłacona'),
                'cancelled' => array('bg' => '#fee2e2', 'text' => '#dc2626', 'label' => 'Anulowana')
            );
            $status = $status_colors[$payment->status] ?? $status_colors['pending'];
        ?>
        
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); <?php echo $is_overdue ? 'border-left: 4px solid #dc2626;' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; color: #1e293b; font-size: 18px;">
                        <?php echo esc_html($payment->title); ?>
                        <?php if ($is_overdue): ?>
                            <span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; margin-left: 10px; font-weight: 600;">ZALEGŁE</span>
                        <?php endif; ?>
                    </h3>
                    <?php if ($payment->description): ?>
                        <p style="margin: 0 0 10px 0; color: #64748b; font-size: 14px;">
                            <?php echo esc_html($payment->description); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; color: #64748b; font-size: 14px;">
                        <?php if ($payment->due_date): ?>
                            <span>📅 Termin: <strong><?php echo date('d.m.Y', strtotime($payment->due_date)); ?></strong></span>
                        <?php endif; ?>
                        
                        <?php if ($payment->installments_count > 1): ?>
                            <span>📊 Raty: <strong><?php echo $payment->paid_installments_count; ?>/<?php echo $payment->installments_count; ?></strong></span>
                        <?php endif; ?>
                        
                        <?php if ($payment->invoice_number): ?>
                            <span>🧾 Faktura: <strong><?php echo esc_html($payment->invoice_number); ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <span style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['text']; ?>; padding: 8px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; display: inline-block; margin-bottom: 10px;">
                        <?php echo $status['label']; ?>
                    </span>
                </div>
            </div>
            
            <!-- Kwoty -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; padding: 15px; background: #f8fafc; border-radius: 8px; margin-bottom: 15px;">
                <div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 3px;">Do zapłaty</div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;">
                        <?php echo number_format($payment->total_amount, 2, ',', ' '); ?> PLN
                    </div>
                </div>
                
                <div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 3px;">Zapłacono</div>
                    <div style="font-size: 24px; font-weight: 700; color: #10b981;">
                        <?php echo number_format($payment->paid_amount, 2, ',', ' '); ?> PLN
                    </div>
                </div>
                
                <?php if ($payment->status != 'paid' && $payment->status != 'cancelled'): ?>
                <div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 3px;">Pozostało</div>
                    <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">
                        <?php echo number_format($remaining, 2, ',', ' '); ?> PLN
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Raty -->
            <?php if ($payment->installments_count > 1): 
                $installments = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ssm_payment_installments 
                     WHERE payment_id = %d 
                     ORDER BY installment_number",
                    $payment->id
                ));
            ?>
            <div>
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #64748b;">Plan spłaty:</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($installments as $installment): 
                        $inst_is_overdue = ($installment->status == 'pending' && $installment->due_date < date('Y-m-d'));
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background: <?php echo $installment->status == 'paid' ? '#d1fae5' : ($inst_is_overdue ? '#fee2e2' : '#f8fafc'); ?>; border-radius: 8px;">
                        <div style="flex: 1;">
                            <strong>Rata <?php echo $installment->installment_number; ?>/<?php echo $payment->installments_count; ?></strong>
                            <span style="color: #64748b; margin-left: 15px;">
                                <?php echo date('d.m.Y', strtotime($installment->due_date)); ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <strong><?php echo number_format($installment->amount, 2, ',', ' '); ?> PLN</strong>
                            
                            <?php if ($installment->status == 'paid'): ?>
                                <span style="background: #059669; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    ✓ Opłacona
                                </span>
                            <?php elseif ($inst_is_overdue): ?>
                                <span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    ZALEGŁE
                                </span>
                            <?php else: ?>
                                <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                    Oczekuje
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Faktura -->
            <?php if ($payment->invoice_url): ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <a href="<?php echo esc_url($payment->invoice_url); ?>" 
                   target="_blank"
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    🧾 Pobierz fakturę
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endforeach; ?>
    </div>
    
<?php endif; ?>
