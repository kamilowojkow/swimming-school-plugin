<?php
/**
 * Klasa instalatora wtyczki Swimming School Manager
 *
 * Obsługuje tworzenie i aktualizację tabel bazy danych
 *
 * @package Swimming_School_Manager
 * @since 2.65
 */

if (!defined('ABSPATH')) {
    exit;
}

class SSM_Installer {

    /**
     * Wersja schematu bazy danych
     */
    const DB_VERSION = '2.67';

    /**
     * Prefix tabel
     */
    private static $prefix;

    /**
     * Charset i collation
     */
    private static $charset_collate;

    /**
     * Uruchom instalację
     */
    public static function install() {
        global $wpdb;

        self::$prefix = $wpdb->prefix . 'ssm_';
        self::$charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tworzenie ról użytkowników
        self::create_roles();

        // Tworzenie wszystkich tabel
        self::create_tables();

        // Aktualizacja kolumn (dla istniejących instalacji)
        self::upgrade_columns();

        // Zapisz wersję schematu
        update_option('ssm_db_version', self::DB_VERSION);
    }

    /**
     * Tworzenie ról użytkowników
     */
    private static function create_roles() {
        // Rola instruktora
        if (!get_role('ssm_instructor')) {
            add_role('ssm_instructor', 'Instruktor SSM', array(
                'read' => true,
                'upload_files' => true
            ));
        }

        // Rola rodzica
        if (!get_role('ssm_parent')) {
            add_role('ssm_parent', 'Rodzic SSM', array(
                'read' => true
            ));
        }
    }

    /**
     * Tworzenie wszystkich tabel
     */
    private static function create_tables() {
        // Tabele główne
        self::create_clients_table();
        self::create_children_table();
        self::create_client_children_table();
        self::create_facilities_table();
        self::create_instructors_table();
        self::create_classes_table();
        self::create_sessions_table();
        self::create_enrollments_table();
        self::create_attendance_table();
        self::create_documents_table();
        self::create_gallery_table();
        self::create_absences_table();
        self::create_instructor_unavailability_table();
        self::create_makeup_slots_table();

        // Płatności
        self::create_payments_table();
        self::create_payment_installments_table();
        self::create_payment_transactions_table();
        self::create_invoices_table();

        // Program poleceniowy
        self::create_referrals_table();
        self::create_wallet_table();
        self::create_wallet_transactions_table();

        // Gamifikacja
        self::create_achievements_table();
        self::create_child_achievements_table();
        self::create_child_points_table();
        self::create_points_history_table();

        // Oceny
        self::create_session_ratings_table();

        // Powiadomienia
        self::create_notifications_table();
        self::create_notification_sent_table();

        // API / Tokeny
        self::create_auth_tokens_table();
        self::create_push_tokens_table();
    }

