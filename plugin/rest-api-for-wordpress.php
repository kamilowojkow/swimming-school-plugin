<?php
/**
 * REST API dla aplikacji mobilnej Szkółki Pływania
 *
 * INSTRUKCJA:
 * 1. Skopiuj ten plik do folderu wtyczki: /wp-content/plugins/TWOJA-WTYCZKA/includes/
 * 2. Dodaj w głównym pliku wtyczki: require_once plugin_dir_path(__FILE__) . 'includes/rest-api-mobile.php';
 * 3. Lub wgraj jako osobną wtyczkę do /wp-content/plugins/
 */

// Zapobiegaj bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rejestracja REST API endpoints
 */
add_action('rest_api_init', function () {
    $namespace = 'ssm/v1';

    // Logowanie
    register_rest_route($namespace, '/auth/login', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_login',
        'permission_callback' => '__return_true',
    ));

    // Wylogowanie
    register_rest_route($namespace, '/auth/logout', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_logout',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Profil użytkownika
    register_rest_route($namespace, '/user/profile', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_profile',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Lista dzieci (dla rodzica)
    register_rest_route($namespace, '/parent/children', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_children',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Harmonogram zajęć
    register_rest_route($namespace, '/schedule', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_schedule',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Płatności
    register_rest_route($namespace, '/payments', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_payments',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Nieobecności
    register_rest_route($namespace, '/absences', array(
        'methods' => 'GET',
        'callback' => 'ssm_api_get_absences',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Zgłoś nieobecność
    register_rest_route($namespace, '/absences/report', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_report_absence',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Rejestracja tokenu push
    register_rest_route($namespace, '/notifications/register', array(
        'methods' => 'POST',
        'callback' => 'ssm_api_register_push_token',
        'permission_callback' => 'ssm_api_check_auth',
    ));

    // Test endpoint
    register_rest_route($namespace, '/test', array(
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'API działa poprawnie',
                'version' => '1.0.0',
                'timestamp' => current_time('mysql')
            ), 200);
        },
        'permission_callback' => '__return_true',
    ));
});

/**
 * Sprawdzenie autoryzacji
 */
function ssm_api_check_auth($request) {
    $auth_header = $request->get_header('Authorization');

    if (empty($auth_header)) {
        return false;
    }

    // Format: "Bearer TOKEN"
    if (strpos($auth_header, 'Bearer ') !== 0) {
        return false;
    }

    $token = substr($auth_header, 7);
    $user_id = ssm_api_validate_token($token);

    return $user_id !== false;
}

/**
 * Generowanie tokenu
 */
function ssm_api_generate_token($user_id) {
    $token = wp_generate_password(64, false);
    $expiry = time() + (30 * DAY_IN_SECONDS); // 30 dni

    update_user_meta($user_id, 'ssm_api_token', $token);
    update_user_meta($user_id, 'ssm_api_token_expiry', $expiry);

    return $token;
}

/**
 * Walidacja tokenu
 */
function ssm_api_validate_token($token) {
    global $wpdb;

    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ssm_api_token' AND meta_value = %s",
        $token
    ));

    if (!$user_id) {
        return false;
    }

    $expiry = get_user_meta($user_id, 'ssm_api_token_expiry', true);

    if ($expiry < time()) {
        delete_user_meta($user_id, 'ssm_api_token');
        delete_user_meta($user_id, 'ssm_api_token_expiry');
        return false;
    }

    return $user_id;
}

/**
 * Pobierz user_id z requestu
 */
function ssm_api_get_user_id($request) {
    $auth_header = $request->get_header('Authorization');
    $token = substr($auth_header, 7);
    return ssm_api_validate_token($token);
}

/**
 * Logowanie
 */
function ssm_api_login($request) {
    $params = $request->get_json_params();

    $username = sanitize_text_field($params['username'] ?? '');
    $password = $params['password'] ?? '';

    if (empty($username) || empty($password)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Podaj login i hasło'
        ), 400);
    }

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Nieprawidłowy login lub hasło'
        ), 401);
    }

    $token = ssm_api_generate_token($user->ID);

    // Określ rolę użytkownika
    $role = 'parent';
    if (in_array('administrator', $user->roles) || in_array('ssm_instructor', $user->roles)) {
        $role = 'instructor';
    }

    return new WP_REST_Response(array(
        'success' => true,
        'token' => $token,
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'displayName' => $user->display_name,
            'role' => $role
        )
    ), 200);
}

/**
 * Wylogowanie
 */
function ssm_api_logout($request) {
    $user_id = ssm_api_get_user_id($request);

    delete_user_meta($user_id, 'ssm_api_token');
    delete_user_meta($user_id, 'ssm_api_token_expiry');

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Wylogowano pomyślnie'
    ), 200);
}

/**
 * Profil użytkownika
 */
