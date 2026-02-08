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

// Tymczasowo wyczyść OPcache - USUŃ PO DEBUGOWANIU
if (function_exists('opcache_reset')) {
    opcache_reset();
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

    register_rest_route($namespace, '/auth/register', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_register',
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
        'methods' => array('GET', 'POST', 'DELETE'),
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

/**
 * Get or auto-create client record for a user with ssm_parent role
 * Returns array with 'client' object and 'debug' info
 */
function ssm_api_get_or_create_client($user_id, $return_debug = false) {
    global $wpdb;

    $debug = array(
        'user_id' => $user_id,
        'db_prefix' => $wpdb->prefix
    );

    $user = get_userdata($user_id);
    if (!$user) {
        $debug['error'] = 'User not found';
        return $return_debug ? array('client' => null, 'debug' => $debug) : null;
    }

    $user_email = $user->user_email;
    $debug['user_email'] = $user_email;
    $debug['user_roles'] = (array) $user->roles;

    // Try to find existing client record by email (user_id column doesn't exist in this schema)
    $query = $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
        $user_email
    );
    $debug['client_query'] = $query;

    $client = $wpdb->get_row($query);
    $debug['client_found'] = $client ? true : false;
    $debug['sql_error'] = $wpdb->last_error;

    // Also check: how many total clients exist?
    $total_clients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_clients");
    $debug['total_clients_in_db'] = $total_clients;

    if ($client) {
        $debug['client_id'] = $client->id;
        return $return_debug ? array('client' => $client, 'debug' => $debug) : $client;
    }

    // Auto-create client record for users with ssm_parent role
    if (in_array('ssm_parent', (array) $user->roles)) {
        $wpdb->insert(
            $wpdb->prefix . 'ssm_clients',
            array(
                'email' => $user_email,
                'first_name' => $user->first_name ?: $user->display_name,
                'last_name' => $user->last_name ?: '',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );

        $client_id = $wpdb->insert_id;
        if ($client_id) {
            error_log("SSM API: Auto-created client record id=$client_id for user_id=$user_id");
            $client = (object) array('id' => $client_id);
            $debug['auto_created'] = true;
            $debug['client_id'] = $client_id;
            return $return_debug ? array('client' => $client, 'debug' => $debug) : $client;
        }
    }

    $debug['error'] = 'No client found and user is not ssm_parent';
    return $return_debug ? array('client' => null, 'debug' => $debug) : null;
}

// ============ AUTH ENDPOINTS ============

// DEBUG: Helper function with detailed debug info
function ssm_api_get_user_roles_debug($user_id, $wp_roles) {
    global $wpdb;

    $debug = array(
        'user_id' => $user_id,
        'wp_roles_raw' => $wp_roles,
        'checks' => array()
    );

    $roles = array();

    // Check if user is an instructor by WordPress role
    $is_admin = in_array('administrator', $wp_roles);
    $is_ssm_instructor = in_array('ssm_instructor', $wp_roles);
    $is_instructor_role = in_array('instructor', $wp_roles);
    $is_instructor = $is_admin || $is_ssm_instructor || $is_instructor_role;

    $debug['checks']['is_administrator'] = $is_admin;
    $debug['checks']['is_ssm_instructor'] = $is_ssm_instructor;
    $debug['checks']['is_instructor_role'] = $is_instructor_role;
    $debug['checks']['is_instructor_combined'] = $is_instructor;

    // Check if user exists in ssm_instructors table (by user_id or email)
    $user_data = get_userdata($user_id);
    $user_email = $user_data ? $user_data->user_email : '';
    $table_instructors = $wpdb->prefix . 'ssm_instructors';

    $in_instructors_table = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_instructors WHERE user_id = %d OR email = %s",
        $user_id, $user_email
    ));

    $debug['checks']['user_email'] = $user_email;
    $debug['checks']['in_instructors_table'] = (int)$in_instructors_table;

    if ($in_instructors_table > 0) {
        $is_instructor = true;
    }

    $debug['checks']['is_instructor_after_table_check'] = $is_instructor;

    // Check if user is explicitly a parent (has ssm_parent role)
    $is_ssm_parent = in_array('ssm_parent', $wp_roles);
    $is_parent_role = in_array('parent', $wp_roles);
    $is_parent = $is_ssm_parent || $is_parent_role;

    $debug['checks']['is_ssm_parent'] = $is_ssm_parent;
    $debug['checks']['is_parent_role'] = $is_parent_role;
    $debug['checks']['is_parent_from_wp_roles'] = $is_parent;

    // Also check if user has instructor AND parent meta flags
    $has_instructor_flag = get_user_meta($user_id, 'ssm_is_instructor', true);
    $has_parent_flag = get_user_meta($user_id, 'ssm_is_parent', true);

    $debug['checks']['meta_ssm_is_instructor'] = $has_instructor_flag;
    $debug['checks']['meta_ssm_is_parent'] = $has_parent_flag;

    if ($has_instructor_flag) $is_instructor = true;
    if ($has_parent_flag) $is_parent = true;

    $debug['checks']['is_instructor_after_meta'] = $is_instructor;
    $debug['checks']['is_parent_after_meta'] = $is_parent;

    // Check if user has children associated (makes them a parent)
    $table_children = $wpdb->prefix . 'ssm_client_children';
    $table_clients = $wpdb->prefix . 'ssm_clients';

    $has_children = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_children ch
         INNER JOIN $table_clients c ON ch.client_id = c.id
         WHERE c.user_id = %d",
        $user_id
    ));

    $debug['checks']['children_count'] = (int)$has_children;

    if ($has_children > 0) {
        $is_parent = true;
    }

    $debug['checks']['is_parent_after_children_check'] = $is_parent;

    // For administrators: can be both instructor and parent for testing
    if ($is_admin) {
        $is_parent = true;
        $debug['checks']['admin_forced_parent'] = true;
    }

    $debug['checks']['final_is_instructor'] = $is_instructor;
    $debug['checks']['final_is_parent'] = $is_parent;

    if ($is_instructor) $roles[] = 'instructor';
    if ($is_parent) $roles[] = 'parent';

    // Default to parent if no roles detected (for new users without specific role)
    if (empty($roles)) {
        $roles[] = 'parent';
        $debug['checks']['defaulted_to_parent'] = true;
    }

    $debug['roles'] = $roles;
    $debug['primary_type'] = count($roles) > 1 ? 'parent' : $roles[0];

    return $debug;
}