    /**
     * Tabela: Klienci (Rodzice)
     */
    private static function create_clients_table() {
        $table = self::$prefix . 'clients';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT NULL,
            address text DEFAULT NULL,
            company_name varchar(255) DEFAULT NULL,
            nip varchar(20) DEFAULT NULL,
            invoice_address varchar(255) DEFAULT NULL,
            invoice_postal_code varchar(10) DEFAULT NULL,
            invoice_city varchar(100) DEFAULT NULL,
            wants_invoice tinyint(1) DEFAULT 0,
            date_of_birth date DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY user_id (user_id)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Dzieci
     */
    private static function create_children_table() {
        $table = self::$prefix . 'children';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            date_of_birth date DEFAULT NULL,
            gender varchar(10) DEFAULT NULL,
            photo varchar(255) DEFAULT NULL,
            swimming_level varchar(50) DEFAULT NULL,
            medical_notes text DEFAULT NULL,
            skills_description text DEFAULT NULL,
            attendance_rate decimal(5,2) DEFAULT 0.00,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY active (active)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Relacje Rodzic-Dziecko
     */
    private static function create_client_children_table() {
        $table = self::$prefix . 'client_children';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Obiekty
     */
    private static function create_facilities_table() {
        $table = self::$prefix . 'facilities';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            address varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            url varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Instruktorzy
     */
    private static function create_instructors_table() {
        $table = self::$prefix . 'instructors';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Kursy
     */
    private static function create_classes_table() {
        $table = self::$prefix . 'classes';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
            max_absences int DEFAULT 2,
            allow_makeups tinyint(1) DEFAULT 1,
            description text DEFAULT NULL,
            url varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY facility_id (facility_id),
            KEY instructor_id (instructor_id),
            KEY status (status)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Sesje/Harmonogram
     */
    private static function create_sessions_table() {
        $table = self::$prefix . 'sessions';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Zapisy
     */
    private static function create_enrollments_table() {
        $table = self::$prefix . 'enrollments';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Frekwencja
     */
    private static function create_attendance_table() {
        $table = self::$prefix . 'attendance';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id mediumint(9) NOT NULL,
            child_id mediumint(9) NOT NULL,
            enrollment_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'present',
            notes text DEFAULT NULL,
            marked_at datetime DEFAULT NULL,
            marked_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY child_id (child_id),
            KEY enrollment_id (enrollment_id),
            UNIQUE KEY unique_attendance (session_id, child_id)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Dokumenty
     */
    private static function create_documents_table() {
        $table = self::$prefix . 'documents';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            document_type varchar(50) NOT NULL,
            file_url varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            notes text DEFAULT NULL,
            uploaded_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY client_id (client_id)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Galeria
     */
    private static function create_gallery_table() {
        $table = self::$prefix . 'gallery';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(200) DEFAULT NULL,
            description text DEFAULT NULL,
            image_url varchar(255) NOT NULL,
            class_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'published',
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY class_id (class_id)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Nieobecności
     */
    private static function create_absences_table() {
        $table = self::$prefix . 'absences';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Niedyspozycje instruktorów
     */
    private static function create_instructor_unavailability_table() {
        $table = self::$prefix . 'instructor_unavailability';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Terminy odrabiania zajęć (makeup slots)
     */
    private static function create_makeup_slots_table() {
        $table = self::$prefix . 'makeup_slots';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_date date NOT NULL,
            time_start time NOT NULL,
            time_end time NOT NULL,
            class_name varchar(255) DEFAULT NULL,
            facility_name varchar(255) DEFAULT NULL,
            max_spots int DEFAULT 5,
            booked_spots int DEFAULT 0,
            status varchar(50) DEFAULT 'available',
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_date (session_date),
            KEY status (status)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Płatności
     */
    private static function create_payments_table() {
        $table = self::$prefix . 'payments';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Raty płatności
     */
    private static function create_payment_installments_table() {
        $table = self::$prefix . 'payment_installments';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Transakcje płatności (historia wpłat)
     */
    private static function create_payment_transactions_table() {
        $table = self::$prefix . 'payment_transactions';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            payment_id mediumint(9) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) DEFAULT NULL,
            transaction_reference varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY payment_id (payment_id),
            KEY created_at (created_at)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Faktury (iFirma.pl)
     */
    private static function create_invoices_table() {
        $table = self::$prefix . 'invoices';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Polecenia (Referral Program)
     */
    private static function create_referrals_table() {
        $table = self::$prefix . 'referrals';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Portfel klienta
     */
    private static function create_wallet_table() {
        $table = self::$prefix . 'wallet';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            balance decimal(10,2) DEFAULT 0.00,
            total_earned decimal(10,2) DEFAULT 0.00,
            total_spent decimal(10,2) DEFAULT 0.00,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_client (client_id),
            KEY balance (balance)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Transakcje portfela
     */
    private static function create_wallet_transactions_table() {
        $table = self::$prefix . 'wallet_transactions';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Odznaczenia (Achievements)
     */
    private static function create_achievements_table() {
        $table = self::$prefix . 'achievements';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Odznaczenia dzieci
     */
    private static function create_child_achievements_table() {
        $table = self::$prefix . 'child_achievements';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Punkty dzieci
     */
    private static function create_child_points_table() {
        $table = self::$prefix . 'child_points';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Historia punktów
     */
    private static function create_points_history_table() {
        $table = self::$prefix . 'points_history';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Oceny zajęć
     */
    private static function create_session_ratings_table() {
        $table = self::$prefix . 'session_ratings';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Powiadomienia
     */
    private static function create_notifications_table() {
        $table = self::$prefix . 'notifications';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            recipient_type varchar(20) NOT NULL,
            recipient_id mediumint(9) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data longtext DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            priority varchar(20) DEFAULT 'normal',
            action_url varchar(255) DEFAULT NULL,
            push_sent tinyint(1) DEFAULT 0,
            push_sent_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            read_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY recipient (recipient_type, recipient_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY priority (priority),
            KEY created_at (created_at),
            KEY expires_at (expires_at)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Wysłane powiadomienia (deduplikacja)
     */
    private static function create_notification_sent_table() {
        $table = self::$prefix . 'notification_sent';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            notification_key varchar(100) NOT NULL,
            recipient_type varchar(20) NOT NULL,
            recipient_id bigint(20) UNSIGNED NOT NULL,
            sent_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_notification (notification_key, recipient_type, recipient_id),
            KEY sent_at (sent_at)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Auth Tokens (dla REST API)
     */
    private static function create_auth_tokens_table() {
        $table = self::$prefix . 'auth_tokens';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_type varchar(20) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            token varchar(64) NOT NULL,
            refresh_token varchar(64) NOT NULL,
            expires_at datetime NOT NULL,
            revoked tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY token (token),
            KEY refresh_token (refresh_token),
            KEY user_type_user_id (user_type, user_id)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Tabela: Push Tokens (dla powiadomień mobilnych)
     */
    private static function create_push_tokens_table() {
        $table = self::$prefix . 'push_tokens';
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_type varchar(20) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            token varchar(255) NOT NULL,
            platform varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_type_user_id (user_type, user_id),
            KEY token (token)
        ) " . self::$charset_collate . ";";
        dbDelta($sql);
    }

    /**
     * Aktualizacja kolumn dla istniejących instalacji
     */
    private static function upgrade_columns() {
        global $wpdb;

        // Instruktorzy - hourly_rate
        $table = self::$prefix . 'instructors';
        if (self::table_exists($table)) {
            if (!self::column_exists($table, 'hourly_rate')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN hourly_rate decimal(10,2) DEFAULT 0.00 AFTER specialization");
            }
            if (!self::column_exists($table, 'user_id')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER hourly_rate");
                $wpdb->query("ALTER TABLE $table ADD KEY user_id (user_id)");
            }
        }

        // Dzieci - dodatkowe pola
        $table = self::$prefix . 'children';
        if (self::table_exists($table)) {
            if (!self::column_exists($table, 'gender')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN gender varchar(10) DEFAULT NULL AFTER date_of_birth");
            }
            if (!self::column_exists($table, 'photo')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN photo varchar(255) DEFAULT NULL AFTER gender");
            }
            if (!self::column_exists($table, 'swimming_level')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN swimming_level varchar(50) DEFAULT NULL AFTER photo");
            }
            if (!self::column_exists($table, 'skills_description')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN skills_description text DEFAULT NULL AFTER medical_notes");
            }
            if (!self::column_exists($table, 'attendance_rate')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN attendance_rate decimal(5,2) DEFAULT 0.00 AFTER skills_description");
            }
        }

        // Frekwencja - enrollment_id
        $table = self::$prefix . 'attendance';
        if (self::table_exists($table)) {
            if (!self::column_exists($table, 'enrollment_id')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN enrollment_id mediumint(9) DEFAULT NULL AFTER child_id");
                $wpdb->query("ALTER TABLE $table ADD KEY enrollment_id (enrollment_id)");
            }
        }

        // Klienci - pola fakturowe
        $table = self::$prefix . 'clients';
        if (self::table_exists($table)) {
            if (!self::column_exists($table, 'address')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN address text DEFAULT NULL AFTER phone");
            }
            if (!self::column_exists($table, 'company_name')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN company_name varchar(255) DEFAULT NULL AFTER address");
            }
            if (!self::column_exists($table, 'nip')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN nip varchar(20) DEFAULT NULL AFTER company_name");
            }
            if (!self::column_exists($table, 'invoice_address')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN invoice_address varchar(255) DEFAULT NULL AFTER nip");
            }
            if (!self::column_exists($table, 'invoice_postal_code')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN invoice_postal_code varchar(10) DEFAULT NULL AFTER invoice_address");
            }
            if (!self::column_exists($table, 'invoice_city')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN invoice_city varchar(100) DEFAULT NULL AFTER invoice_postal_code");
            }
            if (!self::column_exists($table, 'wants_invoice')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN wants_invoice tinyint(1) DEFAULT 0 AFTER invoice_city");
            }
            if (!self::column_exists($table, 'user_id')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN user_id bigint(20) DEFAULT NULL AFTER date_of_birth");
                $wpdb->query("ALTER TABLE $table ADD KEY user_id (user_id)");
            }
        }

        // Kursy - max_absences, allow_makeups
        $table = self::$prefix . 'classes';
        if (self::table_exists($table)) {
            if (!self::column_exists($table, 'max_absences')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN max_absences int DEFAULT 2 AFTER max_participants");
            }
            if (!self::column_exists($table, 'allow_makeups')) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN allow_makeups tinyint(1) DEFAULT 1 AFTER max_absences");
            }
        }
    }

    /**
     * Sprawdź czy tabela istnieje
     */
    private static function table_exists($table) {
        global $wpdb;
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        return $result === $table;
    }

    /**
     * Sprawdź czy kolumna istnieje w tabeli
     */
    private static function column_exists($table, $column) {
        global $wpdb;
        $result = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE '$column'");
        return !empty($result);
    }

    /**
     * Pobierz listę wszystkich tabel wtyczki
     */
    public static function get_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'ssm_';

        return array(
            $prefix . 'clients',
            $prefix . 'children',
            $prefix . 'client_children',
            $prefix . 'facilities',
            $prefix . 'instructors',
            $prefix . 'classes',
            $prefix . 'sessions',
            $prefix . 'enrollments',
            $prefix . 'attendance',
            $prefix . 'documents',
            $prefix . 'gallery',
            $prefix . 'absences',
            $prefix . 'instructor_unavailability',
            $prefix . 'makeup_slots',
            $prefix . 'payments',
            $prefix . 'payment_installments',
            $prefix . 'payment_transactions',
            $prefix . 'invoices',
            $prefix . 'referrals',
            $prefix . 'wallet',
            $prefix . 'wallet_transactions',
            $prefix . 'achievements',
            $prefix . 'child_achievements',
            $prefix . 'child_points',
            $prefix . 'points_history',
            $prefix . 'session_ratings',
            $prefix . 'notifications',
            $prefix . 'notification_sent',
            $prefix . 'auth_tokens',
            $prefix . 'push_tokens',
        );
    }

    /**
     * Usuń wszystkie tabele (używać ostrożnie!)
     */
    public static function uninstall() {
        global $wpdb;

        $tables = self::get_tables();
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('ssm_db_version');
    }
}
