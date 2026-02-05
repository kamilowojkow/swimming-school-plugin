<?php
/**
 * Panel Admin - Lista poleceń
 */
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień.');
}

global $wpdb;

// Filtry
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

// Query
$where = array("1=1");
$params = array();

if ($status_filter != 'all') {
    $where[] = "r.status = %s";
    $params[] = $status_filter;
}

$where[] = "r.created_at BETWEEN %s AND %s";
$params[] = $date_from . ' 00:00:00';
$params[] = $date_to . ' 23:59:59';

$where_sql = implode(' AND ', $where);

$referrals = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*,
            CONCAT(referrer.first_name, ' ', referrer.last_name) as referrer_name,
            referrer.email as referrer_email,
            CONCAT(referred.first_name, ' ', referred.last_name) as referred_name,
            referred.email as referred_email
     FROM {$wpdb->prefix}ssm_referrals r
     JOIN {$wpdb->prefix}ssm_clients referrer ON r.referrer_client_id = referrer.id
     LEFT JOIN {$wpdb->prefix}ssm_clients referred ON r.referred_client_id = referred.id
     WHERE $where_sql
     ORDER BY r.created_at DESC",
    ...$params
));

$status_labels = array(
    'code_only' => 'Tylko kod',
    'invited' => 'Zaproszony',
    'registered' => 'Zarejestrowany',
    'paid' => 'Opłacił',
    'credited' => 'Naliczono'
);

?>

<div class="wrap">
    <h1>👥 Lista poleceń</h1>
    <p class="description">Wszystkie polecenia klientów</p>
    
    <!-- Filtry -->
    <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <form method="get" action="">
            <input type="hidden" name="page" value="swimming-school-referrals-list">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data od:</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="regular-text">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data do:</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="regular-text">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Status:</label>
                    <select name="status" class="regular-text">
                        <option value="all" <?php selected($status_filter, 'all'); ?>>Wszystkie</option>
                        <?php foreach ($status_labels as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php selected($status_filter, $key); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="button button-primary">🔍 Filtruj</button>
            <a href="?page=swimming-school-referrals-list" class="button">↺ Reset</a>
        </form>
    </div>
    
    <!-- Tabela -->
    <div style="background: white; padding: 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Polecający</th>
                    <th>Kod</th>
                    <th>Polecony</th>
                    <th>Status</th>
                    <th>Cashback</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($referrals): ?>
                    <?php foreach ($referrals as $ref): 
                        $status_colors = array(
                            'code_only' => '#94a3b8',
                            'invited' => '#3b82f6',
                            'registered' => '#8b5cf6',
                            'paid' => '#f59e0b',
                            'credited' => '#10b981'
                        );
                        $color = $status_colors[$ref->status] ?? '#64748b';
                    ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($ref->created_at)); ?></td>
                        <td>
                            <strong><?php echo esc_html($ref->referrer_name); ?></strong><br>
                            <small style="color: #666;"><?php echo esc_html($ref->referrer_email); ?></small>
                        </td>
                        <td>
                            <code style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                <?php echo esc_html($ref->referral_code); ?>
                            </code>
                        </td>
                        <td>
                            <?php if ($ref->referred_name): ?>
                                <strong><?php echo esc_html($ref->referred_name); ?></strong><br>
                                <small style="color: #666;"><?php echo esc_html($ref->referred_email); ?></small>
                            <?php elseif ($ref->referred_email): ?>
                                <small style="color: #666;"><?php echo esc_html($ref->referred_email); ?></small>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 12px; background: <?php echo $color; ?>20; color: <?php echo $color; ?>; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                <?php echo $status_labels[$ref->status] ?? $ref->status; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($ref->cashback_amount > 0): ?>
                                <strong style="color: #10b981;">
                                    <?php echo number_format($ref->cashback_amount, 2, ',', ' '); ?> zł
                                </strong>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                            Brak poleceń w wybranym okresie
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Podsumowanie -->
    <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
        <strong>Znaleziono: <?php echo count($referrals); ?> poleceń</strong>
        <?php if ($status_filter != 'all'): ?>
            (status: <?php echo $status_labels[$status_filter]; ?>)
        <?php endif; ?>
    </div>
</div>
