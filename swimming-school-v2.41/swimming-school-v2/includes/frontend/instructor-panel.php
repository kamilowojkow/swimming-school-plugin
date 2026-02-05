<?php
/**
 * Panel Instruktora - Główny plik
 * Shortcode: [swimming_instructor_panel]
 */

if (!defined('ABSPATH')) exit;

// Sprawdź czy zalogowany
if (!is_user_logged_in()) {
    echo '<div class="ssm-notice ssm-notice-error">Musisz być zalogowany aby zobaczyć panel instruktora.</div>';
    return;
}

global $wpdb;
$current_user = wp_get_current_user();

// Pobierz dane instruktora
$instructor = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
    $current_user->ID,
    $current_user->user_email
));

if (!$instructor) {
    echo '<div class="ssm-notice ssm-notice-error">
        Nie znaleziono profilu instruktora powiązanego z Twoim kontem.<br>
        Skontaktuj się z administratorem.
    </div>';
    return;
}

// Routing
$active_page = isset($_GET['instructor_page']) ? sanitize_text_field($_GET['instructor_page']) : 'dashboard';

// Menu
$menu_items = array(
    'dashboard' => array('icon' => '📊', 'label' => 'Panel główny'),
    'schedule' => array('icon' => '📅', 'label' => 'Moje zajęcia'),
    'attendance' => array('icon' => '✅', 'label' => 'Frekwencja'),
    'gamification' => array('icon' => '🏆', 'label' => 'System Nagród'),
    'substitutions' => array('icon' => '🔄', 'label' => 'Zastępstwa'),
    'salary' => array('icon' => '💰', 'label' => 'Wynagrodzenie'),
);

?>

<div class="ssm-instructor-panel">
    <!-- Sidebar -->
    <div class="ssm-sidebar">
        <div class="ssm-sidebar-header">
            <div class="ssm-user-avatar">
                <?php if ($instructor->photo): ?>
                    <img src="<?php echo esc_url($instructor->photo); ?>" alt="<?php echo esc_attr($instructor->first_name); ?>">
                <?php else: ?>
                    <?php echo strtoupper(substr($instructor->first_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="ssm-user-info">
                <h3><?php echo esc_html($instructor->first_name . ' ' . $instructor->last_name); ?></h3>
                <p>Instruktor</p>
            </div>
        </div>
        
        <nav class="ssm-sidebar-nav">
            <?php foreach ($menu_items as $page => $item): ?>
                <a href="<?php echo add_query_arg('instructor_page', $page, get_permalink()); ?>" 
                   class="ssm-nav-item <?php echo $active_page === $page ? 'active' : ''; ?>">
                    <span class="ssm-nav-icon"><?php echo $item['icon']; ?></span>
                    <span class="ssm-nav-label"><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="ssm-sidebar-footer">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="ssm-logout-btn">
                🚪 Wyloguj się
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="ssm-main-content">
        <?php
        // Routing podstron
        $page_file = dirname(__FILE__) . '/instructor-pages/' . $active_page . '.php';
        
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="ssm-notice ssm-notice-error">Strona nie istnieje.</div>';
        }
        ?>
    </div>
</div>

<style>
.ssm-instructor-panel {
    display: flex;
    min-height: 100vh;
    background: var(--bg-light, #f8fafc);
}

.ssm-instructor-panel .ssm-sidebar {
    width: 280px;
    background: white;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.ssm-instructor-panel .ssm-main-content {
    flex: 1;
    padding: 30px;
}

@media (max-width: 768px) {
    .ssm-instructor-panel {
        flex-direction: column;
    }
    
    .ssm-instructor-panel .ssm-sidebar {
        width: 100%;
    }
}
</style>
