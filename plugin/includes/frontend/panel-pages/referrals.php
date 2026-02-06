<?php
/**
 * Panel Rodzica - Poleć znajomych (Fila Style)
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
$invitation_message = '';
if (isset($_POST['send_invitation']) && check_admin_referer('ssm_send_invitation')) {
    $referred_name = sanitize_text_field($_POST['referred_name']);
    $referred_email = sanitize_email($_POST['referred_email']);

    if (!empty($referred_name) && !empty($referred_email)) {
        $sent = ssm_send_referral_invitation($client->id, $referred_email, $referred_name);

        if ($sent) {
            $invitation_message = array('type' => 'success', 'text' => ssm_t('invitation_sent') . ' ' . esc_html($referred_email));
        } else {
            $invitation_message = array('type' => 'error', 'text' => ssm_t('invitation_error'));
        }
    }
}

$settings = get_option('ssm_referral_settings', array(
    'cashback_amount' => 50,
    'cashback_type' => 'fixed'
));

// Statystyki
$total_referrals = count($referrals);
$credited_referrals = 0;
$total_earned = $wallet->total_earned ?? 0;

foreach ($referrals as $ref) {
    if ($ref->status == 'credited') {
        $credited_referrals++;
    }
}
?>

<div class="ssm-page-header">
    <h1><i class="ri-gift-line"></i> <?php echo ssm_t('referrals'); ?></h1>
    <p><?php echo ssm_t('referrals_description'); ?></p>
</div>

<?php if ($invitation_message): ?>
<div class="ssm-alert ssm-alert-<?php echo $invitation_message['type']; ?>">
    <i class="ri-<?php echo $invitation_message['type'] == 'success' ? 'checkbox-circle' : 'error-warning'; ?>-line"></i>
    <span><?php echo $invitation_message['text']; ?></span>
</div>
<?php endif; ?>

<!-- Statystyki -->
<div class="ssm-stats-grid">
    <!-- Portfel -->
    <div class="ssm-stat-card ssm-stat-wallet">
        <div class="ssm-stat-icon purple">
            <i class="ri-wallet-3-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($wallet->balance, 2, ',', ' '); ?> zł</h3>
            <p><?php echo ssm_t('available_funds'); ?></p>
        </div>
        <?php if ($wallet->balance > 0): ?>
        <a href="?panel_page=payments" class="ssm-stat-link">
            <i class="ri-arrow-right-line"></i>
        </a>
        <?php endif; ?>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon green">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($total_earned, 2, ',', ' '); ?> zł</h3>
            <p><?php echo ssm_t('total_earned'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon blue">
            <i class="ri-user-add-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $total_referrals; ?></h3>
            <p><?php echo ssm_t('total_referrals'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon orange">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $credited_referrals; ?></h3>
            <p><?php echo ssm_t('credited_referrals'); ?></p>
        </div>
    </div>
</div>

<!-- Twój kod polecający -->
<div class="ssm-section ssm-section-referral-code">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-key-2-line"></i>
            <?php echo ssm_t('your_referral_code'); ?>
        </h3>
    </div>

    <div class="ssm-referral-code-box">
        <div class="ssm-code-input-group">
            <input type="text"
                   value="<?php echo esc_attr($referral_code); ?>"
                   id="referral-code-input"
                   readonly
                   class="ssm-code-input">
            <button type="button" onclick="copyReferralCode()" class="ssm-btn ssm-btn-primary ssm-copy-btn">
                <i class="ri-file-copy-line"></i>
                <span><?php echo ssm_t('copy_code'); ?></span>
            </button>
        </div>
    </div>

    <div class="ssm-info-box">
        <div class="ssm-info-icon">
            <i class="ri-lightbulb-line"></i>
        </div>
        <div class="ssm-info-content">
            <strong><?php echo ssm_t('how_it_works'); ?></strong>
            <p><?php
                $reward_text = $settings['cashback_type'] == 'fixed'
                    ? $settings['cashback_amount'] . ' zł'
                    : $settings['cashback_amount'] . '%';
                echo sprintf(ssm_t('referral_how_it_works_text'), $reward_text);
            ?></p>
        </div>
    </div>
</div>

<!-- Wyślij zaproszenie -->
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-mail-send-line"></i>
            <?php echo ssm_t('send_invitation'); ?>
        </h3>
    </div>

    <form method="post" action="" class="ssm-invitation-form">
        <?php wp_nonce_field('ssm_send_invitation'); ?>

        <div class="ssm-form-grid">
            <div class="ssm-form-group">
                <label class="ssm-form-label"><?php echo ssm_t('friend_name'); ?></label>
                <div class="ssm-input-icon">
                    <i class="ri-user-line"></i>
                    <input type="text"
                           name="referred_name"
                           placeholder="Jan Kowalski"
                           required
                           class="ssm-form-input">
                </div>
            </div>

            <div class="ssm-form-group">
                <label class="ssm-form-label"><?php echo ssm_t('friend_email'); ?></label>
                <div class="ssm-input-icon">
                    <i class="ri-mail-line"></i>
                    <input type="email"
                           name="referred_email"
                           placeholder="jan@example.com"
                           required
                           class="ssm-form-input">
                </div>
            </div>
        </div>

        <button type="submit" name="send_invitation" class="ssm-btn ssm-btn-success">
            <i class="ri-send-plane-line"></i>
            <?php echo ssm_t('send_invitation_btn'); ?>
        </button>
    </form>
</div>

<!-- Lista poleceń -->
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-team-line"></i>
            <?php echo ssm_t('your_referrals'); ?>
        </h3>
        <?php if (!empty($referrals)): ?>
        <span class="ssm-section-count"><?php echo count($referrals); ?> <?php echo ssm_t('referrals_count'); ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($referrals)): ?>
    <div class="ssm-referrals-list">
        <?php foreach ($referrals as $ref):
            $status_labels = array(
                'invited' => array('label' => ssm_t('ref_status_invited'), 'class' => 'muted', 'icon' => 'ri-time-line'),
                'registered' => array('label' => ssm_t('ref_status_registered'), 'class' => 'info', 'icon' => 'ri-user-add-line'),
                'paid' => array('label' => ssm_t('ref_status_paid'), 'class' => 'warning', 'icon' => 'ri-wallet-line'),
                'credited' => array('label' => ssm_t('ref_status_credited'), 'class' => 'success', 'icon' => 'ri-checkbox-circle-line')
            );
            $status = $status_labels[$ref->status] ?? array('label' => $ref->status, 'class' => 'muted', 'icon' => 'ri-question-line');
        ?>
        <div class="ssm-referral-card">
            <div class="ssm-referral-avatar">
                <i class="ri-user-line"></i>
            </div>
            <div class="ssm-referral-info">
                <h4><?php echo esc_html($ref->referred_client_name ?: $ref->referred_name ?: ssm_t('unknown')); ?></h4>
                <p><?php echo esc_html($ref->referred_email); ?></p>
            </div>
            <div class="ssm-referral-meta">
                <span class="ssm-referral-date">
                    <i class="ri-calendar-line"></i>
                    <?php echo date('d.m.Y', strtotime($ref->created_at)); ?>
                </span>
            </div>
            <div class="ssm-referral-status">
                <span class="ssm-badge ssm-badge-<?php echo $status['class']; ?>">
                    <i class="<?php echo $status['icon']; ?>"></i>
                    <?php echo $status['label']; ?>
                </span>
            </div>
            <div class="ssm-referral-cashback">
                <?php if ($ref->cashback_amount > 0): ?>
                    <span class="ssm-cashback-value">+<?php echo number_format($ref->cashback_amount, 2, ',', ' '); ?> zł</span>
                <?php else: ?>
                    <span class="ssm-cashback-empty">—</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="ssm-empty-state">
        <i class="ri-team-line"></i>
        <p><?php echo ssm_t('no_referrals'); ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Historia transakcji -->
<?php if (!empty($transactions)): ?>
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-exchange-line"></i>
            <?php echo ssm_t('transaction_history'); ?>
        </h3>
    </div>

    <div class="ssm-transactions-list">
        <?php foreach ($transactions as $tx): ?>
        <div class="ssm-transaction-item">
            <div class="ssm-transaction-icon <?php echo $tx->type == 'earned' ? 'earned' : 'spent'; ?>">
                <i class="ri-<?php echo $tx->type == 'earned' ? 'add' : 'subtract'; ?>-line"></i>
            </div>
            <div class="ssm-transaction-info">
                <p class="ssm-transaction-desc"><?php echo esc_html($tx->description); ?></p>
                <span class="ssm-transaction-date">
                    <?php echo date('d.m.Y H:i', strtotime($tx->created_at)); ?>
                </span>
            </div>
            <div class="ssm-transaction-amount <?php echo $tx->type; ?>">
                <?php if ($tx->type == 'earned'): ?>
                    +<?php echo number_format($tx->amount, 2, ',', ' '); ?> zł
                <?php else: ?>
                    -<?php echo number_format($tx->amount, 2, ',', ' '); ?> zł
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
/* ========================================
   REFERRALS PAGE - FILA STYLE
   ======================================== */