// Helper function to determine user roles for the mobile app
function ssm_api_get_user_roles($user_id, $wp_roles) {
    global $wpdb;
    $roles = array();

    // Check if user is an instructor by WordPress role
    $is_instructor = in_array('administrator', $wp_roles) ||
                     in_array('ssm_instructor', $wp_roles) ||
                     in_array('instructor', $wp_roles);

    // Check if user exists in ssm_instructors table (by user_id or email)
    $user_data = get_userdata($user_id);
    $user_email = $user_data ? $user_data->user_email : '';
    $table_instructors = $wpdb->prefix . 'ssm_instructors';

    $in_instructors_table = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_instructors WHERE user_id = %d OR email = %s",
        $user_id, $user_email
    ));

    if ($in_instructors_table > 0) {
        $is_instructor = true;
    }

    // Check if user is explicitly a parent (has ssm_parent role)
    $is_parent = in_array('ssm_parent', $wp_roles) ||
                 in_array('parent', $wp_roles);

    // Also check if user has instructor AND parent meta flags
    $has_instructor_flag = get_user_meta($user_id, 'ssm_is_instructor', true);
    $has_parent_flag = get_user_meta($user_id, 'ssm_is_parent', true);

    if ($has_instructor_flag) $is_instructor = true;
    if ($has_parent_flag) $is_parent = true;

    // Check if user has children associated (makes them a parent)
    $table_children = $wpdb->prefix . 'ssm_client_children';
    $table_clients = $wpdb->prefix . 'ssm_clients';

    $has_children = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_children ch
         INNER JOIN $table_clients c ON ch.client_id = c.id
         WHERE c.user_id = %d",
        $user_id
    ));

    if ($has_children > 0) {
        $is_parent = true;
    }

    // For administrators: can be both instructor and parent for testing
    if (in_array('administrator', $wp_roles)) {
        $is_parent = true;
    }

    if ($is_instructor) $roles[] = 'instructor';
    if ($is_parent) $roles[] = 'parent';

    // Default to parent if no roles detected (for new users without specific role)
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

    // DEBUG: Get detailed role information
    $wp_roles = (array) $user->roles;
    $debug_info = ssm_api_get_user_roles_debug($user->ID, $wp_roles);
    $roles = $debug_info['roles'];

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
        ),
        // DEBUG INFO - remove after debugging
        'debug' => $debug_info
    );
}

function ssm_api_register($request) {
    $params = $request->get_json_params();

    $first_name = sanitize_text_field($params['first_name'] ?? '');
    $last_name = sanitize_text_field($params['last_name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $phone = sanitize_text_field($params['phone'] ?? '');
    $password = $params['password'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name)) {
        return new WP_REST_Response(array('message' => 'Imię i nazwisko są wymagane'), 400);
    }

    if (empty($email) || !is_email($email)) {
        return new WP_REST_Response(array('message' => 'Podaj poprawny adres email'), 400);
    }

    if (empty($password) || strlen($password) < 6) {
        return new WP_REST_Response(array('message' => 'Hasło musi mieć minimum 6 znaków'), 400);
    }

    // Check if email already exists
    if (email_exists($email)) {
        return new WP_REST_Response(array('message' => 'Ten adres email jest już zarejestrowany'), 400);
    }

    // Check if username (email) already exists
    if (username_exists($email)) {
        return new WP_REST_Response(array('message' => 'Ten adres email jest już zarejestrowany'), 400);
    }

    // Create user
    $user_id = wp_create_user($email, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_REST_Response(array('message' => 'Nie udało się utworzyć konta: ' . $user_id->get_error_message()), 500);
    }

    // Update user meta
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    if (!empty($phone)) {
        update_user_meta($user_id, 'phone', $phone);
    }

    // Set display name
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => trim($first_name . ' ' . $last_name),
        'first_name' => $first_name,
        'last_name' => $last_name,
    ));

    // Assign 'ssm_parent' role (registered users are parents by default)
    $user = new WP_User($user_id);
    $user->set_role('ssm_parent');

    // Create client record in ssm_clients table
    global $wpdb;
    $table_clients = $wpdb->prefix . 'ssm_clients';

    $wpdb->insert($table_clients, array(
        'user_id' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'created_at' => current_time('mysql'),
    ));

    return array(
        'success' => true,
        'message' => 'Konto zostało utworzone',
        'user_id' => $user_id,
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
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);

    // Get or create client record with full debug info
    $result = ssm_api_get_or_create_client($user_id, true);
    $client = $result['client'];
    $debug_info = $result['debug'];

    error_log("SSM API get_children DEBUG: " . json_encode($debug_info));

    if (!$client) {
        return array(
            '_debug' => $debug_info,
            'children' => array()
        );
    }

    // First check: how many records in client_children for this client?
    $cc_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d",
        $client->id
    ));
    $debug_info['client_children_count'] = $cc_count;

    // Get children for this client through client_children relationship
    // Removed ch.active = 1 condition to check if that's the issue
    $children = $wpdb->get_results($wpdb->prepare(
        "SELECT
            ch.id,
            ch.first_name,
            ch.last_name,
            ch.date_of_birth,
            ch.swimming_level,
            ch.medical_notes,
            ch.photo,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments e
             WHERE e.child_id = ch.id AND e.status = 'active') as active_courses,
            COALESCE((SELECT cp.points FROM {$wpdb->prefix}ssm_child_points cp
             WHERE cp.child_id = ch.id), 0) as total_points,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_child_achievements ca
             WHERE ca.child_id = ch.id) as achievements_count
        FROM {$wpdb->prefix}ssm_children ch
        JOIN {$wpdb->prefix}ssm_client_children cc ON cc.child_id = ch.id
        WHERE cc.client_id = %d
        ORDER BY ch.first_name",
        $client->id
    ));

    $debug_info['children_found'] = count($children);
    $debug_info['last_query'] = $wpdb->last_query;
    $debug_info['last_error'] = $wpdb->last_error;

    error_log("SSM API get_children RESULT: " . json_encode($debug_info));

    $result = array();
    foreach ($children as $child) {
        // Get next session for this child
        $next_session = $wpdb->get_row($wpdb->prepare(
            "SELECT
                s.session_date as date,
                TIME_FORMAT(s.time_start, '%%H:%%i') as time,
                c.name as class_name
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            JOIN {$wpdb->prefix}ssm_enrollments e ON e.class_id = c.id AND e.status = 'active'
            WHERE e.child_id = %d
            AND s.session_date >= CURDATE()
            AND s.status = 'scheduled'
            ORDER BY s.session_date, s.time_start
            LIMIT 1",
            $child->id
        ));

        $result[] = array(
            'id' => intval($child->id),
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'birth_date' => $child->date_of_birth,
            'swimming_level' => $child->swimming_level ?: 'Początkujący',
            'active_courses' => intval($child->active_courses),
            'total_points' => intval($child->total_points),
            'achievements_count' => intval($child->achievements_count),
            'medical_notes' => $child->medical_notes ?: '',
            'photo' => $child->photo,
            'next_session' => $next_session ? array(
                'date' => $next_session->date,
                'time' => $next_session->time,
                'class_name' => $next_session->class_name
            ) : null
        );
    }

    // Return with full debug info
    $debug_info['children_result_count'] = count($result);
    return array(
        '_debug' => $debug_info,
        'children' => $result
    );
}

