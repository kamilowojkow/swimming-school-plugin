<?php
/**
 * Panel Admin - Szczegóły płatności
 */
if (!defined('ABSPATH')) exit;

$is_overdue = ($payment->status == 'pending' && $payment->due_date && $payment->due_date < date('Y-m-d'));
$remaining = $payment->total_amount - $payment->paid_amount;

$status_colors = array(
    'pending' => array('bg' => '#fef3c7', 'text' => '#d97706', 'label' => 'Oczekująca'),
    'partial' => array('bg' => '#dbeafe', 'text' => '#2563eb', 'label' => 'Częściowo opłacona'),
    'paid' => array('bg' => '#d1fae5', 'text' => '#059669', 'label' => 'Opłacona'),
    'cancelled' => array('bg' => '#fee2e2', 'text' => '#dc2626', 'label' => 'Anulowana')
);
$status = $status_colors[$payment->status] ?? $status_colors['pending'];
?>

<div class="wrap">
    <h1>
        💰 Szczegóły płatności #<?php echo $payment->id; ?>
        <a href="?page=ssm-payments" class="page-title-action">← Powrót do listy</a>
    </h1>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        
        <!-- Lewa kolumna - Szczegóły -->
        <div>
            
            <!-- Informacje o płatności -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                    <div>
                        <h2 style="margin: 0 0 10px 0;"><?php echo esc_html($payment->title); ?></h2>
                        <?php if ($payment->description): ?>
                            <p style="margin: 0; color: #666;"><?php echo esc_html($payment->description); ?></p>
                        <?php endif; ?>
                    </div>
                    <span style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['text']; ?>; padding: 8px 16px; border-radius: 12px; font-weight: 600;">
                        <?php echo $status['label']; ?>
                    </span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; padding: 20px; background: #f9fafb; border-radius: 8px;">
                    <div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Całkowita kwota</div>
                        <div style="font-size: 28px; font-weight: 700; color: #1e293b;">
                            <?php echo number_format($payment->total_amount, 2, ',', ' '); ?> PLN
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Zapłacono</div>
                        <div style="font-size: 28px; font-weight: 700; color: #10b981;">
                            <?php echo number_format($payment->paid_amount, 2, ',', ' '); ?> PLN
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Pozostało</div>
                        <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">
                            <?php echo number_format($remaining, 2, ',', ' '); ?> PLN
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Raty -->
            <?php if (!empty($installments)): ?>
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin: 0 0 20px 0;">📊 Raty (<?php echo count($installments); ?>)</h3>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Rata</th>
                            <th>Termin</th>
                            <th>Kwota</th>
                            <th>Zapłacono</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $inst): 
                            $inst_overdue = ($inst->status == 'pending' && $inst->due_date < date('Y-m-d'));
                        ?>
                        <tr style="<?php echo $inst_overdue ? 'background: #fee2e2;' : ''; ?>">
                            <td><strong><?php echo $inst->installment_number; ?>/<?php echo count($installments); ?></strong></td>
                            <td>
                                <?php echo date('d.m.Y', strtotime($inst->due_date)); ?>
                                <?php if ($inst_overdue): ?>
                                    <br><small style="color: #dc2626; font-weight: 600;">ZALEGŁE</small>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo number_format($inst->amount, 2, ',', ' '); ?> PLN</strong></td>
                            <td>
                                <input type="number" 
                                       id="paid-amount-<?php echo $inst->id; ?>" 
                                       value="<?php echo $inst->paid_amount; ?>" 
                                       step="0.01" 
                                       min="0" 
                                       max="<?php echo $inst->amount; ?>"
                                       style="width: 100px;"
                                       <?php echo $inst->status == 'paid' ? 'disabled' : ''; ?>>
                            </td>
                            <td>
                                <?php if ($inst->status == 'paid'): ?>
                                    <span style="background: #d1fae5; color: #059669; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                        ✓ Opłacona
                                    </span>
                                    <?php if ($inst->paid_at): ?>
                                        <br><small style="color: #666;"><?php echo date('d.m.Y', strtotime($inst->paid_at)); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #d97706; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                        Oczekuje
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inst->status != 'paid'): ?>
                                    <button type="button" class="button button-small button-primary mark-paid-btn" 
                                            data-installment-id="<?php echo $inst->id; ?>"
                                            data-amount="<?php echo $inst->amount; ?>">
                                        ✓ Oznacz jako opłacona
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="button button-small unmark-paid-btn" 
                                            data-installment-id="<?php echo $inst->id; ?>">
                                        ↩ Cofnij
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Dane płatności bez rat -->
            <?php if (empty($installments)): ?>
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin: 0 0 20px 0;">💵 Płatność jednorazowa</h3>
                
                <table class="form-table">
                    <tr>
                        <th>Zapłacono:</th>
                        <td>
                            <input type="number" 
                                   id="single-paid-amount" 
                                   value="<?php echo $payment->paid_amount; ?>" 
                                   step="0.01" 
                                   min="0" 
                                   max="<?php echo $payment->total_amount; ?>"
                                   style="width: 150px;">
                            PLN
                        </td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <select id="single-payment-status" style="width: 200px;">
                                <option value="pending" <?php selected($payment->status, 'pending'); ?>>Oczekująca</option>
                                <option value="paid" <?php selected($payment->status, 'paid'); ?>>Opłacona</option>
                                <option value="cancelled" <?php selected($payment->status, 'cancelled'); ?>>Anulowana</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" class="button button-primary" id="update-single-payment-btn">
                                Zapisz zmiany
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Prawa kolumna - Klient i akcje -->
        <div>
            
            <!-- Dane klienta -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0;">👤 Klient</h3>
                
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 5px;">
                        <?php echo esc_html($payment->client_name); ?>
                    </div>
                    <div style="color: #666; font-size: 14px;">
                        📧 <?php echo esc_html($payment->client_email); ?><br>
                        <?php if ($payment->client_phone): ?>
                            📱 <?php echo esc_html($payment->client_phone); ?><br>
                        <?php endif; ?>
                    </div>
                </div>
                
                <a href="?page=swimming-school-clients&action=edit&client_id=<?php echo $payment->client_id; ?>" 
                   class="button button-small" style="width: 100%;">
                    Zobacz profil klienta
                </a>
            </div>
            
            <!-- Informacje dodatkowe -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0;">ℹ️ Informacje</h3>
                
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Utworzono:</td>
                        <td style="padding: 8px 0; font-weight: 600;">
                            <?php echo $payment->created_at ? date('d.m.Y H:i', strtotime($payment->created_at)) : '-'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Termin:</td>
                        <td style="padding: 8px 0; font-weight: 600;">
                            <?php echo $payment->due_date ? date('d.m.Y', strtotime($payment->due_date)) : '-'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Faktura:</td>
                        <td style="padding: 8px 0; font-weight: 600;">
                            <?php echo $payment->invoice_number ? esc_html($payment->invoice_number) : '-'; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Akcje -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin: 0 0 15px 0;">⚡ Akcje</h3>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="button" class="button" style="width: 100%;">
                        ✏️ Edytuj płatność
                    </button>
                    
                    <button type="button" class="button" style="width: 100%;">
                        🧾 Wystaw fakturę
                    </button>
                    
                    <button type="button" class="button" style="width: 100%;">
                        📧 Wyślij email
                    </button>
                    
                    <button type="button" class="button button-link-delete" 
                            onclick="if(confirm('Czy na pewno usunąć płatność?')) { deletePayment(<?php echo $payment->id; ?>); }"
                            style="width: 100%; color: #dc2626;">
                        🗑️ Usuń płatność
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Oznacz ratę jako opłaconą
    $('.mark-paid-btn').on('click', function() {
        var installmentId = $(this).data('installment-id');
        var fullAmount = $(this).data('amount');
        var inputField = $('#paid-amount-' + installmentId);
        
        // Automatycznie ustaw pełną kwotę raty
        inputField.val(fullAmount);
        
        if (!confirm('Oznaczyć ratę jako opłaconą?\nKwota: ' + fullAmount + ' PLN')) {
            return;
        }
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_update_installment_status',
            nonce: ssmAdmin.nonce,
            installment_id: installmentId,
            paid_amount: fullAmount,
            status: 'paid'
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Cofnij opłaconą ratę
    $('.unmark-paid-btn').on('click', function() {
        var installmentId = $(this).data('installment-id');
        
        if (!confirm('Cofnąć oznaczenie raty jako opłaconej?\nRata zostanie przywrócona do statusu "Oczekuje".')) {
            return;
        }
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_update_installment_status',
            nonce: ssmAdmin.nonce,
            installment_id: installmentId,
            paid_amount: 0,
            status: 'pending'
        }, function(response) {
            if (response.success) {
                alert('✅ Cofnięto oznaczenie raty');
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Aktualizuj płatność jednorazową
    $('#update-single-payment-btn').on('click', function() {
        var paidAmount = $('#single-paid-amount').val();
        var status = $('#single-payment-status').val();
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_update_payment_status',
            nonce: ssmAdmin.nonce,
            payment_id: <?php echo $payment->id; ?>,
            paid_amount: paidAmount,
            status: status
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
});

function deletePayment(paymentId) {
    jQuery.post(ssmAdmin.ajax_url, {
        action: 'ssm_delete_payment',
        nonce: ssmAdmin.nonce,
        id: paymentId
    }, function(response) {
        if (response.success) {
            alert('✅ Płatność usunięta');
            window.location = '?page=ssm-payments';
        } else {
            alert('❌ ' + response.data);
        }
    });
}
</script>
