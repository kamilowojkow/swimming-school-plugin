<?php
/**
 * Panel Admin - Ustawienia iFirma.pl
 */
if (!defined('ABSPATH')) exit;

// Sprawdź uprawnienia
if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień do tej strony.');
}

global $wpdb;

// Zapisz ustawienia
if (isset($_POST['ssm_save_ifirma_settings']) && check_admin_referer('ssm_ifirma_settings')) {
    $settings = array(
        'enabled' => isset($_POST['enabled']) ? 1 : 0,
        'api_key' => sanitize_text_field($_POST['api_key']),
        'email' => sanitize_email($_POST['email']),
        'hash_key' => sanitize_text_field($_POST['hash_key']),
        'bank_account' => sanitize_text_field($_POST['bank_account']),
        'vat_rate' => sanitize_text_field($_POST['vat_rate']),
        'auto_issue' => isset($_POST['auto_issue']) ? 1 : 0,
        'email_client' => isset($_POST['email_client']) ? 1 : 0
    );
    
    update_option('ssm_ifirma_settings', $settings);
    
    echo '<div class="notice notice-success"><p>✅ Ustawienia zapisane</p></div>';
}

// Test połączenia
if (isset($_POST['ssm_test_ifirma']) && check_admin_referer('ssm_test_ifirma')) {
    $test_result = ssm_ifirma_test_connection();
    
    if ($test_result['success']) {
        echo '<div class="notice notice-success"><p>✅ ' . esc_html($test_result['message']) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ ' . esc_html($test_result['message']) . '</p></div>';
    }
}

// Pobierz ustawienia
$settings = get_option('ssm_ifirma_settings', array(
    'enabled' => 0,
    'api_key' => '',
    'email' => '',
    'hash_key' => '',
    'bank_account' => '',
    'vat_rate' => 'zw',
    'auto_issue' => 1,
    'email_client' => 1
));

// Pobierz statystyki
$total_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_invoices WHERE status = 'issued'");
$last_invoice = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ssm_invoices WHERE status = 'issued' ORDER BY issued_at DESC LIMIT 1");
$failed_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_invoices WHERE status = 'failed'");

?>