function ssm_api_get_child_details($request) {
    global $wpdb;
    $child_id = intval($request->get_param('id'));
    $user_id = ssm_api_get_user_id($request);

    // Verify this child belongs to the logged-in parent
    $client = ssm_api_get_or_create_client($user_id);
    if (!$client) {
        return new WP_Error('unauthorized', 'Brak uprawnień', array('status' => 403));
    }

    // Check if child belongs to this client
    $belongs = $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d AND child_id = %d",
        $client->id,
        $child_id
    ));

    if (!$belongs) {
        return new WP_Error('not_found', 'Dziecko nie znalezione', array('status' => 404));
    }

    // Get child basic info
    $child = $wpdb->get_row($wpdb->prepare(
        "SELECT
            id, first_name, last_name, date_of_birth,
            swimming_level, medical_notes, photo
        FROM {$wpdb->prefix}ssm_children
        WHERE id = %d",
        $child_id
    ));

    if (!$child) {
        return new WP_Error('not_found', 'Dziecko nie znalezione', array('status' => 404));
    }

    // Get total points
    $total_points = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(points, 0) FROM {$wpdb->prefix}ssm_child_points WHERE child_id = %d",
        $child_id
    )) ?: 0;

    // Get courses (enrollments)
    $courses = $wpdb->get_results($wpdb->prepare(
        "SELECT
            c.id,
            c.name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            c.day_of_week,
            TIME_FORMAT(c.time_start, '%%H:%%i') as time_start,
            TIME_FORMAT(c.time_end, '%%H:%%i') as time_end,
            f.name as facility_name,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions s
             WHERE s.class_id = c.id AND s.session_date >= CURDATE() AND s.status = 'scheduled') as sessions_remaining,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions s
             WHERE s.class_id = c.id) as sessions_total
        FROM {$wpdb->prefix}ssm_enrollments e
        JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
        LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
        LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
        WHERE e.child_id = %d AND e.status = 'active'",
        $child_id
    ));

    $courses_result = array();
    foreach ($courses as $course) {
        $courses_result[] = array(
            'id' => intval($course->id),
            'name' => $course->name,
            'instructor_name' => $course->instructor_name ?: 'Instruktor',
            'day_of_week' => $course->day_of_week,
            'time_start' => $course->time_start,
            'time_end' => $course->time_end,
            'facility_name' => $course->facility_name ?: 'Basen',
            'sessions_remaining' => intval($course->sessions_remaining),
            'sessions_total' => intval($course->sessions_total)
        );
    }

    // Get achievements
    $achievements = $wpdb->get_results($wpdb->prepare(
        "SELECT
            a.id, a.name, a.description, a.icon, a.points,
            ca.earned_at
        FROM {$wpdb->prefix}ssm_child_achievements ca
        JOIN {$wpdb->prefix}ssm_achievements a ON ca.achievement_id = a.id
        WHERE ca.child_id = %d
        ORDER BY ca.earned_at DESC",
        $child_id
    ));

    $achievements_result = array();
    foreach ($achievements as $ach) {
        $achievements_result[] = array(
            'id' => intval($ach->id),
            'name' => $ach->name,
            'description' => $ach->description,
            'icon' => $ach->icon ?: '🏆',
            'earned_at' => $ach->earned_at,
            'points' => intval($ach->points)
        );
    }

    // Get recent attendance (last 10 sessions)
    $attendance = $wpdb->get_results($wpdb->prepare(
        "SELECT
            s.id,
            s.session_date,
            CASE WHEN att.status IS NOT NULL THEN att.status
                 WHEN ab.id IS NOT NULL THEN 'absent'
                 ELSE 'present' END as status,
            c.name as class_name
        FROM {$wpdb->prefix}ssm_sessions s
        JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        JOIN {$wpdb->prefix}ssm_enrollments e ON e.class_id = c.id AND e.child_id = %d AND e.status = 'active'
        LEFT JOIN {$wpdb->prefix}ssm_attendance att ON att.session_id = s.id AND att.child_id = %d
        LEFT JOIN {$wpdb->prefix}ssm_absences ab ON ab.session_id = s.id AND ab.child_id = %d
        WHERE s.session_date <= CURDATE()
        ORDER BY s.session_date DESC
        LIMIT 10",
        $child_id,
        $child_id,
        $child_id
    ));

    $attendance_result = array();
    foreach ($attendance as $att) {
        $attendance_result[] = array(
            'id' => intval($att->id),
            'session_date' => $att->session_date,
            'status' => $att->status,
            'class_name' => $att->class_name
        );
    }

    return array(
        'id' => intval($child->id),
        'first_name' => $child->first_name,
        'last_name' => $child->last_name,
        'birth_date' => $child->date_of_birth,
        'swimming_level' => $child->swimming_level ?: 'Początkujący',
        'total_points' => intval($total_points),
        'medical_notes' => $child->medical_notes ?: '',
        'photo' => $child->photo,
        'courses' => $courses_result,
        'achievements' => $achievements_result,
        'recent_attendance' => $attendance_result
    );
}

