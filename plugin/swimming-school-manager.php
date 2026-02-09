<?php
/**
 * Plugin Name: Swimming School Manager
 * Plugin URI: https://github.com/kamilowojkow/swimming-school-plugin
 * Description: System zarządzania szkółką pływania - Rodzice, Dzieci, Instruktorzy, Kursy z harmonogramem
 * Version: 2.66
 * Author: Kamil Owojkow
 * Text Domain: swimming-school
 * Domain Path: /languages
 */

// Zabezpieczenie
if (!defined('ABSPATH')) {
    exit;
}

// Stałe
define('SSM_VERSION', '2.66');
define('SSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Załaduj konfigurację i instalator jako pierwsze
require_once SSM_PLUGIN_DIR . 'includes/class-ssm-config.php';
require_once SSM_PLUGIN_DIR . 'includes/class-ssm-installer.php';

// Funkcja pomocnicza - polskie nazwy dni tygodnia
if (!function_exists('ssm_get_day_name_pl')) {
    function ssm_get_day_name_pl($date_obj) {
        $days = array(
            'Monday' => 'poniedziałek',
            'Tuesday' => 'wtorek',
            'Wednesday' => 'środa',
            'Thursday' => 'czwartek',
            'Friday' => 'piątek',
            'Saturday' => 'sobota',
            'Sunday' => 'niedziela'
        );
        $english_day = $date_obj->format('l');
        return $days[$english_day] ?? $english_day;
    }
}

// Funkcja pomocnicza - polskie nazwy miesięcy (skrócone)
if (!function_exists('ssm_get_month_name_pl')) {
    function ssm_get_month_name_pl($date_obj, $short = true) {
        $months_short = array(
            1 => 'sty', 2 => 'lut', 3 => 'mar', 4 => 'kwi',
            5 => 'maj', 6 => 'cze', 7 => 'lip', 8 => 'sie',
            9 => 'wrz', 10 => 'paź', 11 => 'lis', 12 => 'gru'
        );
        $months_full = array(
            1 => 'styczeń', 2 => 'luty', 3 => 'marzec', 4 => 'kwiecień',
            5 => 'maj', 6 => 'czerwiec', 7 => 'lipiec', 8 => 'sierpień',
            9 => 'wrzesień', 10 => 'październik', 11 => 'listopad', 12 => 'grudzień'
        );
        $month_num = (int) $date_obj->format('n');
        return $short ? ($months_short[$month_num] ?? '') : ($months_full[$month_num] ?? '');
    }
}

class Swimming_School_Manager_V2 {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hooki aktywacji/deaktywacji
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Sprawdź i utwórz tabele jeśli nie istnieją
        add_action('plugins_loaded', array($this, 'check_and_create_tables'));
        
        // Include AJAX handlers
        require_once SSM_PLUGIN_DIR . 'includes/ajax-handlers.php';
        require_once SSM_PLUGIN_DIR . 'includes/ifirma-api.php';
        require_once SSM_PLUGIN_DIR . 'includes/referral-system.php';
        require_once SSM_PLUGIN_DIR . 'includes/registration-hooks.php';
        require_once SSM_PLUGIN_DIR . 'includes/gamification-system.php';
        require_once SSM_PLUGIN_DIR . 'includes/translations.php';
        require_once SSM_PLUGIN_DIR . 'includes/notification-system.php';
        require_once SSM_PLUGIN_DIR . 'includes/api/rest-api.php';

        // Inicjalizacja
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_init', array($this, 'check_and_upgrade_tables'));
        
        // Przekierowania po zalogowaniu
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
        
        // Shortcodes
        add_shortcode('swimming_classes_table', array($this, 'classes_table_shortcode'));
        add_shortcode('swimming_parent_panel', array($this, 'parent_panel_shortcode'));
        add_shortcode('swimming_instructor_panel', array($this, 'instructor_panel_shortcode'));
        add_shortcode('swimming_login', array($this, 'login_form_shortcode'));
        add_shortcode('swimming_login_gate', array($this, 'login_gate_shortcode'));
    }
    
    public function check_and_create_tables() {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'ssm_clients';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_clients'");

        if ($table_exists != $table_clients) {
            // Użyj instalatora do utworzenia wszystkich tabel
            SSM_Installer::install();
        }
    }
    
    public function check_and_upgrade_tables() {
        // Sprawdź czy potrzebna jest aktualizacja schematu
        $current_db_version = get_option('ssm_db_version', '0');

        if (version_compare($current_db_version, SSM_Installer::DB_VERSION, '<')) {
            // Uruchom instalator, który utworzy brakujące tabele i zaktualizuje kolumny
            SSM_Installer::install();
        }
    }
    
    public function activate() {
        // Użyj dedykowanej klasy instalatora do utworzenia wszystkich tabel
        SSM_Installer::install();
    }

    public function deactivate() {
        // Placeholder
    }
    
    public function init() {
        load_plugin_textdomain('swimming-school', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Szkółka Pływania',
            'Szkółka Pływania',
            'manage_options',
            'swimming-school',
            array($this, 'dashboard_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page('swimming-school', 'Dashboard', 'Dashboard', 'manage_options', 'swimming-school', array($this, 'dashboard_page'));
        add_submenu_page('swimming-school', 'Rodzice', 'Rodzice', 'manage_options', 'ssm-clients', array($this, 'clients_page'));
        add_submenu_page('swimming-school', 'Dzieci', 'Dzieci', 'manage_options', 'ssm-children', array($this, 'children_page'));
        add_submenu_page('swimming-school', 'Obiekty', 'Obiekty', 'manage_options', 'ssm-facilities', array($this, 'facilities_page'));
        add_submenu_page('swimming-school', 'Instruktorzy', 'Instruktorzy', 'manage_options', 'ssm-instructors', array($this, 'instructors_page'));
        add_submenu_page('swimming-school', 'Kursy', 'Kursy', 'manage_options', 'ssm-classes', array($this, 'classes_page'));
        add_submenu_page('swimming-school', 'Zapisy', 'Zapisy', 'manage_options', 'ssm-enrollments', array($this, 'enrollments_page'));
        add_submenu_page('swimming-school', 'Frekwencja', 'Frekwencja', 'manage_options', 'ssm-attendance', array($this, 'attendance_page'));
        add_submenu_page('swimming-school', 'Płatności', 'Płatności', 'manage_options', 'ssm-payments', array($this, 'payments_page'));
        add_submenu_page('swimming-school', 'Galeria', 'Galeria', 'manage_options', 'ssm-gallery', array($this, 'gallery_page'));
        add_submenu_page('swimming-school', 'iFirma.pl', '🧾 iFirma.pl', 'manage_options', 'swimming-school-ifirma', array($this, 'ifirma_page'));
        add_submenu_page('swimming-school', 'Program Poleceń', '🎁 Program Poleceń', 'manage_options', 'swimming-school-referrals', array($this, 'referrals_settings_page'));
        add_submenu_page('swimming-school', 'System Nagród', '🏆 System Nagród', 'manage_options', 'swimming-school-rewards', array($this, 'rewards_menu_page'));
        add_submenu_page('swimming-school-rewards', 'Odznaczenia', 'Odznaczenia', 'manage_options', 'swimming-school-achievements', array($this, 'achievements_page'));
        add_submenu_page('swimming-school-rewards', 'Tiery', 'Tiery', 'manage_options', 'swimming-school-tiers', array($this, 'tiers_page'));
        add_submenu_page('swimming-school', 'Oceny zajęć', '⭐ Oceny zajęć', 'manage_options', 'swimming-school-ratings', array($this, 'ratings_page'));
        add_submenu_page('swimming-school', 'Powiadomienia', '🔔 Powiadomienia', 'manage_options', 'swimming-school-notifications', array($this, 'notifications_page'));
        add_submenu_page(null, 'Lista poleceń', 'Lista poleceń', 'manage_options', 'swimming-school-referrals-list', array($this, 'referrals_list_page')); // Ukryte submenu
        add_submenu_page('swimming-school', 'Ustawienia', 'Ustawienia', 'manage_options', 'ssm-settings', array($this, 'settings_page'));
        add_submenu_page('swimming-school', 'Srodowisko', 'Srodowisko', 'manage_options', 'ssm-environment', array($this, 'environment_page'));

        // Harmonogram (ukryte menu - dostęp przez ?page=ssm-sessions&class_id=X)
        add_submenu_page(null, 'Harmonogram', 'Harmonogram', 'manage_options', 'ssm-sessions', array($this, 'sessions_page'));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'swimming-school') === false && strpos($hook, 'ssm-') === false) {
            return;
        }

        // Media uploader for logo settings
        wp_enqueue_media();

        wp_enqueue_style('ssm-admin-css', SSM_PLUGIN_URL . 'assets/css/admin.css', array(), SSM_VERSION);
        wp_enqueue_script('ssm-admin-js', SSM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SSM_VERSION, true);

        wp_localize_script('ssm-admin-js', 'ssmAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssm_admin_nonce')
        ));
    }
    
    public function enqueue_frontend_scripts() {
        // Nowy Fila Style
        wp_enqueue_style('ssm-fila-style', SSM_PLUGIN_URL . 'assets/css/fila-style.css', array(), SSM_VERSION);
        // Stary frontend jako fallback
        wp_enqueue_style('ssm-frontend-css', SSM_PLUGIN_URL . 'assets/css/frontend.css', array('ssm-fila-style'), SSM_VERSION);
        wp_enqueue_script('ssm-frontend-js', SSM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SSM_VERSION, true);
    }
    
    // Includy stron admin
    public function dashboard_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/dashboard.php';
    }
    
    public function clients_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/clients.php';
    }
    
    public function children_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/children.php';
    }
    
    public function facilities_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/facilities.php';
    }
    
    public function instructors_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/instructors.php';
    }
    
    public function classes_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/classes.php';
    }
    
    public function sessions_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/sessions.php';
    }
    
    public function enrollments_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/enrollments.php';
    }
    
    public function attendance_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/attendance.php';
    }
    
    public function payments_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/payments.php';
    }
    
    public function gallery_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/gallery.php';
    }
    
    public function ifirma_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/ifirma-settings.php';
    }
    
    public function referrals_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/referral-settings.php';
    }
    
    public function referrals_list_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/referrals-list.php';
    }
    
    public function rewards_menu_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/rewards-dashboard.php';
    }
    
    public function achievements_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/achievements-manager.php';
    }
    
    public function tiers_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/tiers-manager.php';
    }
    
    public function ratings_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/ratings-list.php';
    }

    public function notifications_page() {
        require_once plugin_dir_path(__FILE__) . 'includes/admin/notifications-admin.php';
    }

    public function settings_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/settings.php';
    }

    public function environment_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/environment-settings.php';
    }
    
    // Shortcodes
    public function classes_table_shortcode($atts) {
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/classes-table.php';
        return ob_get_clean();
    }
    
    public function parent_panel_shortcode($atts) {
        // Ukryj pasek admina WordPress
        add_filter('show_admin_bar', '__return_false');
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/parent-panel-v2.php';
        return ob_get_clean();
    }

    public function instructor_panel_shortcode($atts) {
        // Ukryj pasek admina WordPress
        add_filter('show_admin_bar', '__return_false');
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/instructor-panel.php';
        return ob_get_clean();
    }
    
    public function login_form_shortcode($atts) {
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/login-form.php';
        return ob_get_clean();
    }
    
    public function login_gate_shortcode($atts) {
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/login-gate.php';
        return ob_get_clean();
    }
    
    // Przekierowanie po zalogowaniu
    public function custom_login_redirect($redirect_to, $request, $user) {
        // Jeśli błąd logowania
        if (isset($user->errors) && !empty($user->errors)) {
            return $redirect_to;
        }
        
        global $wpdb;
        
        // Sprawdź czy użytkownik jest instruktorem
        $instructor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_instructors 
             WHERE user_id = %d OR email = %s 
             AND active = 1",
            $user->ID,
            $user->user_email
        ));
        
        if ($instructor) {
            // Najpierw sprawdź w ustawieniach
            $instructor_page_id = get_option('ssm_instructor_panel_page', 0);
            if ($instructor_page_id) {
                return get_permalink($instructor_page_id);
            }
            
            // Fallback - znajdź automatycznie
            $instructor_page = $wpdb->get_var(
                "SELECT ID FROM {$wpdb->prefix}posts 
                 WHERE post_content LIKE '%[swimming_instructor_panel]%' 
                 AND post_status = 'publish' 
                 AND post_type = 'page'
                 LIMIT 1"
            );
            
            if ($instructor_page) {
                return get_permalink($instructor_page);
            }
        }
        
        // Sprawdź czy użytkownik jest rodzicem
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
            $user->user_email
        ));
        
        if ($client) {
            // Najpierw sprawdź w ustawieniach
            $parent_page_id = get_option('ssm_parent_panel_page', 0);
            if ($parent_page_id) {
                return get_permalink($parent_page_id);
            }
            
            // Fallback - znajdź automatycznie
            $parent_page = $wpdb->get_var(
                "SELECT ID FROM {$wpdb->prefix}posts 
                 WHERE post_content LIKE '%[swimming_parent_panel]%' 
                 AND post_status = 'publish' 
                 AND post_type = 'page'
                 LIMIT 1"
            );
            
            if ($parent_page) {
                return get_permalink($parent_page);
            }
        }
        
        // Domyślnie zwróć oryginalny redirect
        return $redirect_to;
    }
    
    // Helper: Generowanie harmonogramu
    public function generate_schedule($class_id) {
        global $wpdb;
        
        $class = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_classes WHERE id = %d",
            $class_id
        ));
        
        if (!$class) return false;
        
        // Usuń stare sesje
        $wpdb->delete($wpdb->prefix . 'ssm_sessions', array('class_id' => $class_id));
        
        $current_date = new DateTime($class->start_date);
        $day_of_week = $class->day_of_week;
        
        for ($i = 1; $i <= $class->session_count; $i++) {
            $wpdb->insert($wpdb->prefix . 'ssm_sessions', array(
                'class_id' => $class_id,
                'session_number' => $i,
                'session_date' => $current_date->format('Y-m-d'),
                'time_start' => $class->time_start,
                'time_end' => $class->time_end,
                'instructor_id' => $class->instructor_id,
                'status' => 'scheduled',
                'created_at' => current_time('mysql')
            ));
            
            // Dodaj 7 dni (następny tydzień)
            $current_date->modify('+7 days');
        }
        
        return true;
    }
    
}

// Inicjalizacja wtyczki
function swimming_school_manager_v2() {
    return Swimming_School_Manager_V2::get_instance();
}

swimming_school_manager_v2();
