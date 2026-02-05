<?php
/**
 * Parent Panel v2 - Fila Style
 * Shortcode: [swimming_parent_panel]
 */
if (!defined('ABSPATH')) exit;

// Load translations
require_once dirname(dirname(__FILE__)) . '/translations.php';
$trans = SSM_Translations::get_instance();

if (!is_user_logged_in()) {
    echo '<div class="ssm-login-required">';
    echo '<p>' . ssm_t('login_required') . '</p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="ssm-login-btn">' . ssm_t('login') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$current_user = wp_get_current_user();

// Find client by email
$client = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
    $current_user->user_email
));

if (!$client): ?>
    <div class="ssm-no-access">
        <h3><?php echo ssm_t('no_access'); ?></h3>
        <p><?php echo ssm_t('no_data'); ?></p>
    </div>
<?php return; endif;

// Get children
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
$current_lang = ssm_lang();
$languages = ssm_languages();

// Menu structure with sections
$menu_sections = array(
    'main' => array(
        'label' => ssm_t('menu_main'),
        'items' => array(
            'dashboard' => array('icon' => 'ri-dashboard-line', 'label' => ssm_t('dashboard')),
            'schedule' => array('icon' => 'ri-calendar-schedule-line', 'label' => ssm_t('schedule')),
            'history' => array('icon' => 'ri-history-line', 'label' => ssm_t('history')),
        )
    ),
    'management' => array(
        'label' => ssm_t('menu_management'),
        'items' => array(
            'children' => array('icon' => 'ri-group-line', 'label' => ssm_t('children')),
            'courses' => array('icon' => 'ri-swimming-line', 'label' => ssm_t('courses')),
            'makeup' => array('icon' => 'ri-refresh-line', 'label' => ssm_t('makeup'), 'badge' => true),
            'payments' => array('icon' => 'ri-wallet-3-line', 'label' => ssm_t('payments')),
        )
    ),
    'account' => array(
        'label' => ssm_t('menu_account'),
        'items' => array(
            'my-data' => array('icon' => 'ri-user-settings-line', 'label' => ssm_t('my_data')),
            'referrals' => array('icon' => 'ri-gift-line', 'label' => ssm_t('referrals')),
            'documents' => array('icon' => 'ri-file-list-3-line', 'label' => ssm_t('documents')),
            'gallery' => array('icon' => 'ri-image-line', 'label' => ssm_t('gallery')),
        )
    ),
);

// Count makeups for badge
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

// Count pending payments
$pending_payments = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payments
     WHERE client_id = %d AND status = 'pending'",
    $client->id
)) ?: 0;
?>

<!-- Remix Icon CDN -->
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