/* Wallet stat card - reuse from payments */
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

/* Referral code section */
.ssm-section-referral-code {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    border: none;
}

.ssm-section-referral-code .ssm-section-title {
    color: white;
}

.ssm-section-referral-code .ssm-section-title i {
    color: rgba(255,255,255,0.9);
}

.ssm-referral-code-box {
    margin-bottom: 20px;
}

.ssm-code-input-group {
    display: flex;
    gap: 12px;
    align-items: stretch;
}

.ssm-code-input {
    flex: 1;
    padding: 16px 20px;
    font-size: 24px;
    font-weight: 700;
    text-align: center;
    letter-spacing: 3px;
    background: rgba(255,255,255,0.95);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: var(--ssm-radius);
    color: var(--ssm-text);
    outline: none;
}

.ssm-copy-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 24px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    font-weight: 600;
    white-space: nowrap;
}

.ssm-copy-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Info box */
.ssm-info-box {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: rgba(255,255,255,0.15);
    border-radius: var(--ssm-radius);
}

.ssm-info-icon {
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.ssm-info-content {
    flex: 1;
}

.ssm-info-content strong {
    display: block;
    margin-bottom: 6px;
    font-size: 15px;
}

.ssm-info-content p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
    line-height: 1.5;
}

/* Invitation form */
.ssm-invitation-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ssm-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.ssm-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ssm-form-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-input-icon {
    position: relative;
}

