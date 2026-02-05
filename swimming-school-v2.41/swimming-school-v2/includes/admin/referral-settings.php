<?php
/**
 * Panel Admin - Ustawienia Programu Poleceń
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień.');
}

global $wpdb;

// Zapisz ustawienia
if (isset($_POST['ssm_save_referral_settings']) && check_admin_referer('ssm_referral_settings')) {
    $settings = array(
        'enabled' => isset($_POST['enabled']) ? 1 : 0,
        'cashback_amount' => floatval($_POST['cashback_amount']),
        'cashback_type' => sanitize_text_field($_POST['cashback_type']),
        'max_cashback' => floatval($_POST['max_cashback']),
        'only_first_payment' => isset($_POST['only_first_payment']) ? 1 : 0,
        'min_payment_amount' => floatval($_POST['min_payment_amount']),
        'email_referrer' => isset($_POST['email_referrer']) ? 1 : 0
    );
    
    update_option('ssm_referral_settings', $settings);
    echo '<div class="notice notice-success"><p>✅ Ustawienia zapisane</p></div>';
}

$settings = get_option('ssm_referral_settings', array(
    'enabled' => 0,
    'cashback_amount' => 50,
    'cashback_type' => 'fixed',
    'max_cashback' => 200,
    'only_first_payment' => 1,
    'min_payment_amount' => 200,
    'email_referrer' => 1
));

// Statystyki
$total_referrals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_referrals WHERE status != 'code_only'");
$credited_referrals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_referrals WHERE status = 'credited'");
$total_cashback = $wpdb->get_var("SELECT SUM(cashback_amount) FROM {$wpdb->prefix}ssm_referrals WHERE status = 'credited'");
$active_wallets = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_wallet WHERE balance > 0");

?>

<div class="wrap">
    <h1>🎁 Program Poleceń - Ustawienia</h1>
    <p class="description">Skonfiguruj system poleceń i cashback.</p>
    
    <!-- Statystyki -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <div style="font-size: 13px; color: #64748b;">Wszystkie polecenia</div>
            <div style="font-size: 32px; font-weight: 700; color: #3b82f6;"><?php echo $total_referrals; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <div style="font-size: 13px; color: #64748b;">Naliczony cashback</div>
            <div style="font-size: 32px; font-weight: 700; color: #10b981;"><?php echo $credited_referrals; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <div style="font-size: 13px; color: #64748b;">Suma cashback</div>
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?php echo number_format($total_cashback, 2, ',', ' '); ?> zł</div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
            <div style="font-size: 13px; color: #64748b;">Aktywne portfele</div>
            <div style="font-size: 32px; font-weight: 700; color: #8b5cf6;"><?php echo $active_wallets; ?></div>
        </div>
    </div>
    
    <!-- Formularz -->
    <form method="post" action="">
        <?php wp_nonce_field('ssm_referral_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Włącz program</th>
                <td>
                    <label>
                        <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled'], 1); ?>>
                        Aktywuj program poleceń
                    </label>
                </td>
            </tr>
            
            <tr>
                <th colspan="2"><h2>Cashback</h2></th>
            </tr>
            
            <tr>
                <th scope="row">Typ cashback</th>
                <td>
                    <select name="cashback_type">
                        <option value="fixed" <?php selected($settings['cashback_type'], 'fixed'); ?>>Stała kwota</option>
                        <option value="percentage" <?php selected($settings['cashback_type'], 'percentage'); ?>>Procent od płatności</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Kwota/Procent</th>
                <td>
                    <input type="number" name="cashback_amount" value="<?php echo esc_attr($settings['cashback_amount']); ?>" min="0" step="0.01" class="regular-text">
                    <p class="description">Stała kwota w zł lub procent (np. 10 dla 10%)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Maksymalny cashback</th>
                <td>
                    <input type="number" name="max_cashback" value="<?php echo esc_attr($settings['max_cashback']); ?>" min="0" step="0.01" class="regular-text">
                    <p class="description">Limit dla cashback procentowego</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2"><h2>Warunki</h2></th>
            </tr>
            
            <tr>
                <th scope="row">Tylko pierwsza płatność</th>
                <td>
                    <label>
                        <input type="checkbox" name="only_first_payment" value="1" <?php checked($settings['only_first_payment'], 1); ?>>
                        Cashback tylko za pierwszą płatność poleconego klienta
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Minimalna kwota płatności</th>
                <td>
                    <input type="number" name="min_payment_amount" value="<?php echo esc_attr($settings['min_payment_amount']); ?>" min="0" step="0.01" class="regular-text">
                    <p class="description">Płatność musi być co najmniej tej wartości aby cashback został naliczony</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2"><h2>Powiadomienia</h2></th>
            </tr>
            
            <tr>
                <th scope="row">Email do polecającego</th>
                <td>
                    <label>
                        <input type="checkbox" name="email_referrer" value="1" <?php checked($settings['email_referrer'], 1); ?>>
                        Wysyłaj email gdy naliczono cashback
                    </label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="ssm_save_referral_settings" class="button button-primary">
                💾 Zapisz ustawienia
            </button>
        </p>
    </form>
    
    <!-- Info -->
    <div style="background: #f0f9ff; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3b82f6;">
        <h2 style="margin-top: 0;">💡 Jak to działa?</h2>
        <ol>
            <li>Rodzic otrzymuje unikalny kod polecający w swoim panelu</li>
            <li>Poleca szkółkę znajomym i dzieli się kodem</li>
            <li>Znajomy rejestruje się podając kod</li>
            <li>Gdy znajomy dokona pierwszej płatności, polecający otrzymuje cashback</li>
            <li>Cashback można wykorzystać przy płatnościach</li>
        </ol>
        
        <p><a href="?page=swimming-school-referrals-list" class="button">Zobacz wszystkie polecenia →</a></p>
    </div>
</div>