function ssm_api_get_schedule($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);
    $date_from = $request->get_param('date_from') ?? date('Y-m-d');

    // Get or create client record (auto-creates for ssm_parent users)
    $client = ssm_api_get_or_create_client($user_id);

    if (!$client) {
        return array();
    }

    // Get scheduled sessions for all children of this parent
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT
            s.id,
            s.session_date,
            TIME_FORMAT(s.time_start, '%%H:%%i') as time_start,
            TIME_FORMAT(s.time_end, '%%H:%%i') as time_end,
            c.name as class_name,
            f.name as facility_name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            ch.id as child_id,
            ch.first_name as child_first_name,
            ch.last_name as child_last_name,
            s.status,
            (SELECT 1 FROM {$wpdb->prefix}ssm_absences a
             WHERE a.session_id = s.id AND a.child_id = ch.id LIMIT 1) as is_absent
        FROM {$wpdb->prefix}ssm_sessions s
        JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
        LEFT JOIN {$wpdb->prefix}ssm_instructors i ON COALESCE(s.instructor_id, c.instructor_id) = i.id
        JOIN {$wpdb->prefix}ssm_enrollments e ON e.class_id = c.id AND e.status = 'active'
        JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
        JOIN {$wpdb->prefix}ssm_client_children cc ON cc.child_id = ch.id AND cc.client_id = %d
        WHERE s.session_date >= %s
        AND s.status = 'scheduled'
        ORDER BY s.session_date, s.time_start",
        $client->id,
        $date_from
    ));

    $result = array();
    foreach ($sessions as $session) {
        $result[] = array(
            'id' => intval($session->id),
            'session_date' => $session->session_date,
            'time_start' => $session->time_start,
            'time_end' => $session->time_end,
            'facility_name' => $session->facility_name ?: 'Basen',
            'instructor_name' => $session->instructor_name ?: 'Instruktor',
            'child_id' => intval($session->child_id),
            'child_first_name' => $session->child_first_name,
            'child_last_name' => $session->child_last_name,
            'class_name' => $session->class_name,
            'status' => $session->status,
            'is_absent' => !empty($session->is_absent)
        );
    }

    return $result;
}

function ssm_api_get_payments($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);
    $status_filter = $request->get_param('status'); // 'pending', 'paid', or null for all

    // Get client for this user
    $client = ssm_api_get_or_create_client($user_id);
    if (!$client) {
        return array();
    }

    // Build query
    $where_status = '';
    if ($status_filter === 'pending') {
        $where_status = "AND p.status IN ('pending', 'partial', 'overdue')";
    } elseif ($status_filter === 'paid') {
        $where_status = "AND p.status = 'paid'";
    }

    $payments = $wpdb->get_results($wpdb->prepare(
        "SELECT
            p.id,
            p.title,
            p.description,
            p.total_amount,
            p.paid_amount,
            (p.total_amount - p.paid_amount) as remaining_amount,
            p.due_date,
            p.status,
            p.created_at,
            e.child_id,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name
        FROM {$wpdb->prefix}ssm_payments p
        LEFT JOIN {$wpdb->prefix}ssm_enrollments e ON p.enrollment_id = e.id
        LEFT JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
        WHERE p.client_id = %d
        $where_status
        ORDER BY
            CASE WHEN p.status IN ('pending', 'partial', 'overdue') THEN 0 ELSE 1 END,
            p.due_date ASC",
        $client->id
    ));

    $result = array();
    foreach ($payments as $payment) {
        $result[] = array(
            'id' => intval($payment->id),
            'title' => $payment->title,
            'description' => $payment->description ?: '',
            'total_amount' => floatval($payment->total_amount),
            'paid_amount' => floatval($payment->paid_amount),
            'remaining_amount' => floatval($payment->remaining_amount),
            'due_date' => $payment->due_date,
            'status' => $payment->status,
            'child_name' => $payment->child_name ?: '',
            'created_at' => $payment->created_at
        );
    }

    return $result;
}