.ssm-input-icon i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ssm-text-muted);
    font-size: 18px;
}

.ssm-form-input {
    width: 100%;
    padding: 12px 14px 12px 44px;
    border: 2px solid var(--ssm-border);
    border-radius: var(--ssm-radius);
    font-size: 15px;
    color: var(--ssm-text);
    background: var(--ssm-bg-white);
    transition: all 0.2s ease;
}

.ssm-form-input:focus {
    outline: none;
    border-color: var(--ssm-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.ssm-btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 14px 24px;
    font-weight: 600;
    width: fit-content;
}

.ssm-btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Referrals list */
.ssm-referrals-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ssm-referral-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: var(--ssm-bg-white);
    border: 1px solid var(--ssm-border);
    border-radius: var(--ssm-radius-lg);
    transition: all 0.2s ease;
}

.ssm-referral-card:hover {
    box-shadow: var(--ssm-shadow-md);
    border-color: var(--ssm-border-light);
}

.ssm-referral-avatar {
    width: 48px;
    height: 48px;
    background: var(--ssm-primary-light);
    color: var(--ssm-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.ssm-referral-info {
    flex: 1;
    min-width: 0;
}

.ssm-referral-info h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ssm-referral-info p {
    margin: 0;
    font-size: 13px;
    color: var(--ssm-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ssm-referral-meta {
    display: flex;
    align-items: center;
}

.ssm-referral-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-referral-status {
    flex-shrink: 0;
}

.ssm-referral-cashback {
    min-width: 100px;
    text-align: right;
    flex-shrink: 0;
}

.ssm-cashback-value {
    font-size: 16px;
    font-weight: 700;
    color: var(--ssm-success);
}

.ssm-cashback-empty {
    font-size: 16px;
    color: var(--ssm-text-light);
}

/* Transactions list */
.ssm-transactions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ssm-transaction-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
}

.ssm-transaction-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.ssm-transaction-icon.earned {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-transaction-icon.spent {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

.ssm-transaction-info {
    flex: 1;
}

.ssm-transaction-desc {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: var(--ssm-text);
}

.ssm-transaction-date {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-transaction-amount {
    font-size: 15px;
    font-weight: 700;
    flex-shrink: 0;
}

.ssm-transaction-amount.earned {
    color: var(--ssm-success);
}

.ssm-transaction-amount.spent {
    color: var(--ssm-danger);
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-code-input-group {
        flex-direction: column;
    }

    .ssm-code-input {
        font-size: 18px;
    }

    .ssm-copy-btn {
        justify-content: center;
    }

    .ssm-referral-card {
        flex-wrap: wrap;
    }

    .ssm-referral-info {
        flex: 1 1 100%;
        order: 1;
    }

    .ssm-referral-avatar {
        order: 0;
    }

    .ssm-referral-meta,
    .ssm-referral-status,
    .ssm-referral-cashback {
        order: 2;
    }

    .ssm-referral-cashback {
        min-width: auto;
    }

    .ssm-transaction-item {
        flex-wrap: wrap;
    }

    .ssm-transaction-info {
        flex: 1 1 calc(100% - 56px);
    }

    .ssm-transaction-amount {
        flex: 1 1 100%;
        text-align: right;
        padding-left: 56px;
    }
}
</style>

<script>
function copyReferralCode() {
    var input = document.getElementById('referral-code-input');
    input.select();
    input.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(input.value).then(function() {
        var btn = document.querySelector('.ssm-copy-btn');
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="ri-check-line"></i> <span><?php echo ssm_t('copied'); ?></span>';
        btn.style.background = 'rgba(16, 185, 129, 0.3)';

        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.style.background = '';
        }, 2000);
    }).catch(function() {
        // Fallback dla starszych przeglądarek
        document.execCommand('copy');
        alert('<?php echo ssm_t('code_copied_alert'); ?>');
    });
}
</script>
