<?php
/**
 * Plugin Name: Swimming School Mobile API
 * Plugin URI: https://github.com/kamilowojkow/swimming-school-plugin
 * Description: REST API dla aplikacji mobilnej Szkółki Pływania
 * Version: 2.0.0
 * Author: Kamil Owojkow
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// UWAGA: Tabele bazy danych są tworzone przez główną wtyczkę Swimming School Manager
// w klasie SSM_Installer (includes/class-ssm-installer.php)
// NIE definiujemy tabel tutaj, aby uniknąć konfliktów schematu

add_action('rest_api_init', function () {
    $namespace = 'ssm/v1';

    // Auth
    register_rest_route($namespace, '/auth/login', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_login',
        'permission_callback' => '__return_true',
    ));

    register_rest_route($namespace, '/auth/logout', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_logout',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // User
    register_rest_route($namespace, '/user/me', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_me',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/user/profile', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_me',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/user/profile', array(
        'methods' => 'PUT',
        'callback' => 'ssm_api_update_profile',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/user/password', array(
        'methods' => 'PUT',
        'callback' => 'ssm_api_change_password',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/auth/password-reset', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_password_reset',
        'permission_callback' => '__return_true',
    ));

    register_rest_route($namespace, '/user/push-token', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_register_push_token',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Parent endpoints
    register_rest_route($namespace, '/parent/children', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_children',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/children/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_child_details',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/schedule', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_schedule',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/payments', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_payments',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/payment-history', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_payment_history',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/absences', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'ssm_api_absences',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/upcoming-sessions', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_upcoming_sessions',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/makeups', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'ssm_api_makeups',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/parent/makeup-slots', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_makeup_slots',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Instructor endpoints
    register_rest_route($namespace, '/instructor/schedule', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_instructor_schedule',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/instructor/sessions/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_session_details',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/instructor/sessions/(?P<id>\d+)/attendance', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'ssm_api_session_attendance',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/instructor/substitutions', array(
        'methods' => array('GET', 'POST'),
        'callback' => 'ssm_api_substitutions',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/instructor/substitutions/(?P<id>\d+)/take', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_take_substitution',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/instructor/salary', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_salary',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Notifications
    register_rest_route($namespace, '/notifications', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_notifications',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/notifications/unread-count', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_unread_count',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/notifications/(?P<id>\d+)/read', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_mark_notification_read',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/notifications/read-all', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_mark_all_notifications_read',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    register_rest_route($namespace, '/notifications/register', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_register_push_token',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Test
    register_rest_route($namespace, '/test', array(
        'methods' => 'GET',
        'callback' => function() {
            return array(
                'success' => true,
                'message' => 'API działa poprawnie',
                'version' => '2.0.0'
            );
        },
        'permission_callback' => '__return_true',
    ));

    // Debug/Setup endpoint - creates tables and returns status
    register_rest_route($namespace, '/setup', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_setup_database',
        'permission_callback' => '__return_true',
    ));
});

// ============ SETUP / DEBUG ============

function ssm_api_setup_database() {
    global $wpdb;
    $prefix = $wpdb->prefix;

    // Użyj głównego instalatora do utworzenia wszystkich tabel
    if (class_exists('SSM_Installer')) {
        SSM_Installer::install();
    }

    // Check if tables exist
    $tables_status = array();
    $tables_to_check = SSM_Installer::get_tables();

    foreach ($tables_to_check as $full_table) {
        $table_name = str_replace($prefix, '', $full_table);
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 0;
        $tables_status[$table_name] = array(
            'exists' => $exists,
            'rows' => intval($count)
        );
    }

    return array(
        'success' => true,
        'message' => 'Baza danych została skonfigurowana',
        'db_version' => get_option('ssm_db_version'),
        'tables' => $tables_status,
        'prefix' => $prefix
    );
}

// ============ AUTH HELPERS ============

function ssm_api_check_auth($request) {
    $auth_header = $request->get_header('Authorization');
    if (empty($auth_header) || strpos($auth_header, 'Bearer ') !== 0) {
        return false;
    }
    $token = substr($auth_header, 7);
    return ssm_api_validate_token($token) !== false;
}

function ssm_api_generate_token($user_id) {
    $token = wp_generate_password(64, false);
    $expiry = time() + (30 * DAY_IN_SECONDS);
    update_user_meta($user_id, 'ssm_api_token', $token);
    update_user_meta($user_id, 'ssm_api_token_expiry', $expiry);
    return $token;
}

function ssm_api_validate_token($token) {
    global $wpdb;
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ssm_api_token' AND meta_value = %s",
        $token
    ));
    if (!$user_id) return false;
    $expiry = get_user_meta($user_id, 'ssm_api_token_expiry', true);
    if ($expiry < time()) {
        delete_user_meta($user_id, 'ssm_api_token');
        delete_user_meta($user_id, 'ssm_api_token_expiry');
        return false;
    }
    return $user_id;
}

function ssm_api_get_user_id($request) {
    $auth_header = $request->get_header('Authorization');
    $token = substr($auth_header, 7);
    return ssm_api_validate_token($token);
}

// ============ AUTH ENDPOINTS ============

// Helper function to determine user roles for the mobile app
function ssm_api_get_user_roles($user_id, $wp_roles) {
    $roles = array();

    // Check if user is an instructor
    $is_instructor = in_array('administrator', $wp_roles) ||
                     in_array('ssm_instructor', $wp_roles) ||
                     in_array('instructor', $wp_roles);

    // Check if user is a parent (has ssm_parent role or has children associated)
    $is_parent = in_array('ssm_parent', $wp_roles) ||
                 in_array('parent', $wp_roles) ||
                 in_array('subscriber', $wp_roles) ||
                 in_array('customer', $wp_roles);

    // Also check if user has instructor AND parent meta flags
    $has_instructor_flag = get_user_meta($user_id, 'ssm_is_instructor', true);
    $has_parent_flag = get_user_meta($user_id, 'ssm_is_parent', true);

    if ($has_instructor_flag) $is_instructor = true;
    if ($has_parent_flag) $is_parent = true;

    // For demo purposes: administrators can be both instructor and parent
    if (in_array('administrator', $wp_roles)) {
        $is_parent = true; // Allow admins to test both views
    }

    if ($is_instructor) $roles[] = 'instructor';
    if ($is_parent) $roles[] = 'parent';

    // Default to parent if no roles detected
    if (empty($roles)) {
        $roles[] = 'parent';
    }

    return $roles;
}

function ssm_api_login($request) {
    $params = $request->get_json_params();
    $username = sanitize_text_field($params['username'] ?? $params['email'] ?? '');
    $password = $params['password'] ?? '';

    if (empty($username) || empty($password)) {
        return new WP_REST_Response(array('message' => 'Podaj login i hasło'), 400);
    }

    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
        return new WP_REST_Response(array('message' => 'Nieprawidłowy login lub hasło'), 401);
    }

    $token = ssm_api_generate_token($user->ID);
    $roles = ssm_api_get_user_roles($user->ID, $user->roles);
    // For multi-role users, default to 'parent'. For single-role, use their role.
    $primary_type = count($roles) > 1 ? 'parent' : $roles[0];

    return array(
        'token' => $token,
        'tokens' => array(
            'access_token' => $token,
            'refresh_token' => $token
        ),
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'type' => $primary_type,
            'roles' => $roles
        )
    );
}

function ssm_api_logout($request) {
    $user_id = ssm_api_get_user_id($request);
    delete_user_meta($user_id, 'ssm_api_token');
    delete_user_meta($user_id, 'ssm_api_token_expiry');
    return array('success' => true);
}

function ssm_api_get_me($request) {
    $user_id = ssm_api_get_user_id($request);
    $user = get_userdata($user_id);

    if (!$user) {
        return new WP_REST_Response(array('message' => 'Użytkownik nie znaleziony'), 404);
    }

    $roles = ssm_api_get_user_roles($user_id, (array)$user->roles);
    // For multi-role users, default to 'parent'. For single-role, use their role.
    $primary_type = count($roles) > 1 ? 'parent' : $roles[0];

    return array(
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'display_name' => $user->display_name,
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'phone' => get_user_meta($user_id, 'phone', true),
        'type' => $primary_type,
        'roles' => $roles
    );
}

function ssm_api_update_profile($request) {
    $user_id = ssm_api_get_user_id($request);
    $params = $request->get_json_params();

    // Update allowed fields
    $allowed_fields = array('first_name', 'last_name', 'phone', 'address', 'bio');

    foreach ($allowed_fields as $field) {
        if (isset($params[$field])) {
            update_user_meta($user_id, $field, sanitize_text_field($params[$field]));
        }
    }

    // Update display name if first_name or last_name changed
    if (isset($params['first_name']) || isset($params['last_name'])) {
        $first_name = isset($params['first_name']) ? $params['first_name'] : get_user_meta($user_id, 'first_name', true);
        $last_name = isset($params['last_name']) ? $params['last_name'] : get_user_meta($user_id, 'last_name', true);
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => trim($first_name . ' ' . $last_name)
        ));
    }

    return array(
        'success' => true,
        'message' => 'Profil został zaktualizowany'
    );
}

function ssm_api_change_password($request) {
    $user_id = ssm_api_get_user_id($request);
    $params = $request->get_json_params();

    $current_password = $params['current_password'] ?? '';
    $new_password = $params['new_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        return new WP_REST_Response(array('message' => 'Podaj aktualne i nowe hasło'), 400);
    }

    if (strlen($new_password) < 6) {
        return new WP_REST_Response(array('message' => 'Nowe hasło musi mieć minimum 6 znaków'), 400);
    }

    $user = get_userdata($user_id);
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        return new WP_REST_Response(array('message' => 'Nieprawidłowe aktualne hasło'), 401);
    }

    wp_set_password($new_password, $user_id);

    // Re-generate token after password change
    $token = ssm_api_generate_token($user_id);

    return array(
        'success' => true,
        'message' => 'Hasło zostało zmienione',
        'token' => $token
    );
}

function ssm_api_password_reset($request) {
    $params = $request->get_json_params();
    $email = sanitize_email($params['email'] ?? '');

    if (empty($email) || !is_email($email)) {
        return new WP_REST_Response(array('message' => 'Podaj prawidłowy adres email'), 400);
    }

    $user = get_user_by('email', $email);
    if (!$user) {
        // Return success even if user not found (security: don't reveal if email exists)
        return array(
            'success' => true,
            'message' => 'Jeśli konto istnieje, wysłaliśmy instrukcje resetowania hasła'
        );
    }

    // Generate reset key
    $reset_key = get_password_reset_key($user);
    if (is_wp_error($reset_key)) {
        return new WP_REST_Response(array('message' => 'Nie udało się wygenerować klucza resetowania'), 500);
    }

    // Build reset link (WordPress admin reset page)
    $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');

    // Send email
    $subject = 'Resetowanie hasła - Szkółka Pływania';
    $message = "Cześć {$user->display_name},\n\n";
    $message .= "Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.\n\n";
    $message .= "Aby zresetować hasło, kliknij poniższy link:\n";
    $message .= "$reset_link\n\n";
    $message .= "Jeśli nie prosiłeś o reset hasła, zignoruj tę wiadomość.\n\n";
    $message .= "Pozdrawiamy,\nZespół Szkółki Pływania";

    $sent = wp_mail($email, $subject, $message);

    return array(
        'success' => true,
        'message' => 'Jeśli konto istnieje, wysłaliśmy instrukcje resetowania hasła'
    );
}

// ============ PARENT ENDPOINTS ============

function ssm_api_get_children($request) {
    $user_id = ssm_api_get_user_id($request);

    // Zwróć przykładowe dane testowe z poprawnymi nazwami pól
    return array(
        array(
            'id' => 1,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birth_date' => '2018-05-15',
            'swimming_level' => 'Delfinek',
            'active_courses' => 1,
            'total_points' => 150,
            'achievements_count' => 3,
            'medical_notes' => '',
            'next_session' => array(
                'date' => date('Y-m-d', strtotime('next monday')),
                'time' => '16:00',
                'class_name' => 'Kurs pływania - poziom średni'
            )
        ),
        array(
            'id' => 2,
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'birth_date' => '2020-03-22',
            'swimming_level' => 'Żółwik',
            'active_courses' => 1,
            'total_points' => 45,
            'achievements_count' => 1,
            'medical_notes' => '',
            'next_session' => array(
                'date' => date('Y-m-d', strtotime('next wednesday')),
                'time' => '17:00',
                'class_name' => 'Kurs pływania - początkujący'
            )
        )
    );
}

function ssm_api_get_child_details($request) {
    $child_id = intval($request->get_param('id'));

    // Różne dane dla różnych dzieci
    $children_data = array(
        1 => array(
            'id' => 1,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birth_date' => '2018-05-15',
            'swimming_level' => 'Delfinek',
            'total_points' => 150,
            'medical_notes' => '',
            'courses' => array(
                array(
                    'id' => 1,
                    'name' => 'Kurs pływania - poziom średni',
                    'instructor_name' => 'Anna Nowak',
                    'day_of_week' => 'monday',
                    'time_start' => '16:00:00',
                    'time_end' => '16:45:00',
                    'facility_name' => 'Basen Główny',
                    'sessions_remaining' => 14,
                    'sessions_total' => 40
                )
            ),
            'achievements' => array(
                array(
                    'id' => 1,
                    'name' => 'Pierwsza długość',
                    'description' => 'Przepłynięcie pierwszej długości basenu',
                    'icon' => '🏊',
                    'earned_at' => '2025-10-15',
                    'points' => 50
                ),
                array(
                    'id' => 2,
                    'name' => 'Nurek',
                    'description' => 'Nurkowanie na głębokość 2m',
                    'icon' => '🤿',
                    'earned_at' => '2025-11-20',
                    'points' => 75
                ),
                array(
                    'id' => 3,
                    'name' => 'Regularny',
                    'description' => '10 zajęć pod rząd bez nieobecności',
                    'icon' => '⭐',
                    'earned_at' => '2025-12-01',
                    'points' => 25
                )
            ),
            'recent_attendance' => array(
                array(
                    'id' => 1,
                    'session_date' => date('Y-m-d', strtotime('-7 days')),
                    'status' => 'present',
                    'class_name' => 'Kurs pływania - poziom średni'
                ),
                array(
                    'id' => 2,
                    'session_date' => date('Y-m-d', strtotime('-14 days')),
                    'status' => 'present',
                    'class_name' => 'Kurs pływania - poziom średni'
                ),
                array(
                    'id' => 3,
                    'session_date' => date('Y-m-d', strtotime('-21 days')),
                    'status' => 'absent',
                    'class_name' => 'Kurs pływania - poziom średni'
                ),
                array(
                    'id' => 4,
                    'session_date' => date('Y-m-d', strtotime('-28 days')),
                    'status' => 'present',
                    'class_name' => 'Kurs pływania - poziom średni'
                )
            )
        ),
        2 => array(
            'id' => 2,
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'birth_date' => '2020-03-22',
            'swimming_level' => 'Żółwik',
            'total_points' => 45,
            'medical_notes' => '',
            'courses' => array(
                array(
                    'id' => 2,
                    'name' => 'Kurs pływania - początkujący',
                    'instructor_name' => 'Piotr Wiśniewski',
                    'day_of_week' => 'wednesday',
                    'time_start' => '17:00:00',
                    'time_end' => '17:45:00',
                    'facility_name' => 'Basen Mały',
                    'sessions_remaining' => 28,
                    'sessions_total' => 40
                )
            ),
            'achievements' => array(
                array(
                    'id' => 4,
                    'name' => 'Pierwszy skok',
                    'description' => 'Pierwszy skok do wody z brzegu basenu',
                    'icon' => '🌊',
                    'earned_at' => '2026-01-10',
                    'points' => 45
                )
            ),
            'recent_attendance' => array(
                array(
                    'id' => 5,
                    'session_date' => date('Y-m-d', strtotime('-5 days')),
                    'status' => 'present',
                    'class_name' => 'Kurs pływania - początkujący'
                ),
                array(
                    'id' => 6,
                    'session_date' => date('Y-m-d', strtotime('-12 days')),
                    'status' => 'late',
                    'class_name' => 'Kurs pływania - początkujący'
                ),
                array(
                    'id' => 7,
                    'session_date' => date('Y-m-d', strtotime('-19 days')),
                    'status' => 'present',
                    'class_name' => 'Kurs pływania - początkujący'
                )
            )
        )
    );

    // Zwróć dane dla wybranego dziecka lub domyślne dla id=1
    return isset($children_data[$child_id]) ? $children_data[$child_id] : $children_data[1];
}

function ssm_api_get_schedule($request) {
    $date_from = $request->get_param('date_from') ?? date('Y-m-d');

    return array(
        array(
            'id' => 1,
            'session_date' => date('Y-m-d', strtotime('next monday')),
            'time_start' => '16:00',
            'time_end' => '16:45',
            'facility_name' => 'Basen Główny',
            'instructor_name' => 'Anna Nowak',
            'child_id' => 1,
            'child_first_name' => 'Jan',
            'class_name' => 'Kurs pływania - poziom średni',
            'status' => 'scheduled'
        ),
        array(
            'id' => 2,
            'session_date' => date('Y-m-d', strtotime('next wednesday')),
            'time_start' => '17:00',
            'time_end' => '17:45',
            'facility_name' => 'Basen Mały',
            'instructor_name' => 'Piotr Wiśniewski',
            'child_id' => 2,
            'child_first_name' => 'Anna',
            'class_name' => 'Kurs pływania - początkujący',
            'status' => 'scheduled'
        )
    );
}

function ssm_api_get_payments($request) {
    return array(
        array(
            'id' => 1,
            'title' => 'Kurs pływania - luty 2026',
            'description' => 'Opłata miesięczna za kurs',
            'total_amount' => 350.00,
            'paid_amount' => 0,
            'remaining_amount' => 350.00,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'pending',
            'child_name' => 'Jan Kowalski',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ),
        array(
            'id' => 2,
            'title' => 'Kurs pływania - styczeń 2026',
            'description' => 'Opłata miesięczna za kurs',
            'total_amount' => 280.00,
            'paid_amount' => 280.00,
            'remaining_amount' => 0,
            'due_date' => date('Y-m-d', strtotime('-23 days')),
            'status' => 'paid',
            'child_name' => 'Jan Kowalski',
            'created_at' => date('Y-m-d H:i:s', strtotime('-35 days'))
        )
    );
}

function ssm_api_get_payment_history($request) {
    return array(
        array(
            'id' => 1,
            'amount' => 280.00,
            'payment_date' => date('Y-m-d\TH:i:s', strtotime('-25 days')),
            'payment_method' => 'Przelew',
            'invoice_title' => 'Kurs pływania - styczeń 2026'
        ),
        array(
            'id' => 2,
            'amount' => 350.00,
            'payment_date' => date('Y-m-d\TH:i:s', strtotime('-55 days')),
            'payment_method' => 'Karta',
            'invoice_title' => 'Kurs pływania - grudzień 2025'
        ),
        array(
            'id' => 3,
            'amount' => 350.00,
            'payment_date' => date('Y-m-d\TH:i:s', strtotime('-85 days')),
            'payment_method' => 'Przelew',
            'invoice_title' => 'Kurs pływania - listopad 2025'
        )
    );
}

function ssm_api_absences($request) {
    global $wpdb;
    $table_absences = $wpdb->prefix . 'ssm_absences';

    $user_id = ssm_api_get_user_id($request);

    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        $session_id = intval($params['session_id'] ?? 0);
        $child_id = intval($params['child_id'] ?? 1); // Default to 1 for testing
        $reason = sanitize_text_field($params['reason'] ?? '');

        if (!$session_id) {
            return new WP_REST_Response(array('message' => 'Brak ID sesji'), 400);
        }

        // Check if absence already reported
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_absences WHERE session_id = %d AND child_id = %d",
            $session_id, $child_id
        ));

        if ($existing) {
            return new WP_REST_Response(array('message' => 'Nieobecność już została zgłoszona'), 400);
        }

        // Insert absence with minimal required fields
        $result = $wpdb->insert($table_absences, array(
            'enrollment_id' => 1, // Default for testing
            'session_id' => $session_id,
            'child_id' => $child_id,
            'reported_at' => current_time('mysql'),
            'reason' => $reason,
            'status' => 'reported',
            'can_makeup' => 1
        ));

        if ($result === false) {
            // Log error for debugging
            error_log('SSM API: Failed to insert absence. Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array(
                'message' => 'Błąd zapisu do bazy danych',
                'debug' => $wpdb->last_error
            ), 500);
        }

        return array(
            'success' => true,
            'message' => 'Nieobecność została zgłoszona',
            'absence_id' => $wpdb->insert_id
        );
    }

    // GET - return absences from database or sample data
    $absences = $wpdb->get_results(
        "SELECT * FROM $table_absences ORDER BY reported_at DESC LIMIT 20"
    );

    if (!empty($absences)) {
        $result = array();
        foreach ($absences as $absence) {
            $result[] = array(
                'id' => $absence->id,
                'child_id' => $absence->child_id,
                'child_name' => 'Dziecko #' . $absence->child_id,
                'session_id' => $absence->session_id,
                'session_date' => date('Y-m-d'),
                'time_start' => '16:00',
                'class_name' => 'Kurs pływania',
                'status' => $absence->status,
                'reason' => $absence->reason,
                'reported_at' => $absence->reported_at
            );
        }
        return $result;
    }

    // Return sample data if no records in DB
    return array(
        array(
            'id' => 1,
            'child_id' => 1,
            'child_name' => 'Jan Kowalski',
            'session_id' => 101,
            'session_date' => date('Y-m-d', strtotime('-3 days')),
            'time_start' => '16:00',
            'class_name' => 'Kurs pływania - poziom średni',
            'status' => 'confirmed',
            'reason' => 'Choroba',
            'reported_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
        )
    );
}

function ssm_api_get_upcoming_sessions($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);

    // Get client_id for current user
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_clients WHERE user_id = %d",
        $user_id
    ));

    if (!$client) {
        // No client record - return empty array
        return array();
    }

    // Get upcoming sessions for this parent's children
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT
            s.id,
            s.session_date,
            s.time_start,
            s.time_end,
            c.name as class_name,
            f.name as facility_name,
            ch.id as child_id,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name
        FROM {$wpdb->prefix}ssm_sessions s
        JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
        JOIN {$wpdb->prefix}ssm_enrollments e ON e.class_id = c.id AND e.status = 'active'
        JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
        JOIN {$wpdb->prefix}ssm_client_children cc ON cc.child_id = ch.id AND cc.client_id = %d
        WHERE s.session_date >= CURDATE()
        AND s.status = 'scheduled'
        ORDER BY s.session_date, s.time_start
        LIMIT 20",
        $client->id
    ));

    $result = array();
    foreach ($sessions as $session) {
        // Check if absence can be reported (not already reported)
        $existing_absence = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_absences
             WHERE session_id = %d AND child_id = %d",
            $session->id, $session->child_id
        ));

        $result[] = array(
            'id' => intval($session->id),
            'session_date' => $session->session_date,
            'time_start' => substr($session->time_start, 0, 5),
            'time_end' => substr($session->time_end ?: '', 0, 5),
            'class_name' => $session->class_name,
            'facility_name' => $session->facility_name ?: 'Basen',
            'child_id' => intval($session->child_id),
            'child_name' => $session->child_name,
            'can_report_absence' => empty($existing_absence)
        );
    }

    return $result;
}

function ssm_api_makeups($request) {
    global $wpdb;
    $table_absences = $wpdb->prefix . 'ssm_absences';

    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        $absence_id = intval($params['absence_id'] ?? 0);
        $slot_id = intval($params['slot_id'] ?? $params['session_id'] ?? 0);

        if (!$absence_id || !$slot_id) {
            return new WP_REST_Response(array('message' => 'Brak wymaganych parametrów'), 400);
        }

        // Update absence with makeup info
        $result = $wpdb->update(
            $table_absences,
            array(
                'status' => 'makeup_scheduled',
                'makeup_session_id' => $slot_id
            ),
            array('id' => $absence_id)
        );

        if ($result === false) {
            error_log('SSM API: Failed to update makeup. Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array(
                'message' => 'Błąd zapisu do bazy danych',
                'debug' => $wpdb->last_error
            ), 500);
        }

        return array(
            'success' => true,
            'message' => 'Odrabianie zostało zaplanowane',
            'absence_id' => $absence_id,
            'makeup_session_id' => $slot_id
        );
    }

    return array(
        'available_slots' => array(),
        'booked_makeups' => array()
    );
}

function ssm_api_get_makeup_slots($request) {
    return array(
        array(
            'id' => 1,
            'date' => date('Y-m-d', strtotime('+3 days')),
            'time_start' => '15:00',
            'time_end' => '15:45',
            'class_name' => 'Kurs pływania - odrabianie',
            'facility_name' => 'Basen Główny',
            'available_spots' => 3
        ),
        array(
            'id' => 2,
            'date' => date('Y-m-d', strtotime('+5 days')),
            'time_start' => '14:00',
            'time_end' => '14:45',
            'class_name' => 'Kurs pływania - odrabianie',
            'facility_name' => 'Basen Główny',
            'available_spots' => 2
        ),
        array(
            'id' => 3,
            'date' => date('Y-m-d', strtotime('+7 days')),
            'time_start' => '16:00',
            'time_end' => '16:45',
            'class_name' => 'Kurs pływania - odrabianie',
            'facility_name' => 'Basen Mały',
            'available_spots' => 4
        )
    );
}

function ssm_api_get_makeups_legacy($request) {
    return array(
        'available_slots' => array(
            array(
                'id' => 1,
                'date' => date('Y-m-d', strtotime('+3 days')),
                'start_time' => '15:00',
                'end_time' => '15:45',
                'pool_name' => 'Basen Główny',
                'spots_left' => 2
            )
        ),
        'booked_makeups' => array()
    );
}

function ssm_api_get_notifications($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);
    $table = $wpdb->prefix . 'ssm_notifications';

    // Get role from request parameter (sent by mobile app based on active view)
    $requested_role = $request->get_param('role');

    // Determine recipient type and ID
    $recipient_info = ssm_api_get_recipient_info($user_id);

    // Determine active role for notification filtering
    // For multi-role users, MUST use requested_role from mobile app
    // For single-role users, use detected type
    $active_role = $recipient_info['type'];

    if ($recipient_info['has_multiple_roles']) {
        // Multi-role user - requested_role is required
        if ($requested_role === 'instructor' && $recipient_info['instructor_id']) {
            $active_role = 'instructor';
        } elseif ($requested_role === 'parent' && $recipient_info['client_id']) {
            $active_role = 'parent';
        }
        // If no valid requested_role, active_role stays 'user' (generic notifications only)
    } else {
        // Single-role user - use detected type, but allow override if valid
        if ($requested_role === 'instructor' && $recipient_info['instructor_id']) {
            $active_role = 'instructor';
        } elseif ($requested_role === 'parent' && $recipient_info['client_id']) {
            $active_role = 'parent';
        }
    }

    // DEBUG: Add to response
    $debug_info = array(
        'user_id' => $user_id,
        'requested_role' => $requested_role,
        'detected_type' => $recipient_info['type'],
        'has_multiple_roles' => $recipient_info['has_multiple_roles'],
        'active_role' => $active_role,
        'instructor_id' => $recipient_info['instructor_id'],
        'client_id' => $recipient_info['client_id']
    );

    // Build query based on active role
    if ($active_role === 'instructor') {
        // For instructors: check instructor notifications + user notifications
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE (
                 (recipient_type = 'instructor' AND recipient_id = %d)
                 OR (recipient_type = 'user' AND recipient_id = %d)
             )
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC
             LIMIT 50",
            $recipient_info['instructor_id'],
            $user_id
        ));
    } elseif ($active_role === 'parent') {
        // For parents: check parent/client notifications + user notifications
        // Check both client_id (if found) and user_id (as fallback)
        if ($recipient_info['client_id']) {
            $notifications = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table
                 WHERE (
                     (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type = 'user' AND recipient_id = %d)
                 )
                 AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY created_at DESC
                 LIMIT 50",
                $recipient_info['client_id'],
                $user_id,
                $user_id
            ));
        } else {
            // No client_id found - search by user_id only
            $notifications = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table
                 WHERE (
                     (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type = 'user' AND recipient_id = %d)
                 )
                 AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY created_at DESC
                 LIMIT 50",
                $user_id,
                $user_id
            ));
        }
    } else {
        // For regular users: check only user notifications
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE recipient_type = 'user' AND recipient_id = %d
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC
             LIMIT 50",
            $user_id
        ));
    }

    $result = array();
    foreach ($notifications as $notif) {
        // Calculate time ago
        $created = strtotime($notif->created_at);
        $diff = time() - $created;
        if ($diff < 3600) {
            $time_ago = floor($diff / 60) . ' min temu';
        } elseif ($diff < 86400) {
            $time_ago = floor($diff / 3600) . ' godz. temu';
        } else {
            $time_ago = floor($diff / 86400) . ' dni temu';
        }

        // Determine icon and color based on type
        $type_config = array(
            'reminder' => array('icon' => 'notifications', 'color' => '#3b82f6'),
            'absence' => array('icon' => 'calendar-outline', 'color' => '#ef4444'),
            'payment' => array('icon' => 'card-outline', 'color' => '#22c55e'),
            'achievement' => array('icon' => 'trophy-outline', 'color' => '#f59e0b'),
            'info' => array('icon' => 'information-circle-outline', 'color' => '#6b7280'),
        );
        $config = $type_config[$notif->type] ?? $type_config['info'];

        $result[] = array(
            'id' => intval($notif->id),
            'title' => $notif->title,
            'message' => $notif->message,
            'created_at' => $notif->created_at,
            'is_read' => (bool) $notif->is_read,
            'type' => $notif->type,
            'icon' => $config['icon'],
            'color' => $config['color'],
            'time_ago' => $time_ago
        );
    }

    // DEBUG: Return with debug info
    return array(
        '_debug' => $debug_info,
        'notifications' => $result
    );
}

function ssm_api_get_unread_count($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);
    $table = $wpdb->prefix . 'ssm_notifications';

    // Get role from request parameter
    $requested_role = $request->get_param('role');

    $recipient_info = ssm_api_get_recipient_info($user_id);

    // Use requested role if provided and valid
    $active_role = $recipient_info['type'];
    if ($requested_role === 'instructor' && $recipient_info['instructor_id']) {
        $active_role = 'instructor';
    } elseif ($requested_role === 'parent' && $recipient_info['client_id']) {
        $active_role = 'parent';
    }

    // Build query based on active role
    if ($active_role === 'instructor') {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE (
                 (recipient_type = 'instructor' AND recipient_id = %d)
                 OR (recipient_type = 'user' AND recipient_id = %d)
             )
             AND is_read = 0
             AND (expires_at IS NULL OR expires_at > NOW())",
            $recipient_info['instructor_id'],
            $user_id
        ));
    } elseif ($active_role === 'parent') {
        if ($recipient_info['client_id']) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                 WHERE (
                     (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type = 'user' AND recipient_id = %d)
                 )
                 AND is_read = 0
                 AND (expires_at IS NULL OR expires_at > NOW())",
                $recipient_info['client_id'],
                $user_id,
                $user_id
            ));
        } else {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                 WHERE (
                     (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type = 'user' AND recipient_id = %d)
                 )
                 AND is_read = 0
                 AND (expires_at IS NULL OR expires_at > NOW())",
                $user_id,
                $user_id
            ));
        }
    } else {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE recipient_type = 'user' AND recipient_id = %d
             AND is_read = 0
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id
        ));
    }

    return array(
        'unread_count' => intval($count)
    );
}

function ssm_api_mark_notification_read($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);
    $notification_id = intval($request->get_param('id'));
    $table = $wpdb->prefix . 'ssm_notifications';

    // Get role from request body (POST data)
    $params = $request->get_json_params();
    $requested_role = isset($params['role']) ? $params['role'] : null;

    $recipient_info = ssm_api_get_recipient_info($user_id);

    // Use requested role if provided and valid
    $active_role = $recipient_info['type'];
    if ($requested_role === 'instructor' && $recipient_info['instructor_id']) {
        $active_role = 'instructor';
    } elseif ($requested_role === 'parent' && $recipient_info['client_id']) {
        $active_role = 'parent';
    }

    // Get the appropriate recipient_id based on active role
    $recipient_id = $user_id;
    if ($active_role === 'instructor' && $recipient_info['instructor_id']) {
        $recipient_id = $recipient_info['instructor_id'];
    } elseif ($active_role === 'parent' && $recipient_info['client_id']) {
        $recipient_id = $recipient_info['client_id'];
    }

    // Update notification
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE $table SET is_read = 1, read_at = %s
         WHERE id = %d
         AND (
             (recipient_type = %s AND recipient_id = %d)
             OR (recipient_type = 'user' AND recipient_id = %d)
         )",
        current_time('mysql'),
        $notification_id,
        $active_role,
        $recipient_id,
        $user_id
    ));

    return array(
        'success' => $result !== false,
        'notification_id' => $notification_id
    );
}

function ssm_api_mark_all_notifications_read($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);
    $table = $wpdb->prefix . 'ssm_notifications';
    $now = current_time('mysql');

    // Get role from request body (POST data)
    $params = $request->get_json_params();
    $requested_role = isset($params['role']) ? $params['role'] : null;

    $recipient_info = ssm_api_get_recipient_info($user_id);

    // Use requested role if provided and valid
    $active_role = $recipient_info['type'];
    if ($requested_role === 'instructor' && $recipient_info['instructor_id']) {
        $active_role = 'instructor';
    } elseif ($requested_role === 'parent' && $recipient_info['client_id']) {
        $active_role = 'parent';
    }

    // Build query based on active role
    if ($active_role === 'instructor') {
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET is_read = 1, read_at = %s
             WHERE is_read = 0
             AND (
                 (recipient_type = 'instructor' AND recipient_id = %d)
                 OR (recipient_type = 'user' AND recipient_id = %d)
             )",
            $now,
            $recipient_info['instructor_id'],
            $user_id
        ));
    } elseif ($active_role === 'parent') {
        if ($recipient_info['client_id']) {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table SET is_read = 1, read_at = %s
                 WHERE is_read = 0
                 AND (
                     (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type = 'user' AND recipient_id = %d)
                 )",
                $now,
                $recipient_info['client_id'],
                $user_id,
                $user_id
            ));
        } else {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table SET is_read = 1, read_at = %s
                 WHERE is_read = 0
                 AND (
                     (recipient_type IN ('parent', 'client') AND recipient_id = %d)
                     OR (recipient_type = 'user' AND recipient_id = %d)
                 )",
                $now,
                $user_id,
                $user_id
            ));
        }
    } else {
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET is_read = 1, read_at = %s
             WHERE is_read = 0
             AND recipient_type = 'user' AND recipient_id = %d",
            $now,
            $user_id
        ));
    }

    return array(
        'success' => $result !== false,
        'marked_count' => $result ?: 0
    );
}

// Helper function to get recipient info for notifications
// Returns all matching identities (user can be both instructor and parent)
function ssm_api_get_recipient_info($user_id) {
    global $wpdb;

    $result = array(
        'type' => 'user',  // Default type - will remain 'user' if multiple roles
        'id' => $user_id,
        'user_id' => $user_id,
        'instructor_id' => null,
        'client_id' => null,
        'has_multiple_roles' => false
    );

    // Get WordPress user
    $user = get_userdata($user_id);
    if (!$user) {
        return $result;
    }
    $user_email = $user->user_email;

    // Check if user is instructor by WordPress role
    $is_instructor_role = in_array('administrator', (array) $user->roles) || in_array('ssm_instructor', (array) $user->roles);

    if ($is_instructor_role) {
        // User has instructor role - find their instructor record
        $instructor = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_instructors
             WHERE user_id = %d OR email = %s",
            $user_id,
            $user_email
        ));

        if ($instructor) {
            $result['instructor_id'] = $instructor->id;
        } else {
            // Has role but no record - use user_id as instructor_id
            $result['instructor_id'] = $user_id;
        }
    }

    // Check if user is parent/client (by user_id first, then email)
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_clients WHERE user_id = %d OR email = %s",
        $user_id,
        $user_email
    ));

    if ($client) {
        $result['client_id'] = $client->id;
    }

    // Determine type based on what roles user has
    $has_instructor = !empty($result['instructor_id']);
    $has_parent = !empty($result['client_id']);

    if ($has_instructor && $has_parent) {
        // User has both roles - keep type as 'user', let requested role decide
        $result['type'] = 'user';
        $result['has_multiple_roles'] = true;
    } elseif ($has_instructor) {
        $result['type'] = 'instructor';
        $result['id'] = $result['instructor_id'];
    } elseif ($has_parent) {
        $result['type'] = 'parent';
        $result['id'] = $result['client_id'];
    }
    // else: type remains 'user'

    return $result;
}

function ssm_api_register_push_token($request) {
    $user_id = ssm_api_get_user_id($request);
    $params = $request->get_json_params();
    $token = sanitize_text_field($params['token'] ?? '');
    $platform = sanitize_text_field($params['platform'] ?? 'android');

    if (!empty($token)) {
        update_user_meta($user_id, 'ssm_push_token', $token);
        update_user_meta($user_id, 'ssm_push_platform', $platform);
    }

    return array('success' => true);
}

// ============ INSTRUCTOR ENDPOINTS ============

function ssm_api_get_instructor_schedule($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);

    $date_from = $request->get_param('date_from') ?: date('Y-m-d');
    $date_to = $request->get_param('date_to') ?: date('Y-m-d', strtotime('+14 days'));

    // Get instructor_id for current user
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d",
        $user_id
    ));

    if (!$instructor) {
        // No instructor record - return empty array
        return array();
    }

    // Get sessions for this instructor
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT
            s.id,
            s.session_date,
            s.time_start,
            s.time_end,
            s.status,
            c.name as class_name,
            c.level,
            c.max_participants,
            f.name as facility_name,
            f.address as facility_address,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments e
             WHERE e.class_id = c.id AND e.status = 'active') as enrolled_count,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance a
             WHERE a.session_id = s.id) as attendance_marked
        FROM {$wpdb->prefix}ssm_sessions s
        JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
        WHERE s.instructor_id = %d
        AND s.session_date BETWEEN %s AND %s
        ORDER BY s.session_date, s.time_start",
        $instructor->id,
        $date_from,
        $date_to
    ));

    $result = array();
    foreach ($sessions as $session) {
        $result[] = array(
            'id' => intval($session->id),
            'session_date' => $session->session_date,
            'time_start' => $session->time_start,
            'time_end' => $session->time_end,
            'class_name' => $session->class_name,
            'level' => $session->level ?: 'Początkujący',
            'facility_name' => $session->facility_name ?: 'Basen',
            'facility_address' => $session->facility_address ?: '',
            'enrolled_count' => intval($session->enrolled_count),
            'max_participants' => intval($session->max_participants) ?: 10,
            'attendance_marked' => intval($session->attendance_marked) > 0 ? 1 : 0,
            'status' => $session->status ?: 'scheduled'
        );
    }

    return $result;
}

function ssm_api_get_session_details($request) {
    $session_id = $request->get_param('id');

    return array(
        'id' => $session_id,
        'session_date' => date('Y-m-d'),
        'time_start' => '09:00:00',
        'time_end' => '09:45:00',
        'class_name' => 'Kurs pływania - początkujący',
        'level' => 'Początkujący',
        'facility_name' => 'Basen Główny',
        'facility_address' => 'ul. Sportowa 15',
        'max_participants' => 10,
        'description' => 'Zajęcia dla początkujących - nauka podstaw pływania.',
        'participants' => array(
            array(
                'enrollment_id' => 1,
                'child_id' => 1,
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'status' => 'unmarked',
                'notes' => '',
                'swimming_level' => 'Początkujący',
                'medical_notes' => ''
            ),
            array(
                'enrollment_id' => 2,
                'child_id' => 2,
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'status' => 'unmarked',
                'notes' => '',
                'swimming_level' => 'Początkujący',
                'medical_notes' => 'Alergia na chlor - wymaga okularów'
            ),
            array(
                'enrollment_id' => 3,
                'child_id' => 3,
                'first_name' => 'Piotr',
                'last_name' => 'Wiśniewski',
                'status' => 'unmarked',
                'notes' => '',
                'swimming_level' => 'Początkujący',
                'medical_notes' => ''
            ),
            array(
                'enrollment_id' => 4,
                'child_id' => 4,
                'first_name' => 'Maria',
                'last_name' => 'Dąbrowska',
                'status' => 'unmarked',
                'notes' => '',
                'swimming_level' => 'Początkujący',
                'medical_notes' => ''
            )
        )
    );
}

function ssm_api_session_attendance($request) {
    global $wpdb;
    $table_attendance = $wpdb->prefix . 'ssm_attendance';
    $session_id = intval($request->get_param('id'));

    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        $attendance_data = $params['attendance'] ?? array();

        if (empty($attendance_data)) {
            return new WP_REST_Response(array('message' => 'Brak danych obecności'), 400);
        }

        $saved_count = 0;
        $errors = array();

        foreach ($attendance_data as $record) {
            $child_id = intval($record['child_id'] ?? $record['enrollment_id'] ?? 0);
            $status = sanitize_text_field($record['status'] ?? 'present');
            $notes = sanitize_text_field($record['notes'] ?? '');

            if (!$child_id) continue;

            // Check if attendance record exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_attendance WHERE session_id = %d AND child_id = %d",
                $session_id, $child_id
            ));

            if ($existing) {
                // Update existing record
                $result = $wpdb->update(
                    $table_attendance,
                    array('status' => $status, 'notes' => $notes),
                    array('id' => $existing)
                );
            } else {
                // Insert new record
                $result = $wpdb->insert($table_attendance, array(
                    'session_id' => $session_id,
                    'child_id' => $child_id,
                    'status' => $status,
                    'notes' => $notes
                ));
            }

            if ($result !== false) {
                $saved_count++;
            } else {
                $errors[] = $wpdb->last_error;
            }
        }

        if ($saved_count > 0) {
            return array(
                'success' => true,
                'message' => 'Obecność została zapisana',
                'session_id' => $session_id,
                'attendance_count' => $saved_count
            );
        } else {
            error_log('SSM API: Failed to save attendance. Errors: ' . implode(', ', $errors));
            return new WP_REST_Response(array(
                'message' => 'Błąd zapisu obecności',
                'debug' => implode(', ', $errors)
            ), 500);
        }
    }

    // GET - return attendance for session from DB or sample data
    $attendance = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_attendance WHERE session_id = %d",
        $session_id
    ));

    if (!empty($attendance)) {
        $result = array();
        foreach ($attendance as $record) {
            $result[] = array(
                'enrollment_id' => $record->id,
                'child_id' => $record->child_id,
                'first_name' => 'Dziecko',
                'last_name' => '#' . $record->child_id,
                'status' => $record->status,
                'notes' => $record->notes
            );
        }
        return $result;
    }

    // Return sample data for testing
    return array(
        array(
            'enrollment_id' => 1,
            'child_id' => 1,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'status' => 'unmarked',
            'notes' => ''
        ),
        array(
            'enrollment_id' => 2,
            'child_id' => 2,
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'status' => 'unmarked',
            'notes' => ''
        )
    );
}

function ssm_api_substitutions($request) {
    global $wpdb;
    $table_unavailability = $wpdb->prefix . 'ssm_instructor_unavailability';

    $user_id = ssm_api_get_user_id($request);

    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        $session_id = intval($params['session_id'] ?? 0);
        $reason = sanitize_text_field($params['reason'] ?? '');

        if (!$session_id) {
            return new WP_REST_Response(array('message' => 'Brak ID sesji'), 400);
        }

        // Get instructor_id from authenticated user (fallback to user_id for user meta lookup)
        $ins_id = get_user_meta($user_id, 'ssm_instructor_id', true);
        if (!$ins_id) {
            // Use user_id as instructor_id if no specific instructor_id set
            $ins_id = $user_id;
        }

        // Check if substitution request already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_unavailability WHERE session_id = %d AND instructor_id = %d",
            $session_id, $ins_id
        ));

        if ($existing) {
            return new WP_REST_Response(array('message' => 'Prośba o zastępstwo już istnieje'), 400);
        }

        // Insert substitution request
        $result = $wpdb->insert($table_unavailability, array(
            'instructor_id' => $ins_id,
            'session_id' => $session_id,
            'reported_at' => current_time('mysql'),
            'reason' => $reason,
            'status' => 'pending'
        ));

        if ($result === false) {
            error_log('SSM API: Failed to insert substitution. Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array(
                'message' => 'Błąd zapisu do bazy danych',
                'debug' => $wpdb->last_error
            ), 500);
        }

        return array(
            'success' => true,
            'message' => 'Prośba o zastępstwo została wysłana',
            'substitution_id' => $wpdb->insert_id
        );
    }

    $type = $request->get_param('type') ?: 'available';

    // Get instructor_id for current user
    $current_instructor_id = get_user_meta($user_id, 'ssm_instructor_id', true);
    if (!$current_instructor_id) {
        $current_instructor_id = $user_id;
    }

    if ($type === 'available') {
        // Get pending substitutions that are not from current instructor
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_unavailability
             WHERE status = 'pending'
             AND replacement_instructor_id IS NULL
             AND instructor_id != %d
             ORDER BY reported_at DESC",
            $current_instructor_id
        ));

        $substitutions = array();
        foreach ($results as $row) {
            $substitutions[] = array(
                'id' => intval($row->id),
                'session_id' => intval($row->session_id),
                'session_date' => date('Y-m-d', strtotime('+' . ($row->id % 5 + 1) . ' days')), // Placeholder - should come from sessions table
                'time_start' => '10:00:00',
                'time_end' => '10:45:00',
                'class_name' => 'Kurs pływania',
                'facility_name' => 'Basen Główny',
                'instructor_name' => 'Instruktor #' . $row->instructor_id,
                'reason' => $row->reason ?: '',
                'created_at' => $row->reported_at
            );
        }

        // If no real data, return empty array (no mock data)
        return $substitutions;

    } elseif ($type === 'my_requests') {
        // Get substitutions requested by current instructor
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_unavailability
             WHERE instructor_id = %d
             ORDER BY reported_at DESC",
            $current_instructor_id
        ));

        $substitutions = array();
        foreach ($results as $row) {
            $replacement_name = null;
            if ($row->replacement_instructor_id) {
                $replacement_name = 'Instruktor #' . $row->replacement_instructor_id;
            }

            $substitutions[] = array(
                'id' => intval($row->id),
                'session_id' => intval($row->session_id),
                'session_date' => date('Y-m-d', strtotime('+' . ($row->id % 5 + 1) . ' days')),
                'time_start' => '10:00:00',
                'time_end' => '10:45:00',
                'class_name' => 'Kurs pływania',
                'facility_name' => 'Basen Główny',
                'reason' => $row->reason ?: '',
                'replacement_name' => $replacement_name,
                'status' => $row->status,
                'created_at' => $row->reported_at
            );
        }

        return $substitutions;

    } else { // my_taken
        // Get substitutions taken by current instructor
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_unavailability
             WHERE replacement_instructor_id = %d
             AND status = 'taken'
             ORDER BY reported_at DESC",
            $current_instructor_id
        ));

        $substitutions = array();
        foreach ($results as $row) {
            $substitutions[] = array(
                'id' => intval($row->id),
                'session_id' => intval($row->session_id),
                'session_date' => date('Y-m-d', strtotime('+' . ($row->id % 5 + 1) . ' days')),
                'time_start' => '10:00:00',
                'time_end' => '10:45:00',
                'class_name' => 'Kurs pływania',
                'facility_name' => 'Basen Główny',
                'original_instructor_name' => 'Instruktor #' . $row->instructor_id,
                'reason' => $row->reason ?: '',
                'created_at' => $row->reported_at
            );
        }

        return $substitutions;
    }
}

function ssm_api_take_substitution($request) {
    global $wpdb;
    $table_unavailability = $wpdb->prefix . 'ssm_instructor_unavailability';

    $substitution_id = intval($request->get_param('id'));
    $user_id = ssm_api_get_user_id($request);

    if (!$substitution_id) {
        return new WP_REST_Response(array('message' => 'Brak ID zastępstwa'), 400);
    }

    // Get instructor_id from authenticated user
    $replacement_id = get_user_meta($user_id, 'ssm_instructor_id', true);
    if (!$replacement_id) {
        // Use user_id as instructor_id if no specific instructor_id set
        $replacement_id = $user_id;
    }

    // Update substitution with replacement
    $result = $wpdb->update(
        $table_unavailability,
        array(
            'status' => 'taken',
            'replacement_instructor_id' => $replacement_id
        ),
        array('id' => $substitution_id)
    );

    if ($result === false) {
        error_log('SSM API: Failed to update substitution. Error: ' . $wpdb->last_error);
        return new WP_REST_Response(array(
            'message' => 'Błąd zapisu do bazy danych',
            'debug' => $wpdb->last_error
        ), 500);
    }

    return array(
        'success' => true,
        'message' => 'Zastępstwo zostało przyjęte',
        'substitution_id' => $substitution_id,
        'replacement_id' => $replacement_id
    );
}

function ssm_api_get_salary($request) {
    $month = $request->get_param('month') ?: date('n');
    $year = $request->get_param('year') ?: date('Y');

    return array(
        'month' => intval($month),
        'year' => intval($year),
        'hourly_rate' => 80,
        'total_hours' => 32.5,
        'total_salary' => 2600,
        'sessions_count' => 43,
        'sessions' => array(
            array(
                'id' => 1,
                'session_date' => date('Y-m-d', strtotime('-1 day')),
                'time_start' => '09:00:00',
                'time_end' => '09:45:00',
                'class_name' => 'Kurs pływania - początkujący',
                'duration_minutes' => 45
            ),
            array(
                'id' => 2,
                'session_date' => date('Y-m-d', strtotime('-1 day')),
                'time_start' => '10:00:00',
                'time_end' => '10:45:00',
                'class_name' => 'Kurs pływania - średniozaawansowany',
                'duration_minutes' => 45
            ),
            array(
                'id' => 3,
                'session_date' => date('Y-m-d', strtotime('-2 days')),
                'time_start' => '16:00:00',
                'time_end' => '16:45:00',
                'class_name' => 'Kurs pływania - zaawansowany',
                'duration_minutes' => 45
            ),
            array(
                'id' => 4,
                'session_date' => date('Y-m-d', strtotime('-3 days')),
                'time_start' => '09:00:00',
                'time_end' => '09:45:00',
                'class_name' => 'Kurs pływania - początkujący',
                'duration_minutes' => 45
            ),
            array(
                'id' => 5,
                'session_date' => date('Y-m-d', strtotime('-4 days')),
                'time_start' => '14:00:00',
                'time_end' => '14:45:00',
                'class_name' => 'Kurs pływania - średniozaawansowany',
                'duration_minutes' => 45
            )
        )
    );
}