<div class="wrap">
    <h1>🧾 Integracja iFirma.pl</h1>
    <p class="description">Automatyczne wystawianie faktur za opłacone płatności.</p>
    
    <!-- Status połączenia -->
    <div class="ssm-status-box" style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0;">Status integracji</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="padding: 15px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                <div style="font-size: 13px; color: #64748b;">Status</div>
                <div style="font-size: 20px; font-weight: 600; color: #1e293b;">
                    <?php echo $settings['enabled'] ? '✅ Aktywna' : '⚠️ Nieaktywna'; ?>
                </div>
            </div>
            
            <div style="padding: 15px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">
                <div style="font-size: 13px; color: #64748b;">Wystawionych faktur</div>
                <div style="font-size: 20px; font-weight: 600; color: #1e293b;">
                    <?php echo $total_invoices; ?>
                </div>
            </div>
            
            <?php if ($last_invoice): ?>
            <div style="padding: 15px; background: #fefce8; border-left: 4px solid #f59e0b; border-radius: 4px;">
                <div style="font-size: 13px; color: #64748b;">Ostatnia faktura</div>
                <div style="font-size: 16px; font-weight: 600; color: #1e293b;">
                    <?php echo esc_html($last_invoice->invoice_number); ?>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    <?php echo date('d.m.Y H:i', strtotime($last_invoice->issued_at)); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($failed_invoices > 0): ?>
            <div style="padding: 15px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 4px;">
                <div style="font-size: 13px; color: #64748b;">Błędy</div>
                <div style="font-size: 20px; font-weight: 600; color: #ef4444;">
                    <?php echo $failed_invoices; ?>
                </div>
                <a href="?page=swimming-school-ifirma&show_errors=1" style="font-size: 12px;">Zobacz szczegóły</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Formularz ustawień -->
    <form method="post" action="">
        <?php wp_nonce_field('ssm_ifirma_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="enabled">Aktywuj integrację</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="enabled" id="enabled" value="1" <?php checked($settings['enabled'], 1); ?>>
                        Włącz automatyczne wystawianie faktur
                    </label>
                    <p class="description">Po zaznaczeniu, faktury będą wystawiane automatycznie po oznaczeniu płatności jako opłaconej.</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2">
                    <h2 style="margin-bottom: 0;">Dane dostępowe iFirma.pl</h2>
                    <p class="description">Znajdziesz je w iFirma.pl → Ustawienia → Integracje → API</p>
                </th>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="email">Email</label>
                </th>
                <td>
                    <input type="email" name="email" id="email" value="<?php echo esc_attr($settings['email']); ?>" class="regular-text" required>
                    <p class="description">Email konta iFirma.pl</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="api_key">Klucz API</label>
                </th>
                <td>
                    <input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text" required>
                    <p class="description">Klucz API z ustawień iFirma</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="hash_key">Klucz faktura-hash</label>
                </th>
                <td>
                    <input type="text" name="hash_key" id="hash_key" value="<?php echo esc_attr($settings['hash_key']); ?>" class="regular-text" required>
                    <p class="description">Klucz "faktura" używany do generowania hash autoryzacji</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2">
                    <h2 style="margin-bottom: 0;">Dane faktury</h2>
                </th>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bank_account">Numer konta bankowego</label>
                </th>
                <td>
                    <input type="text" name="bank_account" id="bank_account" value="<?php echo esc_attr($settings['bank_account']); ?>" class="regular-text" placeholder="PL00 0000 0000 0000 0000 0000 0000">
                    <p class="description">Numer konta wyświetlany na fakturze</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vat_rate">Stawka VAT</label>
                </th>
                <td>
                    <select name="vat_rate" id="vat_rate" class="regular-text">
                        <option value="zw" <?php selected($settings['vat_rate'], 'zw'); ?>>Zwolniona z VAT</option>
                        <option value="np" <?php selected($settings['vat_rate'], 'np'); ?>>Nie podlega VAT</option>
                        <option value="23" <?php selected($settings['vat_rate'], '23'); ?>>23%</option>
                        <option value="8" <?php selected($settings['vat_rate'], '8'); ?>>8%</option>
                        <option value="5" <?php selected($settings['vat_rate'], '5'); ?>>5%</option>
                        <option value="0" <?php selected($settings['vat_rate'], '0'); ?>>0%</option>
                    </select>
                    <p class="description">Usługi sportowe są zazwyczaj zwolnione z VAT (zw)</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2">
                    <h2 style="margin-bottom: 0;">Opcje automatyzacji</h2>
                </th>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_issue">Automatyczne wystawianie</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_issue" id="auto_issue" value="1" <?php checked($settings['auto_issue'], 1); ?>>
                        Wystawiaj faktury automatycznie po oznaczeniu płatności jako opłaconej
                    </label>
                    <p class="description">Gdy wyłączone, faktury można wystawiać ręcznie z poziomu szczegółów płatności</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="email_client">Email do klienta</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="email_client" id="email_client" value="1" <?php checked($settings['email_client'], 1); ?>>
                        Wysyłaj email z fakturą do klienta
                    </label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="ssm_save_ifirma_settings" class="button button-primary">
                💾 Zapisz ustawienia
            </button>
        </p>
    </form>
    
    <!-- Test połączenia -->
    <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>🔧 Test połączenia</h2>
        <p>Sprawdź czy dane autoryzacyjne są poprawne.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('ssm_test_ifirma'); ?>
            <button type="submit" name="ssm_test_ifirma" class="button">
                🔍 Testuj połączenie z iFirma.pl
            </button>
        </form>
    </div>
    
    <!-- Dokumentacja -->
    <div style="background: #f0f9ff; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3b82f6;">
        <h2 style="margin-top: 0;">📚 Jak skonfigurować?</h2>
        <ol>
            <li>Zaloguj się do <a href="https://www.ifirma.pl" target="_blank">iFirma.pl</a></li>
            <li>Przejdź do <strong>Ustawienia → Integracje → API</strong></li>
            <li>Wygeneruj klucz API i klucz "faktura"</li>
            <li>Skopiuj dane i wklej powyżej</li>
            <li>Zapisz i przetestuj połączenie</li>
        </ol>
        
        <h3>💡 Wskazówki:</h3>
        <ul>
            <li>Upewnij się że masz aktywne konto iFirma.pl</li>
            <li>Klucz API znajdziesz w zakładce "Integracje"</li>
            <li>Faktury można przeglądać w panelu rodzica (zakładka Płatności)</li>
            <li>Błędy wystawiania faktur są logowane i widoczne w statystykach</li>
        </ul>
    </div>
</div>