function ssm_api_get_payment_history($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);

    // Get client for this user
    $client = ssm_api_get_or_create_client($user_id);
    if (!$client) {
        return array();
    }

    // Get payment transactions for all payments belonging to this client
    $transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT
            t.id,
            t.amount,
            t.created_at as payment_date,
            t.payment_method,
            t.notes,
            p.title as invoice_title
        FROM {$wpdb->prefix}ssm_payment_transactions t
        JOIN {$wpdb->prefix}ssm_payments p ON t.payment_id = p.id
        WHERE p.client_id = %d
        ORDER BY t.created_at DESC",
        $client->id
    ));

    $result = array();
    foreach ($transactions as $tx) {
        $result[] = array(
            'id' => intval($tx->id),
            'amount' => floatval($tx->amount),
            'payment_date' => $tx->payment_date,
            'payment_method' => $tx->payment_method ?: 'Przelew',
            'invoice_title' => $tx->invoice_title
        );
    }

    return $result;
}

function ssm_api_absences($request) {
    global $wpdb;
    $table_absences = $wpdb->prefix . 'ssm_absences';

    $user_id = ssm_api_get_user_id($request);

    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        $session_id = intval($params['session_id'] ?? 0);
        $child_id = intval($params['child_id'] ?? 0);
        $reason = sanitize_text_field($params['reason'] ?? '');

        if (!$session_id || !$child_id) {
            return new WP_REST_Response(array('message' => 'Brak ID sesji lub dziecka'), 400);
        }

        // Get session details with class info
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.id as class_id, c.max_absences, c.allow_makeups
             FROM {$wpdb->prefix}ssm_sessions s
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             WHERE s.id = %d",
            $session_id
        ));

        if (!$session) {
            return new WP_REST_Response(array('message' => 'Sesja nie znaleziona'), 404);
        }

        // Check if absence already reported
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_absences WHERE session_id = %d AND child_id = %d",
            $session_id, $child_id
        ));

        if ($existing) {
            return new WP_REST_Response(array('message' => 'Nieobecność już została zgłoszona'), 400);
        }

        // Get enrollment for this child and class (with max_makeups limit)
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT id, max_makeups FROM {$wpdb->prefix}ssm_enrollments
             WHERE child_id = %d AND class_id = %d AND status = 'active'",
            $child_id,
            $session->class_id
        ));

        if (!$enrollment) {
            return new WP_REST_Response(array('message' => 'Dziecko nie jest zapisane na ten kurs'), 400);
        }

        $enrollment_id = $enrollment->id;

        // Check 24h rule
        $session_datetime = $session->session_date . ' ' . $session->time_start;
        $session_timestamp = strtotime($session_datetime);
        $now_timestamp = current_time('timestamp');
        $hours_until_session = ($session_timestamp - $now_timestamp) / 3600;

        $reported_on_time = $hours_until_session >= 24;

        // Check if class allows makeups
        $class_allows_makeups = (bool) $session->allow_makeups;

        // Check makeup limit from enrollment (per child per course)
        $used_makeups = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
             WHERE enrollment_id = %d AND can_makeup = 1",
            $enrollment_id
        ));

        $max_makeups = intval($enrollment->max_makeups) ?: 2;
        $within_limit = $used_makeups < $max_makeups;

        // Determine if this absence can be made up
        $can_makeup = $class_allows_makeups && $reported_on_time && $within_limit ? 1 : 0;

        // Insert absence
        $result = $wpdb->insert($table_absences, array(
            'enrollment_id' => $enrollment_id,
            'session_id' => $session_id,
            'child_id' => $child_id,
            'reported_at' => current_time('mysql'),
            'reason' => $reason,
            'status' => 'reported',
            'can_makeup' => $can_makeup
        ));

        if ($result === false) {
            error_log('SSM API: Failed to insert absence. Error: ' . $wpdb->last_error);
            return new WP_REST_Response(array(
                'message' => 'Błąd zapisu do bazy danych',
                'debug' => $wpdb->last_error
            ), 500);
        }

        // Build response message
        $message = 'Nieobecność została zgłoszona.';
        if (!$can_makeup) {
            if (!$class_allows_makeups) {
                $message .= ' Ten kurs nie pozwala na odrabianie zajęć.';
            } elseif (!$reported_on_time) {
                $message .= ' Zgłoszenie po terminie (min. 24h przed zajęciami) - brak możliwości odrobienia.';
            } elseif (!$within_limit) {
                $message .= ' Wykorzystano limit odrabiań (' . $max_makeups . ') dla tego kursu.';
            }
        }

        return array(
            'success' => true,
            'message' => $message,
            'absence_id' => $wpdb->insert_id,
            'can_makeup' => (bool) $can_makeup,
            '_debug' => array(
                'enrollment_id' => $enrollment_id,
                'hours_until_session' => round($hours_until_session, 1),
                'reported_on_time' => $reported_on_time,
                'class_allows_makeups' => $class_allows_makeups,
                'used_makeups' => intval($used_makeups),
                'max_makeups' => $max_makeups,
                'within_limit' => $within_limit
            )
        );
    }

    // DELETE - cancel/withdraw absence report or scheduled makeup
    if ($request->get_method() === 'DELETE') {
        $params = $request->get_json_params();
        $absence_id = intval($params['absence_id'] ?? 0);

        if (!$absence_id) {
            return new WP_REST_Response(array('message' => 'Brak ID nieobecności'), 400);
        }

        $user_id = ssm_api_get_user_id($request);
        $client = ssm_api_get_or_create_client($user_id);

        if (!$client) {
            return new WP_REST_Response(array('message' => 'Nie znaleziono klienta'), 404);
        }

        // Get child IDs for this client
        $child_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT child_id FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d",
            $client->id
        ));

        if (empty($child_ids)) {
            return new WP_REST_Response(array('message' => 'Brak przypisanych dzieci'), 400);
        }

        // Get the absence with session info
        $placeholders = implode(',', array_fill(0, count($child_ids), '%d'));
        $query_params = array_merge([$absence_id], $child_ids);

        $absence = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, s.session_date, s.time_start
             FROM {$wpdb->prefix}ssm_absences a
             JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
             WHERE a.id = %d AND a.child_id IN ($placeholders)",
            ...$query_params
        ));

        if (!$absence) {
            return new WP_REST_Response(array('message' => 'Nieobecność nie znaleziona lub brak uprawnień'), 404);
        }

        // Check if makeup is already completed - cannot cancel
        if ($absence->status === 'makeup_completed') {
            return new WP_REST_Response(array(
                'message' => 'Nie można cofnąć - odrabianie zostało już zrealizowane'
            ), 400);
        }

        // Check if session is in the future
        $session_datetime = $absence->session_date . ' ' . $absence->time_start;
        $session_timestamp = strtotime($session_datetime);
        $now_timestamp = current_time('timestamp');

        if ($session_timestamp <= $now_timestamp) {
            return new WP_REST_Response(array(
                'message' => 'Nie można cofnąć nieobecności - zajęcia już się odbyły'
            ), 400);
        }

        // Handle based on status
        if ($absence->status === 'makeup_scheduled') {
            // Cancel scheduled makeup - revert to 'reported' status
            $result = $wpdb->update(
                $table_absences,
                array(
                    'status' => 'reported',
                    'makeup_session_id' => null
                ),
                array('id' => $absence_id)
            );

            if ($result === false) {
                return new WP_REST_Response(array(
                    'message' => 'Błąd podczas cofania zaplanowanego odrabiania',
                    'debug' => $wpdb->last_error
                ), 500);
            }

            return array(
                'success' => true,
                'message' => 'Zaplanowane odrabianie zostało cofnięte. Możesz zaplanować je ponownie.'
            );
        } else {
            // Delete the absence entirely (for 'reported' or 'confirmed' status)
            $result = $wpdb->delete($table_absences, array('id' => $absence_id));

            if ($result === false) {
                return new WP_REST_Response(array(
                    'message' => 'Błąd podczas usuwania nieobecności',
                    'debug' => $wpdb->last_error
                ), 500);
            }

            return array(
                'success' => true,
                'message' => 'Zgłoszenie nieobecności zostało cofnięte'
            );
        }
    }

    // GET - return absences from database with real data
    $user_id = ssm_api_get_user_id($request);
    $result_client = ssm_api_get_or_create_client($user_id, true);
    $client = $result_client['client'];
    $debug_info = $result_client['debug'];

    if (!$client) {
        return array(
            '_debug' => array_merge($debug_info, array('error' => 'No client found')),
            'absences' => array()
        );
    }

    // First, get child IDs for this client
    $child_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT child_id FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d",
        $client->id
    ));

    $debug_info['client_id'] = $client->id;
    $debug_info['child_ids'] = $child_ids;

    if (empty($child_ids)) {
        return array(
            '_debug' => array_merge($debug_info, array('error' => 'No children linked to client')),
            'absences' => array()
        );
    }

    // Build IN clause for child_ids
    $placeholders = implode(',', array_fill(0, count($child_ids), '%d'));

    // Get absences for children of this parent
    $query = $wpdb->prepare(
        "SELECT
            a.id,
            a.child_id,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            a.session_id,
            s.session_date,
            TIME_FORMAT(s.time_start, '%%H:%%i') as time_start,
            c.name as class_name,
            a.status,
            a.reason,
            a.reported_at,
            COALESCE(a.can_makeup, 0) as can_makeup,
            a.makeup_session_id
        FROM {$wpdb->prefix}ssm_absences a
        LEFT JOIN {$wpdb->prefix}ssm_children ch ON a.child_id = ch.id
        LEFT JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
        LEFT JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        WHERE a.child_id IN ($placeholders)
        ORDER BY a.reported_at DESC
        LIMIT 50",
        ...$child_ids
    );

    $absences = $wpdb->get_results($query);

    $debug_info['absences_count'] = count($absences);
    $debug_info['total_absences_in_table'] = $total_absences_count;

    $result = array();
    foreach ($absences as $absence) {
        $makeup_session = null;
        if ($absence->makeup_session_id) {
            $makeup = $wpdb->get_row($wpdb->prepare(
                "SELECT s.session_date as date, TIME_FORMAT(s.time_start, '%%H:%%i') as time, c.name as class_name
                 FROM {$wpdb->prefix}ssm_sessions s
                 JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                 WHERE s.id = %d",
                $absence->makeup_session_id
            ));
            if ($makeup) {
                $makeup_session = array(
                    'id' => $absence->makeup_session_id,
                    'date' => $makeup->date,
                    'time' => $makeup->time,
                    'class_name' => $makeup->class_name
                );
            }
        }

        $result[] = array(
            'id' => intval($absence->id),
            'child_id' => intval($absence->child_id),
            'child_name' => $absence->child_name ?: 'Nieznane dziecko',
            'session_id' => intval($absence->session_id),
            'session_date' => $absence->session_date,
            'time_start' => $absence->time_start,
            'class_name' => $absence->class_name ?: 'Zajęcia',
            'status' => $absence->status,
            'reason' => $absence->reason,
            'reported_at' => $absence->reported_at,
            'can_makeup' => (bool) $absence->can_makeup,
            'makeup_session' => $makeup_session
        );
    }

    return array(
        '_debug' => $debug_info,
        'absences' => $result
    );
}