<div class="ssm-parent-dashboard ssm-fila">

    <!-- Sidebar -->
    <aside class="ssm-sidebar">
        <!-- Logo -->
        <div class="ssm-sidebar-logo">
            <div class="ssm-logo-icon">
                <i class="ri-water-flash-line"></i>
            </div>
            <span class="ssm-logo-text"><?php echo ssm_t('swimming_school'); ?></span>
            <button class="ssm-sidebar-toggle" id="ssmSidebarToggle">
                <i class="ri-menu-line"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="ssm-sidebar-nav">
            <?php foreach ($menu_sections as $section_key => $section): ?>
                <div class="ssm-nav-section">
                    <span class="ssm-nav-section-title"><?php echo $section['label']; ?></span>

                    <?php foreach ($section['items'] as $page => $item):
                        $badge_count = 0;
                        if (isset($item['badge'])) {
                            if ($page === 'makeup') $badge_count = $makeup_count;
                            if ($page === 'payments') $badge_count = $pending_payments;
                        }
                    ?>
                        <a href="<?php echo add_query_arg('panel_page', $page, get_permalink()); ?>"
                           class="ssm-nav-item <?php echo $active_page === $page ? 'active' : ''; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span class="ssm-nav-label"><?php echo $item['label']; ?></span>
                            <?php if ($badge_count > 0): ?>
                                <span class="ssm-nav-badge"><?php echo $badge_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="ssm-sidebar-footer">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="ssm-logout-btn">
                <i class="ri-logout-box-r-line"></i>
                <span><?php echo ssm_t('logout'); ?></span>
            </a>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="ssm-main-area">

        <!-- Top Bar -->
        <header class="ssm-topbar">
            <div class="ssm-topbar-left">
                <div class="ssm-search-box">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="<?php echo ssm_t('search'); ?>" id="ssmSearch">
                </div>
            </div>

            <div class="ssm-topbar-right">
                <!-- Language Selector -->
                <div class="ssm-topbar-dropdown">
                    <button class="ssm-topbar-btn" id="langDropdownBtn">
                        <i class="ri-translate-2"></i>
                        <span class="ssm-lang-current"><?php echo strtoupper($current_lang); ?></span>
                    </button>
                    <div class="ssm-dropdown-menu" id="langDropdown">
                        <?php foreach ($languages as $code => $lang): ?>
                            <a href="<?php echo add_query_arg('lang', $code, $_SERVER['REQUEST_URI']); ?>"
                               class="ssm-dropdown-item <?php echo $current_lang === $code ? 'active' : ''; ?>">
                                <span class="ssm-lang-flag"><?php echo $lang['flag']; ?></span>
                                <span><?php echo $lang['name']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dark Mode Toggle -->
                <button class="ssm-topbar-btn" id="darkModeToggle" title="<?php echo ssm_t('dark_mode'); ?>">
                    <i class="ri-moon-line"></i>
                </button>

                <!-- Notifications -->
                <button class="ssm-topbar-btn" title="<?php echo ssm_t('notifications'); ?>">
                    <i class="ri-notification-3-line"></i>
                    <?php if ($makeup_count > 0): ?>
                        <span class="ssm-topbar-badge"><?php echo $makeup_count; ?></span>
                    <?php endif; ?>
                </button>

                <!-- User Profile -->
                <div class="ssm-topbar-dropdown">
                    <button class="ssm-topbar-profile" id="profileDropdownBtn">
                        <div class="ssm-profile-avatar">
                            <?php echo strtoupper(substr($client->first_name, 0, 1)); ?>
                        </div>
                        <div class="ssm-profile-info">
                            <span class="ssm-profile-name"><?php echo esc_html($client->first_name); ?></span>
                            <span class="ssm-profile-role">Rodzic</span>
                        </div>
                        <i class="ri-arrow-down-s-line"></i>
                    </button>
                    <div class="ssm-dropdown-menu ssm-dropdown-right" id="profileDropdown">
                        <div class="ssm-dropdown-header">
                            <strong><?php echo esc_html($client->first_name . ' ' . $client->last_name); ?></strong>
                            <small><?php echo esc_html($client->email); ?></small>
                        </div>
                        <a href="<?php echo add_query_arg('panel_page', 'my-data', get_permalink()); ?>" class="ssm-dropdown-item">
                            <i class="ri-user-line"></i>
                            <?php echo ssm_t('profile'); ?>
                        </a>
                        <a href="<?php echo add_query_arg('panel_page', 'settings', get_permalink()); ?>" class="ssm-dropdown-item">
                            <i class="ri-settings-3-line"></i>
                            <?php echo ssm_t('settings'); ?>
                        </a>
                        <div class="ssm-dropdown-divider"></div>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="ssm-dropdown-item ssm-text-danger">
                            <i class="ri-logout-box-r-line"></i>
                            <?php echo ssm_t('logout'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="ssm-main-content">
            <?php
            $page_file = dirname(__FILE__) . '/panel-pages/' . $active_page . '.php';

            if (file_exists($page_file)) {
                include $page_file;
            } else {
                echo '<div class="ssm-section">';
                echo '<h2>' . ssm_t('no_data') . '</h2>';
                echo '</div>';
            }
            ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown toggles
    document.querySelectorAll('.ssm-topbar-dropdown > button').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            document.querySelectorAll('.ssm-dropdown-menu.show').forEach(function(d) {
                if (d !== dropdown) d.classList.remove('show');
            });
            dropdown.classList.toggle('show');
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.ssm-dropdown-menu.show').forEach(function(d) {
            d.classList.remove('show');
        });
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('ssmSidebarToggle');
    const sidebar = document.querySelector('.ssm-sidebar');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('ssm-sidebar-collapsed');
        });
    }

    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('ssm-dark-mode');
            const icon = this.querySelector('i');
            if (document.body.classList.contains('ssm-dark-mode')) {
                icon.className = 'ri-sun-line';
                localStorage.setItem('ssm_dark_mode', '1');
            } else {
                icon.className = 'ri-moon-line';
                localStorage.setItem('ssm_dark_mode', '0');
            }
        });

        // Check saved preference
        if (localStorage.getItem('ssm_dark_mode') === '1') {
            document.body.classList.add('ssm-dark-mode');
            darkModeToggle.querySelector('i').className = 'ri-sun-line';
        }
    }
});
</script>
