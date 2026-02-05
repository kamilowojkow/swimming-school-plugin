<?php
/**
 * Panel Instruktora - Fila Style
 * Shortcode: [swimming_instructor_panel]
 */

if (!defined('ABSPATH')) exit;

// Ukryj pasek admina WordPress
add_filter('show_admin_bar', '__return_false');

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

// Pobierz dane instruktora
$instructor = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
    $current_user->ID,
    $current_user->user_email
));

if (!$instructor): ?>
    <div class="ssm-no-access">
        <h3><?php echo ssm_t('no_access'); ?></h3>
        <p><?php echo ssm_t('client_not_found'); ?></p>
    </div>
<?php return; endif;

// Routing
$active_page = isset($_GET['instructor_page']) ? sanitize_text_field($_GET['instructor_page']) : 'dashboard';
$current_lang = ssm_lang();
$languages = ssm_languages();

// Menu structure with sections
$menu_sections = array(
    'main' => array(
        'label' => ssm_t('menu_main'),
        'items' => array(
            'dashboard' => array('icon' => 'ri-dashboard-line', 'label' => ssm_t('dashboard')),
            'schedule' => array('icon' => 'ri-calendar-todo-line', 'label' => ssm_t('instr_my_classes')),
            'attendance' => array('icon' => 'ri-user-follow-line', 'label' => ssm_t('attendance')),
        )
    ),
    'management' => array(
        'label' => ssm_t('menu_management'),
        'items' => array(
            'gamification' => array('icon' => 'ri-trophy-line', 'label' => ssm_t('instr_rewards')),
            'substitutions' => array('icon' => 'ri-exchange-line', 'label' => ssm_t('instr_substitutions'), 'badge' => true),
        )
    ),
    'account' => array(
        'label' => ssm_t('menu_account'),
        'items' => array(
            'salary' => array('icon' => 'ri-money-dollar-circle-line', 'label' => ssm_t('instr_salary')),
        )
    ),
);

// Count available substitutions for badge
$substitution_count = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) FROM {$wpdb->prefix}ssm_instructor_unavailability u
    JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
    WHERE u.instructor_id != %d
    AND u.replacement_instructor_id IS NULL
    AND u.status = 'pending'
    AND s.session_date >= CURDATE()
", $instructor->id)) ?: 0;

// Get logo settings
$logo_desktop = get_option('ssm_logo_desktop', '');
$logo_mobile = get_option('ssm_logo_mobile', '');
$school_name = get_option('ssm_school_name', ssm_t('swimming_school'));
?>

<!-- Remix Icon CDN -->
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

<!-- Ukryj pasek admina WordPress -->
<style>
#wpadminbar, html.wp-toolbar { display: none !important; margin-top: 0 !important; padding-top: 0 !important; }
html { margin-top: 0 !important; }
</style>

