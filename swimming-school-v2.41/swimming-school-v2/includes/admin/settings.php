<?php
// Panel Admin - Ustawienia
if (!defined('ABSPATH')) exit;

// Zapisywanie ustawień
if (isset($_POST['ssm_save_settings'])) {
    update_option('ssm_contract_template_url', esc_url_raw($_POST['contract_template_url']));
    update_option('ssm_health_template_url', esc_url_raw($_POST['health_template_url']));
    update_option('ssm_consent_template_url', esc_url_raw($_POST['consent_template_url']));
    update_option('ssm_notification_email', sanitize_email($_POST['notification_email']));
    update_option('ssm_enable_email_notifications', isset($_POST['enable_email_notifications']) ? '1' : '0');
    update_option('ssm_parent_panel_page', intval($_POST['parent_panel_page']));
    update_option('ssm_instructor_panel_page', intval($_POST['instructor_panel_page']));
    update_option('ssm_bank_transfer_info', wp_kses_post($_POST['bank_transfer_info']));
    update_option('ssm_ifirma_api_key', sanitize_text_field($_POST['ifirma_api_key']));
    update_option('ssm_ifirma_username', sanitize_text_field($_POST['ifirma_username']));
    update_option('ssm_enable_ifirma', isset($_POST['enable_ifirma']) ? '1' : '0');
    
    echo '<div class="notice notice-success"><p>✅ Ustawienia zapisane!</p></div>';
}

// Pobierz obecne ustawienia
$contract_url = get_option('ssm_contract_template_url', '');
$health_url = get_option('ssm_health_template_url', '');
$consent_url = get_option('ssm_consent_template_url', '');
$notification_email = get_option('ssm_notification_email', get_bloginfo('admin_email'));
$enable_notifications = get_option('ssm_enable_email_notifications', '0');
$parent_panel_page = get_option('ssm_parent_panel_page', 0);
$instructor_panel_page = get_option('ssm_instructor_panel_page', 0);
$bank_transfer_info = get_option('ssm_bank_transfer_info', '');
$ifirma_api_key = get_option('ssm_ifirma_api_key', '');
$ifirma_username = get_option('ssm_ifirma_username', '');
$enable_ifirma = get_option('ssm_enable_ifirma', '0');
?>