function ssm_api_get_upcoming_sessions($request) {
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);

    // Get client for current user
    $client = ssm_api_get_or_create_client($user_id);

    if (!$client) {
        return array();
    }

    // Get upcoming sessions for this parent's children with class makeup settings
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT
            s.id,
            s.session_date,
            s.time_start,
            s.time_end,
            c.id as class_id,
            c.name as class_name,
            c.max_absences,
            c.allow_makeups,
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
        // Check if absence already reported
        $existing_absence = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_absences
             WHERE session_id = %d AND child_id = %d",
            $session->id, $session->child_id
        ));

        // Check 24h rule
        $session_datetime = $session->session_date . ' ' . $session->time_start;
        $session_timestamp = strtotime($session_datetime);
        $now_timestamp = current_time('timestamp');
        $hours_until_session = ($session_timestamp - $now_timestamp) / 3600;
        $can_report_on_time = $hours_until_session >= 24;

        // Check makeup limit for this child in this class
        $used_makeups = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences a
             JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
             WHERE a.child_id = %d AND s.class_id = %d AND a.can_makeup = 1",
            $session->child_id,
            $session->class_id
        ));

        $max_makeups = intval($session->max_absences);
        $remaining_makeups = max(0, $max_makeups - intval($used_makeups));
        $class_allows_makeups = (bool) $session->allow_makeups;

        // Can report absence if not already reported
        $can_report = empty($existing_absence);

        // Will get makeup credit if: class allows, reported on time, within limit
        $will_get_makeup = $can_report && $class_allows_makeups && $can_report_on_time && $remaining_makeups > 0;

        $result[] = array(
            'id' => intval($session->id),
            'session_date' => $session->session_date,
            'time_start' => substr($session->time_start, 0, 5),
            'time_end' => substr($session->time_end ?: '', 0, 5),
            'class_name' => $session->class_name,
            'facility_name' => $session->facility_name ?: 'Basen',
            'child_id' => intval($session->child_id),
            'child_name' => $session->child_name,
            'can_report_absence' => $can_report,
            'makeup_info' => array(
                'class_allows_makeups' => $class_allows_makeups,
                'can_report_on_time' => $can_report_on_time,
                'hours_until_session' => round($hours_until_session, 1),
                'remaining_makeups' => $remaining_makeups,
                'max_makeups' => $max_makeups,
                'will_get_makeup' => $will_get_makeup
            )
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

    // Check if user is parent by WordPress role
    $is_parent_role = in_array('ssm_parent', (array) $user->roles) ||
                      in_array('subscriber', (array) $user->roles) ||
                      in_array('customer', (array) $user->roles);

    // For administrators, also allow parent view (for testing/demo)
    if (in_array('administrator', (array) $user->roles)) {
        $is_parent_role = true;
    }

    // Check if user is parent/client (by user_id first, then email)
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_clients WHERE user_id = %d OR email = %s",
        $user_id,
        $user_email
    ));

    if ($client) {
        $result['client_id'] = $client->id;
    } elseif ($is_parent_role) {
        // Has parent role but no record - use user_id as client_id (fallback)
        $result['client_id'] = $user_id;
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

    // Get user email for lookup
    $user_data = get_userdata($user_id);
    $user_email = $user_data ? $user_data->user_email : '';

    // Get instructor_id for current user (by user_id or email)
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
        $user_id, $user_email
    ));

    if (!$instructor) {
        // No instructor record - return debug info
        return array(
            '_debug' => array(
                'error' => 'No instructor found',
                'user_id' => $user_id,
                'user_email' => $user_email,
                'query' => "SELECT id FROM {$wpdb->prefix}ssm_instructors WHERE user_id = $user_id OR email = '$user_email'"
            ),
            'sessions' => array()
        );
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

    // Return with debug info
    return array(
        '_debug' => array(
            'user_id' => $user_id,
            'user_email' => $user_email,
            'instructor_id' => $instructor->id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'sessions_found' => count($result)
        ),
        'sessions' => $result
    );
}

