<?php
/**
 * Panel Admin - Płatności
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień do tej strony.');
}

global $wpdb;

// Obsługa widoku szczegółów
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['payment_id'])) {
    $payment_id = intval($_GET['payment_id']);
    
    // Pobierz płatność
    $payment = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, 
               CONCAT(c.first_name, ' ', c.last_name) as client_name,
               c.email as client_email,
               c.phone as client_phone,
               c.date_of_birth as client_dob
        FROM {$wpdb->prefix}ssm_payments p
        JOIN {$wpdb->prefix}ssm_clients c ON p.client_id = c.id
        WHERE p.id = %d
    ", $payment_id));
    
    if (!$payment) {
        echo '<div class="wrap"><h1>Błąd</h1><p>Płatność nie istnieje.</p></div>';
        return;
    }
    
    // Pobierz raty
    $installments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_payment_installments 
         WHERE payment_id = %d 
         ORDER BY installment_number",
        $payment_id
    ));
    
    include SSM_PLUGIN_DIR . 'includes/admin/payment-details.php';
    return;
}

// Pobierz płatności
$payments = $wpdb->get_results("
    SELECT p.*, 
           CONCAT(c.first_name, ' ', c.last_name) as client_name,
           c.email as client_email,
           c.phone as client_phone,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payment_installments 
            WHERE payment_id = p.id) as installments_count,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payment_installments 
            WHERE payment_id = p.id AND status = 'paid') as paid_installments
    FROM {$wpdb->prefix}ssm_payments p
    JOIN {$wpdb->prefix}ssm_clients c ON p.client_id = c.id
    ORDER BY p.created_at DESC, p.due_date ASC
");

?>

<div class="wrap">
    <h1>💰 Płatności
        <button type="button" class="page-title-action" id="ssm-add-payment-btn">Dodaj płatność</button>
    </h1>
    
    <!-- Statystyki -->
    <?php
    $total_amount = array_sum(array_column($payments, 'total_amount'));
    $paid_amount = array_sum(array_column($payments, 'paid_amount'));
    $pending_amount = $total_amount - $paid_amount;
    $pending_count = count(array_filter($payments, function($p) { return $p->status == 'pending'; }));
    $overdue_count = count(array_filter($payments, function($p) { 
        return $p->status == 'pending' && $p->due_date && $p->due_date < date('Y-m-d'); 
    }));
    ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #2271b1;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Wszystkie płatności</div>
            <div style="font-size: 32px; font-weight: 700; color: #2271b1;"><?php echo count($payments); ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Oczekujące</div>
            <div style="font-size: 32px; font-weight: 700; color: #f59e0b;"><?php echo $pending_count; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #dc3232;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Zaległe</div>
            <div style="font-size: 32px; font-weight: 700; color: #dc3232;"><?php echo $overdue_count; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Do zapłaty</div>
            <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?php echo number_format($pending_amount, 2, ',', ' '); ?> PLN</div>
        </div>
    </div>
    
    <!-- Formularz dodawania/edycji -->
    <div id="ssm-payment-form-container" style="display:none; background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 id="ssm-payment-form-title">Dodaj płatność</h2>
        
        <form id="ssm-payment-form">
            <input type="hidden" id="payment-id" name="id" value="">
            
            <table class="form-table">
                <tr>
                    <th><label for="payment-client">Klient *</label></th>
                    <td>
                        <select id="payment-client" name="client_id" class="regular-text" required>
                            <option value="">-- Wybierz klienta --</option>
                            <?php
                            $clients = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name, ' (', email, ')') as name FROM {$wpdb->prefix}ssm_clients ORDER BY last_name, first_name");
                            foreach ($clients as $client) {
                                echo '<option value="' . $client->id . '">' . esc_html($client->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="payment-title">Tytuł *</label></th>
                    <td>
                        <input type="text" id="payment-title" name="title" class="regular-text" required
                               placeholder="np. Zajęcia pływania - Styczeń 2026">
                    </td>
                </tr>
                <tr>
                    <th><label for="payment-description">Opis</label></th>
                    <td>
                        <textarea id="payment-description" name="description" class="large-text" rows="3"
                                  placeholder="Dodatkowe informacje o płatności"></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="payment-amount">Kwota (PLN) *</label></th>
                    <td>
                        <input type="number" id="payment-amount" name="total_amount" class="regular-text" 
                               step="0.01" min="0" required placeholder="500.00">
                    </td>
                </tr>
                <tr>
                    <th><label for="payment-due-date">Termin płatności</label></th>
                    <td>
                        <input type="date" id="payment-due-date" name="due_date" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="payment-installments">Podziel na raty</label></th>
                    <td>
                        <input type="number" id="payment-installments" name="installments_count" 
                               class="small-text" min="1" max="12" value="1">
                        <p class="description">Liczba rat (1 = płatność jednorazowa)</p>
                        
                        <div id="installments-config" style="display:none; margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 8px;">
                            <h4 style="margin: 0 0 10px 0;">Konfiguracja rat</h4>
                            <div id="installments-list"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="payment-status">Status</label></th>
                    <td>
                        <select id="payment-status" name="status" class="regular-text">
                            <option value="pending">Oczekująca</option>
                            <option value="partial">Częściowo opłacona</option>
                            <option value="paid">Opłacona</option>
                            <option value="cancelled">Anulowana</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" class="button button-primary">Zapisz płatność</button>
                <button type="button" class="button" id="ssm-cancel-payment-btn">Anuluj</button>
            </p>
        </form>
    </div>
    
    <!-- Lista płatności -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 100px;">Data utworzenia</th>
                <th>Klient</th>
                <th>Tytuł</th>
                <th style="width: 100px;">Kwota</th>
                <th style="width: 100px;">Zapłacono</th>
                <th style="width: 100px;">Termin</th>
                <th style="width: 80px;">Raty</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 150px;">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($payments): ?>
                <?php foreach ($payments as $payment): 
                    $is_overdue = ($payment->status == 'pending' && $payment->due_date && $payment->due_date < date('Y-m-d'));
                    $status_colors = array(
                        'pending' => array('bg' => '#fef3c7', 'text' => '#d97706', 'label' => 'Oczekująca'),
                        'partial' => array('bg' => '#dbeafe', 'text' => '#2563eb', 'label' => 'Częściowa'),
                        'paid' => array('bg' => '#d1fae5', 'text' => '#059669', 'label' => 'Opłacona'),
                        'cancelled' => array('bg' => '#fee2e2', 'text' => '#dc2626', 'label' => 'Anulowana')
                    );
                    $status = $status_colors[$payment->status] ?? $status_colors['pending'];
                ?>
                <tr style="<?php echo $is_overdue ? 'background: #fee2e2;' : ''; ?>">
                    <td>
                        <?php echo $payment->created_at ? date('d.m.Y', strtotime($payment->created_at)) : '-'; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($payment->client_name); ?></strong><br>
                        <small style="color: #666;"><?php echo esc_html($payment->client_email); ?></small>
                    </td>
                    <td>
                        <strong><?php echo esc_html($payment->title); ?></strong>
                        <?php if ($payment->description): ?>
                            <br><small style="color: #666;"><?php echo esc_html(wp_trim_words($payment->description, 10)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo number_format($payment->total_amount, 2, ',', ' '); ?> PLN</strong></td>
                    <td>
                        <span style="color: #059669; font-weight: 600;">
                            <?php echo number_format($payment->paid_amount, 2, ',', ' '); ?> PLN
                        </span>
                    </td>
                    <td>
                        <?php if ($payment->due_date): ?>
                            <?php echo date('d.m.Y', strtotime($payment->due_date)); ?>
                            <?php if ($is_overdue): ?>
                                <br><small style="color: #dc2626; font-weight: 600;">ZALEGŁE</small>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($payment->installments_count > 1): ?>
                            <span style="background: #dbeafe; color: #2563eb; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                <?php echo $payment->paid_installments; ?>/<?php echo $payment->installments_count; ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['text']; ?>; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; display: inline-block;">
                            <?php echo $status['label']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="?page=ssm-payments&action=view&payment_id=<?php echo $payment->id; ?>" 
                           class="button button-small">
                            Szczegóły
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                        Brak płatności. Dodaj pierwszą płatność!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    // Pokaż formularz
    $('#ssm-add-payment-btn').on('click', function() {
        $('#ssm-payment-form-title').text('Dodaj płatność');
        $('#ssm-payment-form')[0].reset();
        $('#payment-id').val('');
        $('#ssm-payment-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-payment-form-container').offset().top - 50 }, 500);
    });
    
    // Anuluj
    $('#ssm-cancel-payment-btn').on('click', function() {
        $('#ssm-payment-form-container').slideUp();
    });
    
    // Konfiguracja rat
    $('#payment-installments').on('change', function() {
        var count = parseInt($(this).val());
        var amount = parseFloat($('#payment-amount').val()) || 0;
        
        if (count > 1 && amount > 0) {
            $('#installments-config').slideDown();
            var installmentAmount = (amount / count).toFixed(2);
            var html = '';
            
            for (var i = 1; i <= count; i++) {
                html += '<div style="margin-bottom: 10px;">';
                html += '<strong>Rata ' + i + '/' + count + ':</strong> ';
                html += '<input type="number" name="installment_amount_' + i + '" value="' + installmentAmount + '" step="0.01" style="width: 100px;"> PLN ';
                html += '<input type="date" name="installment_date_' + i + '" style="width: 150px;">';
                html += '</div>';
            }
            
            $('#installments-list').html(html);
        } else {
            $('#installments-config').slideUp();
        }
    });
    
    // Zapisz płatność
    $('#ssm-payment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=ssm_save_payment';
        formData += '&nonce=' + ssmAdmin.nonce;
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
});
</script>
