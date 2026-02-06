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
    $primary_type = in_array('instructor', $roles) ? 'instructor' : 'parent';

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
    $primary_type = in_array('instructor', $roles) ? 'instructor' : 'parent';

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
    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        return array(
            'success' => true,
            'message' => 'Nieobecność została zgłoszona',
            'absence_id' => rand(100, 999)
        );
    }

    // Return sample absences
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
    return array(
        array(
            'id' => 201,
            'session_date' => date('Y-m-d', strtotime('+2 days')),
            'time_start' => '16:00',
            'class_name' => 'Kurs pływania - poziom średni',
            'child_id' => 1,
            'child_name' => 'Jan Kowalski',
            'can_report_absence' => true
        ),
        array(
            'id' => 202,
            'session_date' => date('Y-m-d', strtotime('+4 days')),
            'time_start' => '17:00',
            'class_name' => 'Kurs pływania - początkujący',
            'child_id' => 2,
            'child_name' => 'Anna Kowalska',
            'can_report_absence' => true
        ),
        array(
            'id' => 203,
            'session_date' => date('Y-m-d', strtotime('+7 days')),
            'time_start' => '16:00',
            'class_name' => 'Kurs pływania - poziom średni',
            'child_id' => 1,
            'child_name' => 'Jan Kowalski',
            'can_report_absence' => true
        )
    );
}

function ssm_api_makeups($request) {
    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        return array(
            'success' => true,
            'message' => 'Odrabianie zostało zaplanowane'
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

function ssm_api_mark_notification_read($request) {
    $notification_id = $request->get_param('id');
    // W prawdziwej implementacji: oznacz powiadomienie jako przeczytane w bazie
    return array(
        'success' => true,
        'notification_id' => $notification_id
    );
}

function ssm_api_mark_all_notifications_read($request) {
    // W prawdziwej implementacji: oznacz wszystkie powiadomienia jako przeczytane
    return array(
        'success' => true
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

// ============ INSTRUCTOR ENDPOINTS ============

function ssm_api_get_instructor_schedule($request) {
    $date_from = $request->get_param('date_from') ?: date('Y-m-d');
    $date_to = $request->get_param('date_to') ?: date('Y-m-d', strtotime('+14 days'));

    // Przykładowe dane
    return array(
        array(
            'id' => 101,
            'session_date' => date('Y-m-d'),
            'time_start' => '09:00:00',
            'time_end' => '09:45:00',
            'class_name' => 'Kurs pływania - początkujący',
            'level' => 'Początkujący',
            'facility_name' => 'Basen Główny',
            'facility_address' => 'ul. Sportowa 15',
            'enrolled_count' => 8,
            'max_participants' => 10,
            'attendance_marked' => 0,
            'status' => 'scheduled'
        ),
        array(
            'id' => 102,
            'session_date' => date('Y-m-d'),
            'time_start' => '10:00:00',
            'time_end' => '10:45:00',
            'class_name' => 'Kurs pływania - średniozaawansowany',
            'level' => 'Średniozaawansowany',
            'facility_name' => 'Basen Główny',
            'facility_address' => 'ul. Sportowa 15',
            'enrolled_count' => 6,
            'max_participants' => 8,
            'attendance_marked' => 1,
            'status' => 'completed'
        ),
        array(
            'id' => 103,
            'session_date' => date('Y-m-d', strtotime('+1 day')),
            'time_start' => '16:00:00',
            'time_end' => '16:45:00',
            'class_name' => 'Kurs pływania - zaawansowany',
            'level' => 'Zaawansowany',
            'facility_name' => 'Basen Mały',
            'facility_address' => 'ul. Wodna 8',
            'enrolled_count' => 5,
            'max_participants' => 6,
            'attendance_marked' => 0,
            'status' => 'scheduled'
        ),
        array(
            'id' => 104,
            'session_date' => date('Y-m-d', strtotime('+2 days')),
            'time_start' => '09:00:00',
            'time_end' => '09:45:00',
            'class_name' => 'Kurs pływania - początkujący',
            'level' => 'Początkujący',
            'facility_name' => 'Basen Główny',
            'facility_address' => 'ul. Sportowa 15',
            'enrolled_count' => 8,
            'max_participants' => 10,
            'attendance_marked' => 0,
            'status' => 'scheduled'
        )
    );
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
    $session_id = $request->get_param('id');

    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        $attendance = $params['attendance'] ?? array();

        // W prawdziwej implementacji: zapisz obecność do bazy danych
        return array(
            'success' => true,
            'message' => 'Obecność została zapisana',
            'session_id' => $session_id,
            'attendance_count' => count($attendance)
        );
    }

    // GET - zwróć obecność dla sesji
    return array(
        array(
            'enrollment_id' => 1,
            'child_id' => 1,
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'status' => 'present',
            'notes' => ''
        ),
        array(
            'enrollment_id' => 2,
            'child_id' => 2,
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'status' => 'absent',
            'notes' => 'Choroba'
        )
    );
}

function ssm_api_substitutions($request) {
    if ($request->get_method() === 'POST') {
        $params = $request->get_json_params();
        return array(
            'success' => true,
            'message' => 'Prośba o zastępstwo została wysłana',
            'substitution_id' => rand(100, 999)
        );
    }

    $type = $request->get_param('type') ?: 'available';

    if ($type === 'available') {
        return array(
            array(
                'id' => 1,
                'session_id' => 201,
                'session_date' => date('Y-m-d', strtotime('+2 days')),
                'time_start' => '14:00:00',
                'time_end' => '14:45:00',
                'class_name' => 'Kurs pływania - średniozaawansowany',
                'facility_name' => 'Basen Główny',
                'instructor_name' => 'Anna Kowalska',
                'reason' => 'Choroba',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ),
            array(
                'id' => 2,
                'session_id' => 202,
                'session_date' => date('Y-m-d', strtotime('+3 days')),
                'time_start' => '16:00:00',
                'time_end' => '16:45:00',
                'class_name' => 'Kurs pływania - początkujący',
                'facility_name' => 'Basen Mały',
                'instructor_name' => 'Piotr Nowak',
                'reason' => 'Wyjazd służbowy',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            )
        );
    } elseif ($type === 'my_requests') {
        return array(
            array(
                'id' => 10,
                'session_id' => 301,
                'session_date' => date('Y-m-d', strtotime('+5 days')),
                'time_start' => '10:00:00',
                'time_end' => '10:45:00',
                'class_name' => 'Kurs pływania - zaawansowany',
                'facility_name' => 'Basen Główny',
                'reason' => 'Wizyta lekarska',
                'replacement_name' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            )
        );
    } else { // my_taken
        return array(
            array(
                'id' => 20,
                'session_id' => 401,
                'session_date' => date('Y-m-d', strtotime('+1 day')),
                'time_start' => '11:00:00',
                'time_end' => '11:45:00',
                'class_name' => 'Kurs pływania - początkujący',
                'facility_name' => 'Basen Główny',
                'original_instructor_name' => 'Maria Wiśniewska',
                'reason' => 'Urlop',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            )
        );
    }
}

function ssm_api_take_substitution($request) {
    $substitution_id = $request->get_param('id');

    return array(
        'success' => true,
        'message' => 'Zastępstwo zostało przyjęte',
        'substitution_id' => $substitution_id
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
