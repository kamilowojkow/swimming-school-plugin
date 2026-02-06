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

    register_rest_route($namespace, '/parent/makeups', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_makeups',
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
});

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
    $role = (in_array('administrator', $user->roles) || in_array('ssm_instructor', $user->roles)) ? 'instructor' : 'parent';

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
            'role' => $role
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

    $role = (in_array('administrator', $user->roles) || in_array('ssm_instructor', $user->roles)) ? 'instructor' : 'parent';

    return array(
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'display_name' => $user->display_name,
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'phone' => get_user_meta($user_id, 'phone', true),
        'role' => $role
    );
}

// ============ PARENT ENDPOINTS ============

function ssm_api_get_children($request) {
    $user_id = ssm_api_get_user_id($request);

    // Zwróć przykładowe dane testowe
    return array(
        array(
            'id' => 1,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birth_date' => '2018-05-15',
            'level' => 'Delfinek',
            'medical_notes' => '',
            'avatar' => null,
            'courses' => array(
                array(
                    'id' => 1,
                    'name' => 'Kurs pływania - poziom średni',
                    'progress' => 65
                )
            ),
            'stats' => array(
                'attendance_rate' => 92,
                'completed_lessons' => 12,
                'achievements' => 3
            )
        ),
        array(
            'id' => 2,
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'birth_date' => '2020-03-22',
            'level' => 'Żółwik',
            'medical_notes' => '',
            'avatar' => null,
            'courses' => array(
                array(
                    'id' => 2,
                    'name' => 'Kurs pływania - początkujący',
                    'progress' => 30
                )
            ),
            'stats' => array(
                'attendance_rate' => 88,
                'completed_lessons' => 5,
                'achievements' => 1
            )
        )
    );
}

function ssm_api_get_child_details($request) {
    $child_id = $request->get_param('id');

    return array(
        'id' => $child_id,
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'birth_date' => '2018-05-15',
        'level' => 'Delfinek',
        'medical_notes' => '',
        'courses' => array(
            array(
                'id' => 1,
                'name' => 'Kurs pływania - poziom średni',
                'instructor' => 'Anna Nowak',
                'schedule' => 'Poniedziałek 16:00',
                'progress' => 65,
                'start_date' => '2025-09-01',
                'end_date' => '2026-06-30'
            )
        ),
        'achievements' => array(
            array(
                'id' => 1,
                'name' => 'Pierwsza długość',
                'description' => 'Przepłynięcie pierwszej długości basenu',
                'earned_date' => '2025-10-15',
                'icon' => 'medal'
            ),
            array(
                'id' => 2,
                'name' => 'Nurek',
                'description' => 'Nurkowanie na głębokość 2m',
                'earned_date' => '2025-11-20',
                'icon' => 'star'
            )
        ),
        'attendance' => array(
            'rate' => 92,
            'present' => 12,
            'absent' => 1,
            'excused' => 0
        )
    );
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
    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        return array(
            'success' => true,
            'message' => 'Nieobecność została zgłoszona',
            'absence_id' => rand(100, 999)
        );
    }

    return array(
        'absences' => array(),
        'makeups_available' => 2
    );
}

function ssm_api_get_makeups($request) {
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
    return array(
        array(
            'id' => 1,
            'title' => 'Przypomnienie o zajęciach',
            'message' => 'Jutro o 16:00 zajęcia pływania dla Jana',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'is_read' => false,
            'type' => 'reminder',
            'icon' => 'notifications',
            'color' => '#3b82f6',
            'time_ago' => '1 godzinę temu'
        )
    );
}

function ssm_api_get_unread_count($request) {
    return array(
        'unread_count' => 1
    );
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