<div class="wrap">
    <h1>⚙️ Ustawienia Szkółki Pływania</h1>
    
    <form method="post" action="">
        
        <!-- Wzory dokumentów -->
        <div class="ssm-settings-section">
            <h2>📄 Wzory dokumentów</h2>
            <p>Linki do plików PDF z wzorami dokumentów, które rodzice mogą pobrać</p>
            
            <table class="form-table">
                <tr>
                    <th><label for="contract_template_url">Wzór umowy</label></th>
                    <td>
                        <input type="url" id="contract_template_url" name="contract_template_url" 
                               class="regular-text" value="<?php echo esc_url($contract_url); ?>"
                               placeholder="https://twojastrona.pl/wp-content/uploads/umowa.pdf">
                        <p class="description">
                            Link do pliku PDF z wzorem umowy. 
                            <a href="<?php echo admin_url('media-new.php'); ?>" target="_blank">Upload pliku</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="health_template_url">Wzór karty zdrowia</label></th>
                    <td>
                        <input type="url" id="health_template_url" name="health_template_url" 
                               class="regular-text" value="<?php echo esc_url($health_url); ?>"
                               placeholder="https://twojastrona.pl/wp-content/uploads/karta-zdrowia.pdf">
                        <p class="description">Link do pliku PDF z kartą zdrowia</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="consent_template_url">Wzór zgód</label></th>
                    <td>
                        <input type="url" id="consent_template_url" name="consent_template_url" 
                               class="regular-text" value="<?php echo esc_url($consent_url); ?>"
                               placeholder="https://twojastrona.pl/wp-content/uploads/zgody.pdf">
                        <p class="description">Link do pliku PDF ze zgodami (RODO, wizerunek, itp.)</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Strony paneli -->
        <div class="ssm-settings-section">
            <h2>🔗 Przekierowania po zalogowaniu</h2>
            <p>Wybierz strony, na które będą przekierowywani użytkownicy po zalogowaniu</p>
            
            <table class="form-table">
                <tr>
                    <th><label for="parent_panel_page">Strona panelu rodzica</label></th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'parent_panel_page',
                            'id' => 'parent_panel_page',
                            'selected' => $parent_panel_page,
                            'show_option_none' => '-- Wybierz stronę --',
                            'option_none_value' => '0'
                        ));
                        ?>
                        <p class="description">Strona z shortcode <code>[swimming_parent_panel]</code></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="instructor_panel_page">Strona panelu instruktora</label></th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'instructor_panel_page',
                            'id' => 'instructor_panel_page',
                            'selected' => $instructor_panel_page,
                            'show_option_none' => '-- Wybierz stronę --',
                            'option_none_value' => '0'
                        ));
                        ?>
                        <p class="description">Strona z shortcode <code>[swimming_instructor_panel]</code></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Płatności i faktury -->
        <div class="ssm-settings-section">
            <h2>💰 Płatności i faktury</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="bank_transfer_info">Dane do przelewu</label></th>
                    <td>
                        <textarea id="bank_transfer_info" name="bank_transfer_info" 
                                  class="large-text code" rows="8" 
                                  style="font-family: monospace;"><?php echo esc_textarea($bank_transfer_info); ?></textarea>
                        <p class="description">
                            Informacje wyświetlane klientom przy płatnościach (nazwa firmy, numer konta, adres).<br>
                            Możesz użyć HTML dla formatowania.
                        </p>
                        <p class="description">
                            <strong>Przykład:</strong><br>
                            <code style="display: block; margin-top: 5px; padding: 10px; background: #f0f0f0;">
                                Szkółka Pływania AQUA<br>
                                ul. Basenowa 1, 00-001 Warszawa<br>
                                NIP: 123-456-78-90<br>
                                <br>
                                Numer konta:<br>
                                12 3456 7890 1234 5678 9012 3456<br>
                                Bank: PKO BP
                            </code>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Integracja z iFirma -->
        <div class="ssm-settings-section">
            <h2>🧾 Integracja z iFirma.pl</h2>
            <p>Automatyczne wystawianie faktur przez API iFirma.pl</p>
            
            <table class="form-table">
                <tr>
                    <th><label for="enable_ifirma">Włącz integrację</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="enable_ifirma" name="enable_ifirma" value="1"
                                   <?php checked($enable_ifirma, '1'); ?>>
                            Automatyczne wystawianie faktur przez iFirma.pl
                        </label>
                        <p class="description">
                            Po włączeniu faktury będą wystawiane automatycznie po potwierdzeniu płatności.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ifirma_username">iFirma Login</label></th>
                    <td>
                        <input type="text" id="ifirma_username" name="ifirma_username" 
                               class="regular-text" value="<?php echo esc_attr($ifirma_username); ?>"
                               placeholder="twoj-login@example.com">
                        <p class="description">Login do konta iFirma.pl</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ifirma_api_key">iFirma Klucz API</label></th>
                    <td>
                        <input type="password" id="ifirma_api_key" name="ifirma_api_key" 
                               class="regular-text" value="<?php echo esc_attr($ifirma_api_key); ?>"
                               placeholder="klucz-api-z-ifirma">
                        <p class="description">
                            Klucz API z panelu iFirma.pl (Ustawienia → Integracje → API)<br>
                            <a href="https://www.ifirma.pl/api" target="_blank">Dokumentacja API iFirma.pl →</a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Powiadomienia email -->
        <div class="ssm-settings-section">
            <h2>📧 Powiadomienia email</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="enable_email_notifications">Włącz powiadomienia</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="enable_email_notifications" 
                                   name="enable_email_notifications" value="1"
                                   <?php checked($enable_notifications, '1'); ?>>
                            Wysyłaj powiadomienia email
                        </label>
                        <p class="description">Powiadomienia o nowych zapisach, dokumentach, itp.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="notification_email">Email powiadomień</label></th>
                    <td>
                        <input type="email" id="notification_email" name="notification_email" 
                               class="regular-text" value="<?php echo esc_attr($notification_email); ?>"
                               placeholder="admin@szkolka.pl">
                        <p class="description">Adres email, na który będą wysyłane powiadomienia</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Informacje o systemie -->
        <div class="ssm-settings-section">
            <h2>ℹ️ Informacje o systemie</h2>
            
            <table class="form-table">
                <tr>
                    <th>Wersja wtyczki:</th>
                    <td><strong>2.0</strong></td>
                </tr>
                <tr>
                    <th>Shortcody:</th>
                    <td>
                        <code>[swimming_classes_table]</code> - Tabela kursów<br>
                        <code>[swimming_parent_panel]</code> - Panel rodzica<br>
                        <code>[swimming_instructor_panel]</code> - Panel instruktora<br>
                        <code>[swimming_login]</code> - Formularz logowania<br>
                        <code>[swimming_login_gate]</code> - Bramka login + rejestracja
                    </td>
                </tr>
                <tr>
                    <th>Statystyki:</th>
                    <td>
                        <?php
                        global $wpdb;
                        $stats = array(
                            'Rodzice' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_clients"),
                            'Dzieci' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_children"),
                            'Kursy' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_classes"),
                            'Zapisy' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments WHERE status='active'"),
                            'Zdjęcia' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_gallery"),
                            'Dokumenty' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_documents"),
                        );
                        
                        foreach ($stats as $label => $count) {
                            echo "<strong>$label:</strong> $count<br>";
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" name="ssm_save_settings" class="button button-primary button-large">
                💾 Zapisz ustawienia
            </button>
        </p>
        
    </form>
</div>

<style>
.ssm-settings-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.ssm-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
}
</style>
