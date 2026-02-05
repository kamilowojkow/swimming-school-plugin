<?php
/**
 * Plugin Name: Swimming School Manager
 * Plugin URI: https://example.com
 * Description: System zarządzania szkółką pływania - Rodzice, Dzieci, Instruktorzy, Kursy z harmonogramem
 * Version: 2.50
 * Author: Twoje Imię
 * Text Domain: swimming-school
 * Domain Path: /languages
 */

// Zabezpieczenie
if (!defined('ABSPATH')) {
    exit;
}

// Stałe
define('SSM_VERSION', '2.50');
define('SSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSM_PLUGIN_URL', plugin_dir_url(__FILE__));

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
            $this->activate();
        }
    }
    
    public function check_and_upgrade_tables() {
        global $wpdb;
        
        // Sprawdź i dodaj brakujące kolumny w instruktorach
        $table_instructors = $wpdb->prefix . 'ssm_instructors';
        
        // Sprawdź czy tabela istnieje
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_instructors'");
        if ($table_exists != $table_instructors) {
            return; // Tabela nie istnieje, zostanie utworzona przy aktywacji
        }
        
        // Dodaj hourly_rate jeśli nie istnieje
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_instructors LIKE 'hourly_rate'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_instructors ADD COLUMN hourly_rate decimal(10,2) DEFAULT 0.00 AFTER specialization");
        }
        
        // Dodaj user_id jeśli nie istnieje
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_instructors LIKE 'user_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_instructors ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER hourly_rate, ADD KEY user_id (user_id)");
        }
        
        // Sprawdź i dodaj kolumny w dzieciach
        $table_children = $wpdb->prefix . 'ssm_children';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_children'");
        if ($table_exists == $table_children) {
            // Dodaj skills_description jeśli nie istnieje
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_children LIKE 'skills_description'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_children ADD COLUMN skills_description text DEFAULT NULL AFTER medical_notes");
            }
            
            // Dodaj attendance_rate jeśli nie istnieje
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_children LIKE 'attendance_rate'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_children ADD COLUMN attendance_rate decimal(5,2) DEFAULT 0.00 AFTER skills_description");
            }
        }
        
        // Sprawdź i utwórz tabelę płatności
        $table_payments = $wpdb->prefix . 'ssm_payments';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_payments'");
        
        if ($table_exists != $table_payments) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_payments = "CREATE TABLE $table_payments (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                client_id mediumint(9) NOT NULL,
                enrollment_id mediumint(9) DEFAULT NULL,
                title varchar(255) NOT NULL,
                description text DEFAULT NULL,
                total_amount decimal(10,2) NOT NULL,
                paid_amount decimal(10,2) DEFAULT 0.00,
                status varchar(20) DEFAULT 'pending',
                due_date date DEFAULT NULL,
                invoice_number varchar(50) DEFAULT NULL,
                invoice_url varchar(255) DEFAULT NULL,
                ifirma_invoice_id varchar(50) DEFAULT NULL,
                notes text DEFAULT NULL,
                created_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY client_id (client_id),
                KEY enrollment_id (enrollment_id),
                KEY status (status),
                KEY due_date (due_date)
            ) $charset_collate;";
            dbDelta($sql_payments);
        }
        
        // Sprawdź i utwórz tabelę rat płatności
        $table_installments = $wpdb->prefix . 'ssm_payment_installments';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_installments'");
        
        if ($table_exists != $table_installments) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_installments = "CREATE TABLE $table_installments (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                payment_id mediumint(9) NOT NULL,
                installment_number int NOT NULL,
                amount decimal(10,2) NOT NULL,
                due_date date NOT NULL,
                paid_amount decimal(10,2) DEFAULT 0.00,
                status varchar(20) DEFAULT 'pending',
                paid_at datetime DEFAULT NULL,
                notes text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY payment_id (payment_id),
                KEY status (status),
                KEY due_date (due_date)
            ) $charset_collate;";
            dbDelta($sql_installments);
        }
        
        // Sprawdź i utwórz tabelę niedyspozycji instruktorów
        $table_unavailability = $wpdb->prefix . 'ssm_instructor_unavailability';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_unavailability'");
        
        if ($table_exists != $table_unavailability) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_unavailability = "CREATE TABLE $table_unavailability (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                instructor_id mediumint(9) NOT NULL,
                session_id mediumint(9) NOT NULL,
                reported_at datetime NOT NULL,
                reason text DEFAULT NULL,
                replacement_instructor_id mediumint(9) DEFAULT NULL,
                status varchar(20) DEFAULT 'pending',
                notes text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY instructor_id (instructor_id),
                KEY session_id (session_id),
                KEY replacement_instructor_id (replacement_instructor_id),
                KEY status (status),
                UNIQUE KEY unique_unavailability (instructor_id, session_id)
            ) $charset_collate;";
            dbDelta($sql_unavailability);
        }
        
        // Tabela: Faktury (iFirma.pl)
        $table_invoices = $wpdb->prefix . 'ssm_invoices';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_invoices'");
        
        if ($table_exists != $table_invoices) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_invoices = "CREATE TABLE $table_invoices (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                payment_id mediumint(9) NOT NULL,
                installment_id mediumint(9) DEFAULT NULL,
                client_id mediumint(9) NOT NULL,
                ifirma_id varchar(100) DEFAULT NULL,
                invoice_number varchar(50) DEFAULT NULL,
                invoice_date date DEFAULT NULL,
                amount decimal(10,2) NOT NULL,
                vat_amount decimal(10,2) DEFAULT 0.00,
                status varchar(20) DEFAULT 'pending',
                pdf_url text DEFAULT NULL,
                xml_data text DEFAULT NULL,
                error_message text DEFAULT NULL,
                created_at datetime NOT NULL,
                issued_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY payment_id (payment_id),
                KEY installment_id (installment_id),
                KEY client_id (client_id),
                KEY status (status),
                KEY invoice_number (invoice_number)
            ) $charset_collate;";
            dbDelta($sql_invoices);
        }
        
        // Tabela: Polecenia (Referral Program)
        $table_referrals = $wpdb->prefix . 'ssm_referrals';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_referrals'");
        
        if ($table_exists != $table_referrals) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_referrals = "CREATE TABLE $table_referrals (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                referrer_client_id mediumint(9) NOT NULL,
                referred_client_id mediumint(9) DEFAULT NULL,
                referral_code varchar(20) NOT NULL,
                referred_email varchar(255) DEFAULT NULL,
                referred_name varchar(255) DEFAULT NULL,
                status varchar(20) DEFAULT 'pending',
                cashback_amount decimal(10,2) DEFAULT 0.00,
                payment_id mediumint(9) DEFAULT NULL,
                created_at datetime NOT NULL,
                registered_at datetime DEFAULT NULL,
                paid_at datetime DEFAULT NULL,
                credited_at datetime DEFAULT NULL,
                notes text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY referrer_client_id (referrer_client_id),
                KEY referred_client_id (referred_client_id),
                KEY referral_code (referral_code),
                KEY status (status),
                UNIQUE KEY unique_code (referral_code)
            ) $charset_collate;";
            dbDelta($sql_referrals);
        }
        
        // Tabela: Portfel klienta
        $table_wallet = $wpdb->prefix . 'ssm_wallet';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_wallet'");
        
        if ($table_exists != $table_wallet) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_wallet = "CREATE TABLE $table_wallet (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                client_id mediumint(9) NOT NULL,
                balance decimal(10,2) DEFAULT 0.00,
                total_earned decimal(10,2) DEFAULT 0.00,
                total_spent decimal(10,2) DEFAULT 0.00,
                updated_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_client (client_id),
                KEY balance (balance)
            ) $charset_collate;";
            dbDelta($sql_wallet);
        }
        
        // Tabela: Transakcje portfela
        $table_wallet_transactions = $wpdb->prefix . 'ssm_wallet_transactions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_wallet_transactions'");
        
        if ($table_exists != $table_wallet_transactions) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_wallet_transactions = "CREATE TABLE $table_wallet_transactions (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                wallet_id mediumint(9) NOT NULL,
                type varchar(20) NOT NULL,
                amount decimal(10,2) NOT NULL,
                description text DEFAULT NULL,
                referral_id mediumint(9) DEFAULT NULL,
                payment_id mediumint(9) DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY wallet_id (wallet_id),
                KEY type (type),
                KEY referral_id (referral_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            dbDelta($sql_wallet_transactions);
        }
        
        // Tabela: Odznaczenia (Achievements)
        $table_achievements = $wpdb->prefix . 'ssm_achievements';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_achievements'");
        
        if ($table_exists != $table_achievements) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_achievements = "CREATE TABLE $table_achievements (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                description text DEFAULT NULL,
                icon varchar(50) DEFAULT NULL,
                category varchar(50) NOT NULL,
                type varchar(50) NOT NULL,
                points int DEFAULT 0,
                criteria text DEFAULT NULL,
                active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY category (category),
                KEY type (type),
                KEY active (active)
            ) $charset_collate;";
            dbDelta($sql_achievements);
        }
        
        // Tabela: Odznaczenia dzieci
        $table_child_achievements = $wpdb->prefix . 'ssm_child_achievements';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_child_achievements'");
        
        if ($table_exists != $table_child_achievements) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_child_achievements = "CREATE TABLE $table_child_achievements (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                child_id mediumint(9) NOT NULL,
                achievement_id mediumint(9) NOT NULL,
                earned_at datetime NOT NULL,
                awarded_by bigint(20) DEFAULT NULL,
                notes text DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_achievement (child_id, achievement_id),
                KEY child_id (child_id),
                KEY achievement_id (achievement_id),
                KEY earned_at (earned_at)
            ) $charset_collate;";
            dbDelta($sql_child_achievements);
        }
        
        // Tabela: Punkty dzieci
        $table_child_points = $wpdb->prefix . 'ssm_child_points';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_child_points'");
        
        if ($table_exists != $table_child_points) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_child_points = "CREATE TABLE $table_child_points (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                child_id mediumint(9) NOT NULL,
                points int DEFAULT 0,
                level int DEFAULT 1,
                tier varchar(50) DEFAULT 'beginner',
                total_earned int DEFAULT 0,
                updated_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_child (child_id),
                KEY points (points),
                KEY level (level),
                KEY tier (tier)
            ) $charset_collate;";
            dbDelta($sql_child_points);
        }
        
        // Tabela: Historia punktów
        $table_points_history = $wpdb->prefix . 'ssm_points_history';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_points_history'");
        
        if ($table_exists != $table_points_history) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_points_history = "CREATE TABLE $table_points_history (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                child_id mediumint(9) NOT NULL,
                points int NOT NULL,
                reason varchar(100) DEFAULT NULL,
                description text DEFAULT NULL,
                session_id mediumint(9) DEFAULT NULL,
                achievement_id mediumint(9) DEFAULT NULL,
                awarded_by bigint(20) DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY child_id (child_id),
                KEY session_id (session_id),
                KEY created_at (created_at)
            ) $charset_collate;";
            dbDelta($sql_points_history);
        }
        
        // Tabela: Oceny zajęć
        $table_ratings = $wpdb->prefix . 'ssm_session_ratings';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_ratings'");
        
        if ($table_exists != $table_ratings) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql_ratings = "CREATE TABLE $table_ratings (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                session_id mediumint(9) NOT NULL,
                client_id mediumint(9) NOT NULL,
                child_id mediumint(9) NOT NULL,
                instructor_id mediumint(9) NOT NULL,
                rating tinyint(1) NOT NULL,
                comment text DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_rating (session_id, client_id),
                KEY session_id (session_id),
                KEY client_id (client_id),
                KEY instructor_id (instructor_id),
                KEY rating (rating),
                KEY created_at (created_at)
            ) $charset_collate;";
            dbDelta($sql_ratings);
        }
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Tabela: Klienci (Rodzice)
        $table_clients = $wpdb->prefix . 'ssm_clients';
        $sql_clients = "CREATE TABLE IF NOT EXISTS $table_clients (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT NULL,
            date_of_birth date DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email)
        ) $charset_collate;";
        dbDelta($sql_clients);
        
        // Tabela: Dzieci
        $table_children = $wpdb->prefix . 'ssm_children';
        $sql_children = "CREATE TABLE IF NOT EXISTS $table_children (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            date_of_birth date DEFAULT NULL,
            medical_notes text DEFAULT NULL,
            skills_description text DEFAULT NULL,
            attendance_rate decimal(5,2) DEFAULT 0.00,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY active (active)
        ) $charset_collate;";
        dbDelta($sql_children);
        
        // Dodaj kolumny jeśli nie istnieją (upgrade)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_children LIKE 'skills_description'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_children ADD COLUMN skills_description text DEFAULT NULL AFTER medical_notes");
        }
        
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_children LIKE 'attendance_rate'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_children ADD COLUMN attendance_rate decimal(5,2) DEFAULT 0.00 AFTER skills_description");
        }
        
        // Tabela: Relacje Rodzic-Dziecko
        $table_client_children = $wpdb->prefix . 'ssm_client_children';
        $sql_client_children = "CREATE TABLE IF NOT EXISTS $table_client_children (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            child_id mediumint(9) NOT NULL,
            relationship varchar(50) DEFAULT 'parent',
            primary_contact tinyint(1) DEFAULT 0,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY child_id (child_id),
            UNIQUE KEY unique_relationship (client_id, child_id)
        ) $charset_collate;";
        dbDelta($sql_client_children);
        
        // Tabela: Obiekty
        $table_facilities = $wpdb->prefix . 'ssm_facilities';
        $sql_facilities = "CREATE TABLE IF NOT EXISTS $table_facilities (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            address varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            url varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_facilities);
        
        // Tabela: Instruktorzy
        $table_instructors = $wpdb->prefix . 'ssm_instructors';
        $sql_instructors = "CREATE TABLE IF NOT EXISTS $table_instructors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) DEFAULT NULL,
            phone varchar(20) DEFAULT NULL,
            bio text DEFAULT NULL,
            photo varchar(255) DEFAULT NULL,
            url varchar(255) DEFAULT NULL,
            specialization varchar(255) DEFAULT NULL,
            hourly_rate decimal(10,2) DEFAULT 0.00,
            user_id bigint(20) DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY active (active),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_instructors);
        
        // Dodaj kolumnę hourly_rate jeśli nie istnieje (upgrade)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_instructors LIKE 'hourly_rate'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_instructors ADD COLUMN hourly_rate decimal(10,2) DEFAULT 0.00 AFTER specialization");
        }
        
        // Dodaj kolumnę user_id jeśli nie istnieje (upgrade)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_instructors LIKE 'user_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_instructors ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER hourly_rate, ADD KEY user_id (user_id)");
        }
        
        // Tabela: Kursy
        $table_classes = $wpdb->prefix . 'ssm_classes';
        $sql_classes = "CREATE TABLE IF NOT EXISTS $table_classes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            facility_id mediumint(9) NOT NULL,
            instructor_id mediumint(9) DEFAULT NULL,
            day_of_week tinyint(1) NOT NULL,
            time_start time NOT NULL,
            time_end time NOT NULL,
            start_date date NOT NULL,
            session_count int NOT NULL DEFAULT 10,
            price_per_session decimal(10,2) DEFAULT 0.00,
            total_price decimal(10,2) DEFAULT 0.00,
            level varchar(50) DEFAULT NULL,
            max_participants int DEFAULT 10,
            description text DEFAULT NULL,
            url varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY facility_id (facility_id),
            KEY instructor_id (instructor_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_classes);
        
        // Tabela: Sesje/Harmonogram
        $table_sessions = $wpdb->prefix . 'ssm_sessions';
        $sql_sessions = "CREATE TABLE IF NOT EXISTS $table_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            class_id mediumint(9) NOT NULL,
            session_number int NOT NULL,
            session_date date NOT NULL,
            time_start time NOT NULL,
            time_end time NOT NULL,
            instructor_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'scheduled',
            room varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY class_id (class_id),
            KEY session_date (session_date),
            UNIQUE KEY unique_session (class_id, session_number)
        ) $charset_collate;";
        dbDelta($sql_sessions);
        
        // Tabela: Zapisy
        $table_enrollments = $wpdb->prefix . 'ssm_enrollments';
        $sql_enrollments = "CREATE TABLE IF NOT EXISTS $table_enrollments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            child_id mediumint(9) NOT NULL,
            class_id mediumint(9) NOT NULL,
            enrollment_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY child_id (child_id),
            KEY class_id (class_id),
            UNIQUE KEY unique_enrollment (child_id, class_id)
        ) $charset_collate;";
        dbDelta($sql_enrollments);
        
        // Tabela: Frekwencja
        $table_attendance = $wpdb->prefix . 'ssm_attendance';
        $sql_attendance = "CREATE TABLE IF NOT EXISTS $table_attendance (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id mediumint(9) NOT NULL,
            child_id mediumint(9) NOT NULL,
            status varchar(20) DEFAULT 'present',
            notes text DEFAULT NULL,
            marked_at datetime DEFAULT NULL,
            marked_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY child_id (child_id),
            UNIQUE KEY unique_attendance (session_id, child_id)
        ) $charset_collate;";
        dbDelta($sql_attendance);
        
        // Tabela: Dokumenty
        $table_documents = $wpdb->prefix . 'ssm_documents';
        $sql_documents = "CREATE TABLE IF NOT EXISTS $table_documents (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            document_type varchar(50) NOT NULL,
            file_url varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            notes text DEFAULT NULL,
            uploaded_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY client_id (client_id)
        ) $charset_collate;";
        dbDelta($sql_documents);
        
        // Tabela: Galeria
        $table_gallery = $wpdb->prefix . 'ssm_gallery';
        $sql_gallery = "CREATE TABLE IF NOT EXISTS $table_gallery (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(200) DEFAULT NULL,
            description text DEFAULT NULL,
            image_url varchar(255) NOT NULL,
            class_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'published',
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY class_id (class_id)
        ) $charset_collate;";
        dbDelta($sql_gallery);
        
        // Dodaj pole address do tabeli clients jeśli nie istnieje
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_clients LIKE 'address'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_clients ADD COLUMN address text DEFAULT NULL AFTER phone");
        }
        
        // Dodaj pola nieobecności do tabeli classes
        $max_absences_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}ssm_classes LIKE 'max_absences'");
        if (empty($max_absences_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_classes ADD COLUMN max_absences int DEFAULT 2 AFTER max_participants");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_classes ADD COLUMN allow_makeups tinyint(1) DEFAULT 1 AFTER max_absences");
        }
        
        // Dodaj pola do faktury w tabeli clients
        $company_name_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}ssm_clients LIKE 'company_name'");
        if (empty($company_name_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_clients ADD COLUMN company_name varchar(255) DEFAULT NULL AFTER phone");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_clients ADD COLUMN nip varchar(20) DEFAULT NULL AFTER company_name");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_clients ADD COLUMN invoice_address varchar(255) DEFAULT NULL AFTER nip");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_clients ADD COLUMN invoice_postal_code varchar(10) DEFAULT NULL AFTER invoice_address");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_clients ADD COLUMN invoice_city varchar(100) DEFAULT NULL AFTER invoice_postal_code");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}ssm_clients ADD COLUMN wants_invoice tinyint(1) DEFAULT 0 AFTER invoice_city");
        }
        
        // Tabela: Nieobecności
        $table_absences = $wpdb->prefix . 'ssm_absences';
        $sql_absences = "CREATE TABLE IF NOT EXISTS $table_absences (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            enrollment_id mediumint(9) NOT NULL,
            session_id mediumint(9) NOT NULL,
            child_id mediumint(9) NOT NULL,
            reported_at datetime NOT NULL,
            reported_by bigint(20) DEFAULT NULL,
            reason text DEFAULT NULL,
            can_makeup tinyint(1) DEFAULT 1,
            makeup_session_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'reported',
            PRIMARY KEY (id),
            KEY enrollment_id (enrollment_id),
            KEY session_id (session_id),
            KEY child_id (child_id),
            UNIQUE KEY unique_absence (session_id, child_id)
        ) $charset_collate;";
        dbDelta($sql_absences);
        
        // Tabela: Niedyspozycje instruktorów
        $table_unavailability = $wpdb->prefix . 'ssm_instructor_unavailability';
        $sql_unavailability = "CREATE TABLE IF NOT EXISTS $table_unavailability (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            instructor_id mediumint(9) NOT NULL,
            session_id mediumint(9) NOT NULL,
            reported_at datetime NOT NULL,
            reason text DEFAULT NULL,
            replacement_instructor_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY instructor_id (instructor_id),
            KEY session_id (session_id),
            KEY replacement_instructor_id (replacement_instructor_id),
            KEY status (status),
            UNIQUE KEY unique_unavailability (instructor_id, session_id)
        ) $charset_collate;";
        dbDelta($sql_unavailability);
        
        // Tabela: Płatności
        $table_payments = $wpdb->prefix . 'ssm_payments';
        $sql_payments = "CREATE TABLE IF NOT EXISTS $table_payments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            enrollment_id mediumint(9) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            total_amount decimal(10,2) NOT NULL,
            paid_amount decimal(10,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'pending',
            due_date date DEFAULT NULL,
            invoice_number varchar(50) DEFAULT NULL,
            invoice_url varchar(255) DEFAULT NULL,
            ifirma_invoice_id varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY enrollment_id (enrollment_id),
            KEY status (status),
            KEY due_date (due_date)
        ) $charset_collate;";
        dbDelta($sql_payments);
        
        // Tabela: Raty płatności
        $table_payment_installments = $wpdb->prefix . 'ssm_payment_installments';
        $sql_installments = "CREATE TABLE IF NOT EXISTS $table_payment_installments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            payment_id mediumint(9) NOT NULL,
            installment_number int NOT NULL,
            amount decimal(10,2) NOT NULL,
            due_date date NOT NULL,
            paid_amount decimal(10,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'pending',
            paid_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY payment_id (payment_id),
            KEY status (status),
            KEY due_date (due_date)
        ) $charset_collate;";
        dbDelta($sql_installments);
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
        add_submenu_page(null, 'Lista poleceń', 'Lista poleceń', 'manage_options', 'swimming-school-referrals-list', array($this, 'referrals_list_page')); // Ukryte submenu
        add_submenu_page('swimming-school', 'Ustawienia', 'Ustawienia', 'manage_options', 'ssm-settings', array($this, 'settings_page'));
        
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
    
    public function settings_page() {
        include SSM_PLUGIN_DIR . 'includes/admin/settings.php';
    }
    
    // Shortcodes
    public function classes_table_shortcode($atts) {
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/classes-table.php';
        return ob_get_clean();
    }
    
    public function parent_panel_shortcode($atts) {
        ob_start();
        include SSM_PLUGIN_DIR . 'includes/frontend/parent-panel-v2.php';
        return ob_get_clean();
    }
    
    public function instructor_panel_shortcode($atts) {
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