function ssm_api_get_profile($request) {
    $user_id = ssm_api_get_user_id($request);
    $user = get_userdata($user_id);

    if (!$user) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Użytkownik nie znaleziony'
        ), 404);
    }

    $role = 'parent';
    if (in_array('administrator', $user->roles) || in_array('ssm_instructor', $user->roles)) {
        $role = 'instructor';
    }

    return new WP_REST_Response(array(
        'success' => true,
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'displayName' => $user->display_name,
            'firstName' => get_user_meta($user_id, 'first_name', true),
            'lastName' => get_user_meta($user_id, 'last_name', true),
            'phone' => get_user_meta($user_id, 'phone', true),
            'role' => $role
        )
    ), 200);
}

/**
 * Lista dzieci rodzica
 */
function ssm_api_get_children($request) {
    $user_id = ssm_api_get_user_id($request);

    // Pobierz dzieci przypisane do rodzica
    // To wymaga dostosowania do struktury Twojej wtyczki
    global $wpdb;
    $table_name = $wpdb->prefix . 'ssm_participants';

    // Sprawdź czy tabela istnieje
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Zwróć przykładowe dane jeśli tabela nie istnieje
        return new WP_REST_Response(array(
            'success' => true,
            'children' => array(
                array(
                    'id' => 1,
                    'firstName' => 'Jan',
                    'lastName' => 'Kowalski',
                    'birthDate' => '2018-05-15',
                    'level' => 'Delfinek',
                    'medicalNotes' => '',
                    'courses' => array()
                )
            )
        ), 200);
    }

    $children = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE parent_id = %d",
        $user_id
    ), ARRAY_A);

    $result = array();
    foreach ($children as $child) {
        $result[] = array(
            'id' => $child['id'],
            'firstName' => $child['first_name'],
            'lastName' => $child['last_name'],
            'birthDate' => $child['birth_date'],
            'level' => $child['level'] ?? 'Żółwik',
            'medicalNotes' => $child['medical_notes'] ?? '',
            'courses' => array() // Dodaj logikę pobierania kursów
        );
    }

    return new WP_REST_Response(array(
        'success' => true,
        'children' => $result
    ), 200);
}

/**
 * Harmonogram zajęć
 */
function ssm_api_get_schedule($request) {
    $user_id = ssm_api_get_user_id($request);
    $week_start = $request->get_param('week_start') ?? date('Y-m-d', strtotime('monday this week'));

    // Zwróć przykładowe dane
    return new WP_REST_Response(array(
        'success' => true,
        'schedule' => array(
            array(
                'id' => 1,
                'date' => $week_start,
                'startTime' => '10:00',
                'endTime' => '10:45',
                'poolName' => 'Basen Główny',
                'instructorName' => 'Anna Nowak',
                'childId' => 1,
                'childName' => 'Jan Kowalski',
                'status' => 'scheduled'
            )
        )
    ), 200);
}

/**
 * Płatności
 */
function ssm_api_get_payments($request) {
    $user_id = ssm_api_get_user_id($request);

    // Zwróć przykładowe dane
    return new WP_REST_Response(array(
        'success' => true,
        'payments' => array(
            array(
                'id' => 1,
                'amount' => 350.00,
                'currency' => 'PLN',
                'status' => 'pending',
                'dueDate' => date('Y-m-d', strtotime('+7 days')),
                'description' => 'Kurs pływania - styczeń 2026',
                'childName' => 'Jan Kowalski'
            )
        ),
        'summary' => array(
            'totalDue' => 350.00,
            'totalPaid' => 0,
            'currency' => 'PLN'
        )
    ), 200);
}

/**
 * Nieobecności
 */
function ssm_api_get_absences($request) {
    $user_id = ssm_api_get_user_id($request);

    return new WP_REST_Response(array(
        'success' => true,
        'absences' => array(),
        'makeups' => array()
    ), 200);
}

/**
 * Zgłoś nieobecność
 */
function ssm_api_report_absence($request) {
    $user_id = ssm_api_get_user_id($request);
    $params = $request->get_json_params();

    $session_id = intval($params['sessionId'] ?? 0);
    $child_id = intval($params['childId'] ?? 0);
    $reason = sanitize_textarea_field($params['reason'] ?? '');

    if (!$session_id || !$child_id) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Brakuje wymaganych danych'
        ), 400);
    }

    // Tutaj dodaj logikę zapisywania nieobecności

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Nieobecność została zgłoszona'
    ), 200);
}

/**
 * Rejestracja tokenu push
 */
function ssm_api_register_push_token($request) {
    $user_id = ssm_api_get_user_id($request);
    $params = $request->get_json_params();

    $token = sanitize_text_field($params['token'] ?? '');
    $platform = sanitize_text_field($params['platform'] ?? 'android');

    if (empty($token)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Brak tokenu'
        ), 400);
    }

    update_user_meta($user_id, 'ssm_push_token', $token);
    update_user_meta($user_id, 'ssm_push_platform', $platform);

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Token zarejestrowany'
    ), 200);
}