function ssm_api_get_session_details($request) {
    global $wpdb;
    $session_id = intval($request->get_param('id'));
    $user_id = ssm_api_get_user_id($request);

    // Get instructor for current user
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d",
        $user_id
    ));

    // Get session details
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT
            s.id,
            s.session_date,
            s.time_start,
            s.time_end,
            s.status,
            s.instructor_id,
            c.id as class_id,
            c.name as class_name,
            c.level,
            c.max_participants,
            c.description,
            f.name as facility_name,
            f.address as facility_address
        FROM {$wpdb->prefix}ssm_sessions s
        JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
        WHERE s.id = %d",
        $session_id
    ));

    if (!$session) {
        return new WP_REST_Response(array('message' => 'Sesja nie znaleziona'), 404);
    }

    // Get enrolled participants for this class
    $participants = $wpdb->get_results($wpdb->prepare(
        "SELECT
            e.id as enrollment_id,
            ch.id as child_id,
            ch.first_name,
            ch.last_name,
            ch.swimming_level,
            ch.medical_notes,
            COALESCE(a.status, 'unmarked') as status,
            COALESCE(a.notes, '') as notes
        FROM {$wpdb->prefix}ssm_enrollments e
        JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
        LEFT JOIN {$wpdb->prefix}ssm_attendance a ON a.session_id = %d AND a.child_id = ch.id
        WHERE e.class_id = %d AND e.status = 'active'
        ORDER BY ch.last_name, ch.first_name",
        $session_id,
        $session->class_id
    ));

    $participants_array = array();
    foreach ($participants as $p) {
        $participants_array[] = array(
            'enrollment_id' => intval($p->enrollment_id),
            'child_id' => intval($p->child_id),
            'first_name' => $p->first_name,
            'last_name' => $p->last_name,
            'status' => $p->status,
            'notes' => $p->notes,
            'swimming_level' => $p->swimming_level ?: 'Początkujący',
            'medical_notes' => $p->medical_notes ?: ''
        );
    }

    return array(
        'id' => intval($session->id),
        'session_date' => $session->session_date,
        'time_start' => $session->time_start,
        'time_end' => $session->time_end,
        'class_name' => $session->class_name,
        'level' => $session->level ?: 'Początkujący',
        'facility_name' => $session->facility_name ?: 'Basen',
        'facility_address' => $session->facility_address ?: '',
        'max_participants' => intval($session->max_participants) ?: 10,
        'description' => $session->description ?: '',
        'participants' => $participants_array
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

            // Sprawdź czy rodzic zgłosił nieobecność dla tego dziecka
            $has_absence_report = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
                 WHERE session_id = %d AND child_id = %d",
                $session_id, $child_id
            ));

            // Walidacja statusu:
            // - "excused" można ustawić TYLKO gdy rodzic zgłosił nieobecność
            // - instruktor może wybrać tylko "present" lub "absent"
            if ($status === 'excused' && !$has_absence_report) {
                $status = 'absent';
            }
            if ($has_absence_report) {
                $status = 'excused';
            }

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

    // GET - return real participants with their attendance status
    // First, get the class_id for this session
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT class_id FROM {$wpdb->prefix}ssm_sessions WHERE id = %d",
        $session_id
    ));

    if (!$session) {
        return new WP_REST_Response(array('message' => 'Sesja nie znaleziona'), 404);
    }

    // Get all enrolled children for this class with their attendance status
    $participants = $wpdb->get_results($wpdb->prepare(
        "SELECT
            e.id as enrollment_id,
            ch.id as child_id,
            ch.first_name,
            ch.last_name,
            ch.swimming_level,
            ch.medical_notes,
            COALESCE(a.status, 'unmarked') as status,
            COALESCE(a.notes, '') as notes,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences ab
             WHERE ab.session_id = %d AND ab.child_id = ch.id) as has_reported_absence
        FROM {$wpdb->prefix}ssm_enrollments e
        JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
        LEFT JOIN $table_attendance a ON a.session_id = %d AND a.child_id = ch.id
        WHERE e.class_id = %d AND e.status = 'active'
        ORDER BY ch.last_name, ch.first_name",
        $session_id,
        $session_id,
        $session->class_id
    ));

    $result = array();
    foreach ($participants as $p) {
        $is_reported = intval($p->has_reported_absence) > 0;
        $result[] = array(
            'enrollment_id' => intval($p->enrollment_id),
            'child_id' => intval($p->child_id),
            'first_name' => $p->first_name,
            'last_name' => $p->last_name,
            'status' => $is_reported ? 'excused' : $p->status,
            'notes' => $p->notes,
            'swimming_level' => $p->swimming_level ?: '',
            'medical_notes' => $p->medical_notes ?: '',
            'is_reported_absence' => $is_reported,
            'is_locked' => $is_reported
        );
    }

    return $result;
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
    global $wpdb;
    $user_id = ssm_api_get_user_id($request);

    $month = intval($request->get_param('month') ?: date('n'));
    $year = intval($request->get_param('year') ?: date('Y'));

    // Version marker to confirm code update
    $api_version = 'v2.1-debug-' . date('Y-m-d-His');

    // Get user email for lookup
    $user_data = get_userdata($user_id);
    $user_email = $user_data ? $user_data->user_email : '';

    // Get instructor for current user (by user_id or email)
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT id, hourly_rate FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
        $user_id, $user_email
    ));

    if (!$instructor) {
        return array(
            '_debug' => array(
                'api_version' => $api_version,
                'error' => 'No instructor found',
                'user_id' => $user_id,
                'user_email' => $user_email
            ),
            'month' => $month,
            'year' => $year,
            'hourly_rate' => 0,
            'total_hours' => 0,
            'total_salary' => 0,
            'sessions_count' => 0,
            'sessions' => array()
        );
    }

    $hourly_rate = floatval($instructor->hourly_rate) ?: 0;

    // Calculate date range for the month
    $date_from = sprintf('%04d-%02d-01', $year, $month);
    $date_to = date('Y-m-t', strtotime($date_from));

    // Get completed sessions for this instructor in the given month
    // Only count sessions that have at least one attendance marked (confirmed sessions)
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT
            s.id,
            s.session_date,
            s.time_start,
            s.time_end,
            c.name as class_name,
            TIMESTAMPDIFF(MINUTE, s.time_start, s.time_end) as duration_minutes
        FROM {$wpdb->prefix}ssm_sessions s
        JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
        WHERE s.instructor_id = %d
        AND s.session_date BETWEEN %s AND %s
        AND s.session_date <= CURDATE()
        AND EXISTS (
            SELECT 1 FROM {$wpdb->prefix}ssm_attendance a
            WHERE a.session_id = s.id
        )
        ORDER BY s.session_date DESC, s.time_start",
        $instructor->id,
        $date_from,
        $date_to
    ));

    $sessions_array = array();
    $total_minutes = 0;

    foreach ($sessions as $session) {
        $duration = intval($session->duration_minutes) ?: 45;
        $total_minutes += $duration;

        $sessions_array[] = array(
            'id' => intval($session->id),
            'session_date' => $session->session_date,
            'time_start' => $session->time_start,
            'time_end' => $session->time_end,
            'class_name' => $session->class_name,
            'duration_minutes' => $duration
        );
    }

    $total_hours = $total_minutes / 60;
    $total_salary = $total_hours * $hourly_rate;

    return array(
        '_debug' => array(
            'api_version' => $api_version,
            'user_id' => $user_id,
            'user_email' => $user_email,
            'instructor_id' => $instructor->id,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'total_minutes' => $total_minutes
        ),
        'month' => $month,
        'year' => $year,
        'hourly_rate' => $hourly_rate,
        'total_hours' => round($total_hours, 2),
        'total_salary' => round($total_salary, 2),
        'sessions_count' => count($sessions_array),
        'sessions' => $sessions_array
    );
}
