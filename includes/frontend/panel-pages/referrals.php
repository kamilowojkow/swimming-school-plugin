<?php
/**
 * Panel Rodzica - Poleć znajomych
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Pobierz kod polecający
$referral_code = ssm_get_client_referral_code($client->id);

// Pobierz portfel
$wallet = ssm_get_or_create_wallet($client->id);

// Pobierz polecenia
$referrals = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, 
            CONCAT(c.first_name, ' ', c.last_name) as referred_client_name
     FROM {$wpdb->prefix}ssm_referrals r
     LEFT JOIN {$wpdb->prefix}ssm_clients c ON r.referred_client_id = c.id
     WHERE r.referrer_client_id = %d AND r.status != 'code_only'
     ORDER BY r.created_at DESC",
    $client->id
));

// Pobierz historię transakcji portfela
$transactions = $wpdb->get_results($wpdb->prepare(
    "SELECT wt.*, r.referred_name, r.referred_email
     FROM {$wpdb->prefix}ssm_wallet_transactions wt
     JOIN {$wpdb->prefix}ssm_wallet w ON wt.wallet_id = w.id
     LEFT JOIN {$wpdb->prefix}ssm_referrals r ON wt.referral_id = r.id
     WHERE w.client_id = %d
     ORDER BY wt.created_at DESC
     LIMIT 20",
    $client->id
));

// Obsługa wysłania zaproszenia
if (isset($_POST['send_invitation']) && check_admin_referer('ssm_send_invitation')) {
    $referred_name = sanitize_text_field($_POST['referred_name']);
    $referred_email = sanitize_email($_POST['referred_email']);
    
    if (!empty($referred_name) && !empty($referred_email)) {
        $sent = ssm_send_referral_invitation($client->id, $referred_email, $referred_name);
        
        if ($sent) {
            echo '<div class="ssm-notice success">✅ Zaproszenie wysłane do ' . esc_html($referred_email) . '</div>';
        } else {
            echo '<div class="ssm-notice error">❌ Błąd wysyłania zaproszenia</div>';
        }
    }
}

$settings = get_option('ssm_referral_settings', array(
    'cashback_amount' => 50,
    'cashback_type' => 'fixed'
));

?>

<div class="ssm-page-header">
    <h1>💰 Program Poleceń</h1>
    <p>Poleć znajomym szkółkę i otrzymuj nagrody!</p>
</div>

<!-- Saldo portfela -->
<div class="ssm-wallet-balance" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 16px; color: white; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Dostępne środki</div>
            <div style="font-size: 42px; font-weight: 700;">
                <?php echo number_format($wallet->balance, 2, ',', ' '); ?> zł
            </div>
            <div style="font-size: 13px; opacity: 0.8; margin-top: 8px;">
                Zarobione: <?php echo number_format($wallet->total_earned, 2, ',', ' '); ?> zł | 
                Wykorzystane: <?php echo number_format($wallet->total_spent, 2, ',', ' '); ?> zł
            </div>
        </div>
        
        <?php if ($wallet->balance > 0): ?>
        <div>
            <a href="?panel_page=payments" style="display: inline-block; padding: 12px 24px; background: white; color: #667eea; border-radius: 8px; font-weight: 600; text-decoration: none;">
                💳 Użyj przy płatności
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Twój kod polecający -->
<div class="ssm-card" style="background: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 20px 0;">🎁 Twój kod polecający</h2>
    
    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" 
                   value="<?php echo esc_attr($referral_code); ?>" 
                   id="referral-code-input"
                   readonly
                   style="width: 100%; padding: 15px; font-size: 24px; font-weight: 700; text-align: center; border: 2px solid #667eea; border-radius: 8px; background: #f8f9ff; letter-spacing: 2px;">
        </div>
        
        <button type="button" 
                onclick="copyReferralCode()"
                style="padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; white-space: nowrap;">
            📋 Kopiuj kod
        </button>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
        <p style="margin: 0; font-size: 14px; color: #1e293b;">
            <strong>Jak to działa?</strong><br>
            Podaj znajomym swój kod polecający. Gdy zarejestrują się i dokonają pierwszej płatności, 
            otrzymasz <strong><?php echo $settings['cashback_type'] == 'fixed' ? $settings['cashback_amount'] . ' zł' : $settings['cashback_amount'] . '%'; ?></strong> cashback!
        </p>
    </div>
</div>

<!-- Wyślij zaproszenie -->
<div class="ssm-card" style="background: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 20px 0;">✉️ Wyślij zaproszenie</h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('ssm_send_invitation'); ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Imię znajomego:</label>
                <input type="text" 
                       name="referred_name" 
                       placeholder="Jan Kowalski"
                       required
                       style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Email znajomego:</label>
                <input type="email" 
                       name="referred_email" 
                       placeholder="jan@example.com"
                       required
                       style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
        </div>
        
        <button type="submit" 
                name="send_invitation"
                style="padding: 12px 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            📨 Wyślij zaproszenie
        </button>
    </form>
</div>

<!-- Lista poleceń -->
<div class="ssm-card" style="background: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 20px 0;">👥 Twoje polecenia</h2>
    
    <?php if (!empty($referrals)): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Znajomy</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Status</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Data</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e2e8f0;">Cashback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $ref): 
                        $status_labels = array(
                            'invited' => array('label' => 'Zaproszony', 'color' => '#94a3b8'),
                            'registered' => array('label' => 'Zarejestrowany', 'color' => '#3b82f6'),
                            'paid' => array('label' => 'Opłacił', 'color' => '#f59e0b'),
                            'credited' => array('label' => 'Naliczono', 'color' => '#10b981')
                        );
                        $status = $status_labels[$ref->status] ?? array('label' => $ref->status, 'color' => '#64748b');
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 15px;">
                            <div style="font-weight: 600;">
                                <?php echo esc_html($ref->referred_client_name ?: $ref->referred_name ?: 'Nieznany'); ?>
                            </div>
                            <div style="font-size: 13px; color: #64748b;">
                                <?php echo esc_html($ref->referred_email); ?>
                            </div>
                        </td>
                        <td style="padding: 15px;">
                            <span style="display: inline-block; padding: 4px 12px; background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                <?php echo $status['label']; ?>
                            </span>
                        </td>
                        <td style="padding: 15px; color: #64748b;">
                            <?php echo date('d.m.Y', strtotime($ref->created_at)); ?>
                        </td>
                        <td style="padding: 15px; text-align: right; font-weight: 600;">
                            <?php if ($ref->cashback_amount > 0): ?>
                                <span style="color: #10b981;">+<?php echo number_format($ref->cashback_amount, 2, ',', ' '); ?> zł</span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 10px;">👥</div>
            <p style="margin: 0;">Nie poleciłeś jeszcze nikogo. Zacznij zapraszać znajomych!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Historia portfela -->
<?php if (!empty($transactions)): ?>
<div class="ssm-card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 20px 0;">📊 Historia transakcji</h2>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Data</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Opis</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e2e8f0;">Kwota</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px; color: #64748b;">
                        <?php echo date('d.m.Y H:i', strtotime($tx->created_at)); ?>
                    </td>
                    <td style="padding: 15px;">
                        <?php echo esc_html($tx->description); ?>
                    </td>
                    <td style="padding: 15px; text-align: right; font-weight: 600;">
                        <?php if ($tx->type == 'earned'): ?>
                            <span style="color: #10b981;">+<?php echo number_format($tx->amount, 2, ',', ' '); ?> zł</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">-<?php echo number_format($tx->amount, 2, ',', ' '); ?> zł</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function copyReferralCode() {
    var input = document.getElementById('referral-code-input');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile
    document.execCommand('copy');
    
    alert('✅ Kod skopiowany do schowka: ' + input.value);
}
</script>
