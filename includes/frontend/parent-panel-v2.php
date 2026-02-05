<?php
// Shortcode: [swimming_parent_panel]
// Panel rodzica z nawigacją i podstronami
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div class="ssm-login-required">';
    echo '<p>Musisz być zalogowany aby zobaczyć panel rodzica.</p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="ssm-login-btn">Zaloguj się</a>';
    echo '</div>';
    return;
}

global $wpdb;
$current_user = wp_get_current_user();

// Znajdź klienta po emailu
$client = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
    $current_user->user_email
));

if (!$client): ?>
    <div class="ssm-no-access">
        <h3>⚠️ Brak dostępu</h3>
        <p>Nie znaleziono Twojego konta w systemie szkółki pływania.</p>
        <p>Skontaktuj się z administracją.</p>
    </div>
<?php return; endif;

// Pobierz dzieci klienta
$children = $wpdb->get_results($wpdb->prepare(
    "SELECT ch.*, TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as age
     FROM {$wpdb->prefix}ssm_children ch
     JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
     WHERE cc.client_id = %d AND ch.active = 1
     ORDER BY ch.first_name",
    $client->id
));

// Routing
$active_page = isset($_GET['panel_page']) ? sanitize_text_field($_GET['panel_page']) : 'dashboard';

// Menu
$menu_items = array(
    'dashboard' => array('icon' => '🏠', 'label' => 'Panel główny'),
    'schedule' => array('icon' => '📅', 'label' => 'Harmonogram'),
    'history' => array('icon' => '📚', 'label' => 'Historia zajęć'),
    'makeup' => array('icon' => '🔄', 'label' => 'Odrabianie', 'badge' => true),
    'payments' => array('icon' => '💰', 'label' => 'Płatności'),
    'referrals' => array('icon' => '🎁', 'label' => 'Poleć znajomych'),
    'my-data' => array('icon' => '👤', 'label' => 'Moje dane'),
    'children' => array('icon' => '👶', 'label' => 'Dzieci'),
    'courses' => array('icon' => '🏊', 'label' => 'Kursy'),
    'documents' => array('icon' => '📄', 'label' => 'Dokumenty'),
    'gallery' => array('icon' => '📷', 'label' => 'Galeria'),
);

// Sprawdź nieobecności do odrobienia dla badge
$makeup_count = 0;
if (isset($client->id)) {
    $makeup_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences a
         JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
         WHERE e.client_id = %d AND a.can_makeup = 1 
         AND a.makeup_session_id IS NULL AND a.status = 'reported'",
        $client->id
    ));
}

$days_pl = array(1 => 'Pon', 2 => 'Wt', 3 => 'Śr', 4 => 'Czw', 5 => 'Pt', 6 => 'Sob', 7 => 'Niedz');
?>

<div class="ssm-parent-dashboard">
    
    <!-- Lewy pasek -->
    <div class="ssm-sidebar">
        <div class="ssm-sidebar-header">
            <div class="ssm-user-avatar">
                <?php echo strtoupper(substr($client->first_name, 0, 1)); ?>
            </div>
            <div class="ssm-user-info">
                <h3><?php echo esc_html($client->first_name . ' ' . $client->last_name); ?></h3>
                <p><?php echo esc_html($client->email); ?></p>
            </div>
        </div>
        
        <nav class="ssm-sidebar-nav">
            <?php foreach ($menu_items as $page => $item): ?>
                <a href="<?php echo add_query_arg('panel_page', $page, get_permalink()); ?>" 
                   class="ssm-nav-item <?php echo $active_page === $page ? 'active' : ''; ?>">
                    <span class="ssm-nav-icon"><?php echo $item['icon']; ?></span>
                    <span class="ssm-nav-label"><?php echo $item['label']; ?></span>
                    <?php if (isset($item['badge']) && $item['badge'] && $makeup_count > 0): ?>
                        <span class="ssm-nav-badge"><?php echo $makeup_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="ssm-sidebar-footer">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="ssm-logout-btn">
                🚪 Wyloguj się
            </a>
        </div>
    </div>
    
    <!-- Główna treść -->
    <div class="ssm-main-content">
        <?php
        // Routing podstron
        $page_file = dirname(__FILE__) . '/panel-pages/' . $active_page . '.php';
        
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<h2>Strona nie znaleziona</h2>';
            echo '<p>Wybierz pozycję z menu.</p>';
        }
        ?>
    </div>
    
</div>

<style>
.ssm-parent-dashboard {
    display: flex;
    min-height: 600px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.ssm-sidebar {
    width: 280px;
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
    color: white;
    display: flex;
    flex-direction: column;
}

.ssm-sidebar-header {
    padding: 30px 20px;
    background: rgba(0,0,0,0.2);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.ssm-user-avatar {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

.ssm-user-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
    text-align: center;
}

.ssm-user-info p {
    margin: 0;
    font-size: 13px;
    color: #bdc3c7;
    text-align: center;
}

.ssm-sidebar-nav {
    flex: 1;
    padding: 20px 0;
}

.ssm-nav-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: #ecf0f1;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.ssm-nav-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.ssm-nav-item.active {
    background: rgba(52, 152, 219, 0.2);
    border-left-color: #3498db;
    color: white;
}

.ssm-nav-icon {
    font-size: 20px;
    margin-right: 12px;
    width: 24px;
}

.ssm-nav-label {
    font-size: 15px;
    font-weight: 500;
}

.ssm-sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.ssm-logout-btn {
    display: block;
    padding: 12px;
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    text-align: center;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.ssm-logout-btn:hover {
    background: #e74c3c;
    color: white;
}

.ssm-main-content {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
    background: white;
}

@media (max-width: 768px) {
    .ssm-parent-dashboard {
        flex-direction: column;
    }
    
    .ssm-sidebar {
        width: 100%;
    }
    
    .ssm-sidebar-nav {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 5px;
        padding: 10px;
    }
    
    .ssm-nav-item {
        flex-direction: column;
        text-align: center;
        padding: 10px 5px;
        font-size: 12px;
    }
    
    .ssm-nav-icon {
        margin: 0 0 5px 0;
        font-size: 24px;
    }
    
    .ssm-nav-label {
        font-size: 12px;
    }
}
</style>