<div class="ssm-instructor-dashboard ssm-fila">

    <!-- Sidebar -->
    <aside class="ssm-sidebar">
        <!-- Logo -->
        <div class="ssm-sidebar-logo">
            <?php if ($logo_desktop || $logo_mobile): ?>
                <?php if ($logo_desktop): ?>
                    <img src="<?php echo esc_url($logo_desktop); ?>" alt="<?php echo esc_attr($school_name); ?>" class="ssm-logo-desktop">
                <?php endif; ?>
                <?php if ($logo_mobile): ?>
                    <img src="<?php echo esc_url($logo_mobile); ?>" alt="<?php echo esc_attr($school_name); ?>" class="ssm-logo-mobile">
                <?php elseif ($logo_desktop): ?>
                    <img src="<?php echo esc_url($logo_desktop); ?>" alt="<?php echo esc_attr($school_name); ?>" class="ssm-logo-mobile">
                <?php endif; ?>
            <?php else: ?>
                <div class="ssm-logo-icon">
                    <i class="ri-water-flash-line"></i>
                </div>
                <span class="ssm-logo-text"><?php echo esc_html($school_name); ?></span>
            <?php endif; ?>
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
                            if ($page === 'substitutions') $badge_count = $substitution_count;
                        }
                    ?>
                        <a href="<?php echo add_query_arg('instructor_page', $page, get_permalink()); ?>"
                           class="ssm-nav-item <?php echo $active_page === $page ? 'active' : ''; ?>">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span class="ssm-nav-label"><?php echo $item['label']; ?></span>
                            <?php if ($badge_count > 0): ?>
                                <span class="ssm-nav-badge ssm-nav-badge-warning"><?php echo $badge_count; ?></span>
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
                <!-- Mobile menu toggle -->
                <button class="ssm-sidebar-toggle" id="sidebarToggle">
                    <i class="ri-menu-line"></i>
                </button>
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

                <!-- Notifications / Substitutions -->
                <button class="ssm-topbar-btn" title="<?php echo ssm_t('instr_substitutions'); ?>" onclick="window.location.href='<?php echo add_query_arg('instructor_page', 'substitutions', get_permalink()); ?>'">
                    <i class="ri-exchange-line"></i>
                    <?php if ($substitution_count > 0): ?>
                        <span class="ssm-topbar-badge"><?php echo $substitution_count; ?></span>
                    <?php endif; ?>
                </button>

                <!-- User Profile -->
                <div class="ssm-topbar-dropdown">
                    <button class="ssm-topbar-profile" id="profileDropdownBtn">
                        <div class="ssm-profile-avatar ssm-avatar-instructor">
                            <?php if ($instructor->photo): ?>
                                <img src="<?php echo esc_url($instructor->photo); ?>" alt="<?php echo esc_attr($instructor->first_name); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($instructor->first_name, 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="ssm-profile-info">
                            <span class="ssm-profile-name"><?php echo esc_html($instructor->first_name); ?></span>
                            <span class="ssm-profile-role"><?php echo ssm_t('instructor'); ?></span>
                        </div>
                        <i class="ri-arrow-down-s-line"></i>
                    </button>
                    <div class="ssm-dropdown-menu ssm-dropdown-right" id="profileDropdown">
                        <div class="ssm-dropdown-header">
                            <strong><?php echo esc_html($instructor->first_name . ' ' . $instructor->last_name); ?></strong>
                            <small><?php echo esc_html($instructor->email); ?></small>
                        </div>
                        <a href="<?php echo add_query_arg('instructor_page', 'salary', get_permalink()); ?>" class="ssm-dropdown-item">
                            <i class="ri-money-dollar-circle-line"></i>
                            <?php echo ssm_t('instr_salary'); ?>
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
            $page_file = dirname(__FILE__) . '/instructor-pages/' . $active_page . '.php';

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

<style>
/* Instructor panel uses same Fila styles as parent panel */
.ssm-instructor-dashboard.ssm-fila {
    display: flex;
    min-height: 100vh;
    background: var(--ssm-bg);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    margin: 0 !important;
    padding: 0 !important;
}

/* Instructor-specific avatar styling */
.ssm-avatar-instructor {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
}

.ssm-avatar-instructor img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Warning badge for substitutions */
.ssm-nav-badge-warning {
    background: var(--ssm-warning-light) !important;
    color: var(--ssm-warning) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown toggles
    document.querySelectorAll('.ssm-topbar-dropdown > button').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('ssm-dropdown-menu')) {
                document.querySelectorAll('.ssm-dropdown-menu.show').forEach(function(d) {
                    if (d !== dropdown) d.classList.remove('show');
                });
                dropdown.classList.toggle('show');
            }
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.ssm-dropdown-menu.show').forEach(function(d) {
            d.classList.remove('show');
        });
    });

    // Mobile sidebar toggle
    const sidebarToggles = document.querySelectorAll('.ssm-sidebar-toggle');
    const sidebar = document.querySelector('.ssm-sidebar');
    sidebarToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('ssm-sidebar-open');
        });
    });

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024 && sidebar.classList.contains('ssm-sidebar-open')) {
            if (!sidebar.contains(e.target)) {
                sidebar.classList.remove('ssm-sidebar-open');
            }
        }
    });

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
