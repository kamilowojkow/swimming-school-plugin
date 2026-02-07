<?php
/**
 * SSM REST API dla aplikacji mobilnych
 * Endpoint bazowy: /wp-json/ssm/v1/
 */

if (!defined('ABSPATH')) exit;

class SSM_REST_API {

    private static $instance = null;
    private $namespace = 'ssm/v1';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Rejestracja wszystkich endpointów
     */
    public function register_routes() {
        // ============================================
        // AUTHENTICATION
        // ============================================

        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array('required' => true, 'type' => 'string'),
                'password' => array('required' => true, 'type' => 'string'),
            )
        ));

        register_rest_route($this->namespace, '/auth/logout', array(
            'methods' => 'POST',
            'callback' => array($this, 'logout'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($this->namespace, '/auth/refresh', array(
            'methods' => 'POST',
            'callback' => array($this, 'refresh_token'),
            'permission_callback' => '__return_true',
            'args' => array(
                'refresh_token' => array('required' => true, 'type' => 'string'),
            )
        ));

        register_rest_route($this->namespace, '/auth/password-reset', array(
            'methods' => 'POST',
            'callback' => array($this, 'password_reset'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array('required' => true, 'type' => 'string'),
            )
        ));

        // ============================================
        // USER / PROFILE
        // ============================================

        register_rest_route($this->namespace, '/user/me', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_user'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($this->namespace, '/user/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_profile'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($this->namespace, '/user/password', array(
            'methods' => 'PUT',
            'callback' => array($this, 'change_password'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($this->namespace, '/user/push-token', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_push_token'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'token' => array('required' => true, 'type' => 'string'),
                'platform' => array('required' => true, 'enum' => array('ios', 'android')),
            )
        ));

        // ============================================
        // PARENT ENDPOINTS
        // ============================================

        // Dzieci
        register_rest_route($this->namespace, '/parent/children', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_children'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        register_rest_route($this->namespace, '/parent/children/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_child_details'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        // Zapisy i harmonogram
        register_rest_route($this->namespace, '/parent/enrollments', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_enrollments'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        register_rest_route($this->namespace, '/parent/schedule', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_parent_schedule'),
            'permission_callback' => array($this, 'check_parent_auth'),
            'args' => array(
                'date_from' => array('type' => 'string'),
                'date_to' => array('type' => 'string'),
            )
        ));

        // Płatności
        register_rest_route($this->namespace, '/parent/payments', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payments'),
            'permission_callback' => array($this, 'check_parent_auth'),
            'args' => array(
                'status' => array('type' => 'string', 'enum' => array('all', 'pending', 'paid', 'overdue')),
            )
        ));

        register_rest_route($this->namespace, '/parent/payments/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payment_details'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        // Nieobecności
        register_rest_route($this->namespace, '/parent/absences', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_absences'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        register_rest_route($this->namespace, '/parent/absences', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_absence'),
            'permission_callback' => array($this, 'check_parent_auth'),
            'args' => array(
                'enrollment_id' => array('required' => true, 'type' => 'integer'),
                'session_id' => array('required' => true, 'type' => 'integer'),
                'reason' => array('type' => 'string'),
            )
        ));

        // Odrabianie zajęć
        register_rest_route($this->namespace, '/parent/makeups', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_makeup_options'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        register_rest_route($this->namespace, '/parent/makeups', array(
            'methods' => 'POST',
            'callback' => array($this, 'book_makeup'),
            'permission_callback' => array($this, 'check_parent_auth'),
            'args' => array(
                'absence_id' => array('required' => true, 'type' => 'integer'),
                'session_id' => array('required' => true, 'type' => 'integer'),
            )
        ));

        // Osiągnięcia / Gamifikacja
        register_rest_route($this->namespace, '/parent/children/(?P<id>\d+)/achievements', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_child_achievements'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        register_rest_route($this->namespace, '/parent/children/(?P<id>\d+)/progress', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_child_progress'),
            'permission_callback' => array($this, 'check_parent_auth'),
        ));

        // ============================================
        // INSTRUCTOR ENDPOINTS
        // ============================================

        // Harmonogram
        register_rest_route($this->namespace, '/instructor/schedule', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_instructor_schedule'),
            'permission_callback' => array($this, 'check_instructor_auth'),
            'args' => array(
                'date_from' => array('type' => 'string'),
                'date_to' => array('type' => 'string'),
            )
        ));

        register_rest_route($this->namespace, '/instructor/sessions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_session_details'),
            'permission_callback' => array($this, 'check_instructor_auth'),
        ));

        // Obecność
        register_rest_route($this->namespace, '/instructor/sessions/(?P<id>\d+)/attendance', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_session_attendance'),
            'permission_callback' => array($this, 'check_instructor_auth'),
        ));

        register_rest_route($this->namespace, '/instructor/sessions/(?P<id>\d+)/attendance', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_attendance'),
            'permission_callback' => array($this, 'check_instructor_auth'),
            'args' => array(
                'attendance' => array('required' => true, 'type' => 'array'),
            )
        ));

        // Zastępstwa
        register_rest_route($this->namespace, '/instructor/substitutions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_substitutions'),
            'permission_callback' => array($this, 'check_instructor_auth'),
            'args' => array(
                'type' => array('type' => 'string', 'enum' => array('available', 'my_requests', 'my_taken')),
            )
        ));

        register_rest_route($this->namespace, '/instructor/substitutions', array(
            'methods' => 'POST',
            'callback' => array($this, 'request_substitution'),
            'permission_callback' => array($this, 'check_instructor_auth'),
            'args' => array(
                'session_id' => array('required' => true, 'type' => 'integer'),
                'reason' => array('type' => 'string'),
            )
        ));

        register_rest_route($this->namespace, '/instructor/substitutions/(?P<id>\d+)/take', array(
            'methods' => 'POST',
            'callback' => array($this, 'take_substitution'),
            'permission_callback' => array($this, 'check_instructor_auth'),
        ));

        // Wynagrodzenie
        register_rest_route($this->namespace, '/instructor/salary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_salary'),
            'permission_callback' => array($this, 'check_instructor_auth'),
            'args' => array(
                'month' => array('type' => 'integer'),
                'year' => array('type' => 'integer'),
            )
        ));

        // ============================================
        // NOTIFICATIONS
        // ============================================

        register_rest_route($this->namespace, '/notifications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_auth'),
            'args' => array(
                'limit' => array('type' => 'integer', 'default' => 20),
                'offset' => array('type' => 'integer', 'default' => 0),
                'unread_only' => array('type' => 'boolean', 'default' => false),
            )
        ));

        register_rest_route($this->namespace, '/notifications/unread-count', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_unread_count'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($this->namespace, '/notifications/(?P<id>\d+)/read', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_notification_read'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($this->namespace, '/notifications/read-all', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_all_notifications_read'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        // ============================================
        // COMMON / PUBLIC
        // ============================================

        register_rest_route($this->namespace, '/facilities', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_facilities'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/classes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_classes'),
            'permission_callback' => '__return_true',
        ));
    }

    // ============================================
    // PERMISSION CALLBACKS
    // ============================================

    public function check_auth($request) {
        $user_data = $this->get_authenticated_user($request);
        return $user_data !== false;
    }

    public function check_parent_auth($request) {
        $user_data = $this->get_authenticated_user($request);
        return $user_data && $user_data['type'] === 'parent';
    }

    public function check_instructor_auth($request) {
        $user_data = $this->get_authenticated_user($request);
        return $user_data && $user_data['type'] === 'instructor';
    }

    /**
     * Pobierz dane uwierzytelnionego użytkownika z tokena
     */
    private function get_authenticated_user($request) {
        $auth_header = $request->get_header('Authorization');

        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            return false;
        }

        $token = substr($auth_header, 7);
        return $this->validate_token($token);
    }

    /**
     * Walidacja tokena JWT
     */
    private function validate_token($token) {
        global $wpdb;

        // Sprawdź token w bazie
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_auth_tokens
             WHERE token = %s AND expires_at > NOW() AND revoked = 0",
            hash('sha256', $token)
        ));

        if (!$token_data) {
            return false;
        }

        return array(
            'type' => $token_data->user_type,
            'id' => $token_data->user_id,
            'token_id' => $token_data->id
        );
    }

    /**
     * Generowanie tokena JWT
     */
    private function generate_tokens($user_type, $user_id) {
        global $wpdb;

        $access_token = bin2hex(random_bytes(32));
        $refresh_token = bin2hex(random_bytes(32));

        // Token dostępu (24h)
        $wpdb->insert(
            $wpdb->prefix . 'ssm_auth_tokens',
            array(
                'user_type' => $user_type,
                'user_id' => $user_id,
                'token' => hash('sha256', $access_token),
                'refresh_token' => hash('sha256', $refresh_token),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );

        return array(
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'expires_in' => 86400, // 24h w sekundach
            'token_type' => 'Bearer'
        );
    }

    // ============================================
    // AUTHENTICATION HANDLERS
    // ============================================

    public function login($request) {
        global $wpdb;

        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');

        // Sprawdź najpierw w tabeli klientów (rodziców)
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
            $email
        ));

        if ($client && wp_check_password($password, $client->password, 0)) {
            $tokens = $this->generate_tokens('parent', $client->id);

            return rest_ensure_response(array(
                'success' => true,
                'user' => array(
                    'id' => $client->id,
                    'type' => 'parent',
                    'email' => $client->email,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'phone' => $client->phone,
                ),
                'tokens' => $tokens
            ));
        }

        // Sprawdź w tabeli instruktorów
        $instructor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE email = %s AND active = 1",
            $email
        ));

        if ($instructor && wp_check_password($password, $instructor->password, 0)) {
            $tokens = $this->generate_tokens('instructor', $instructor->id);

            return rest_ensure_response(array(
                'success' => true,
                'user' => array(
                    'id' => $instructor->id,
                    'type' => 'instructor',
                    'email' => $instructor->email,
                    'first_name' => $instructor->first_name,
                    'last_name' => $instructor->last_name,
                    'phone' => $instructor->phone,
                    'photo' => $instructor->photo,
                ),
                'tokens' => $tokens
            ));
        }

        return new WP_Error('invalid_credentials', 'Nieprawidłowy email lub hasło', array('status' => 401));
    }

    public function logout($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        // Unieważnij token
        $wpdb->update(
            $wpdb->prefix . 'ssm_auth_tokens',
            array('revoked' => 1),
            array('id' => $user_data['token_id'])
        );

        return rest_ensure_response(array('success' => true));
    }

    public function refresh_token($request) {
        global $wpdb;

        $refresh_token = $request->get_param('refresh_token');

        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_auth_tokens
             WHERE refresh_token = %s AND revoked = 0",
            hash('sha256', $refresh_token)
        ));

        if (!$token_data) {
            return new WP_Error('invalid_token', 'Nieprawidłowy refresh token', array('status' => 401));
        }

        // Unieważnij stary token
        $wpdb->update(
            $wpdb->prefix . 'ssm_auth_tokens',
            array('revoked' => 1),
            array('id' => $token_data->id)
        );

        // Wygeneruj nowe tokeny
        $tokens = $this->generate_tokens($token_data->user_type, $token_data->user_id);

        return rest_ensure_response(array(
            'success' => true,
            'tokens' => $tokens
        ));
    }

    public function password_reset($request) {
        global $wpdb;

        $email = sanitize_email($request->get_param('email'));

        // Sprawdź czy email istnieje
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT id, first_name FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
            $email
        ));

        $instructor = $wpdb->get_row($wpdb->prepare(
            "SELECT id, first_name FROM {$wpdb->prefix}ssm_instructors WHERE email = %s",
            $email
        ));

        if (!$client && !$instructor) {
            // Nie ujawniaj czy email istnieje
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Jeśli podany email istnieje w systemie, wysłaliśmy instrukcje resetowania hasła.'
            ));
        }

        // TODO: Implementacja wysyłania emaila z linkiem resetującym
        // Na razie zwracamy sukces

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Jeśli podany email istnieje w systemie, wysłaliśmy instrukcje resetowania hasła.'
        ));
    }

    // ============================================
    // USER HANDLERS
    // ============================================

    public function get_current_user($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        if ($user_data['type'] === 'parent') {
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT id, email, first_name, last_name, phone, address, created_at
                 FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
                $user_data['id']
            ));

            // Pobierz liczbę dzieci
            $children_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d",
                $user_data['id']
            ));

            return rest_ensure_response(array(
                'id' => $user->id,
                'type' => 'parent',
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'address' => $user->address,
                'children_count' => (int) $children_count,
                'member_since' => $user->created_at,
            ));
        } else {
            $user = $wpdb->get_row($wpdb->prepare(
                "SELECT id, email, first_name, last_name, phone, photo, specialization,
                        bio, hourly_rate, created_at
                 FROM {$wpdb->prefix}ssm_instructors WHERE id = %d",
                $user_data['id']
            ));

            return rest_ensure_response(array(
                'id' => $user->id,
                'type' => 'instructor',
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'photo' => $user->photo,
                'specialization' => $user->specialization,
                'bio' => $user->bio,
                'hourly_rate' => (float) $user->hourly_rate,
                'member_since' => $user->created_at,
            ));
        }
    }

    public function update_profile($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $table = $user_data['type'] === 'parent' ? 'ssm_clients' : 'ssm_instructors';

        $allowed_fields = array('first_name', 'last_name', 'phone', 'address');
        if ($user_data['type'] === 'instructor') {
            $allowed_fields[] = 'bio';
        }

        $update_data = array();
        foreach ($allowed_fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $update_data[$field] = sanitize_text_field($value);
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', 'Brak danych do aktualizacji', array('status' => 400));
        }

        $wpdb->update(
            $wpdb->prefix . $table,
            $update_data,
            array('id' => $user_data['id'])
        );

        return $this->get_current_user($request);
    }

    public function change_password($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $table = $user_data['type'] === 'parent' ? 'ssm_clients' : 'ssm_instructors';

        $current_password = $request->get_param('current_password');
        $new_password = $request->get_param('new_password');

        if (!$current_password || !$new_password) {
            return new WP_Error('missing_params', 'Wymagane aktualne i nowe hasło', array('status' => 400));
        }

        // Sprawdź aktualne hasło
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT password FROM {$wpdb->prefix}{$table} WHERE id = %d",
            $user_data['id']
        ));

        if (!wp_check_password($current_password, $user->password, 0)) {
            return new WP_Error('wrong_password', 'Nieprawidłowe aktualne hasło', array('status' => 400));
        }

        // Zaktualizuj hasło
        $wpdb->update(
            $wpdb->prefix . $table,
            array('password' => wp_hash_password($new_password)),
            array('id' => $user_data['id'])
        );

        return rest_ensure_response(array('success' => true, 'message' => 'Hasło zostało zmienione'));
    }

    public function register_push_token($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $token = sanitize_text_field($request->get_param('token'));
        $platform = sanitize_text_field($request->get_param('platform'));

        // Usuń stare tokeny dla tego urządzenia
        $wpdb->delete(
            $wpdb->prefix . 'ssm_push_tokens',
            array('token' => $token)
        );

        // Zapisz nowy token
        $wpdb->insert(
            $wpdb->prefix . 'ssm_push_tokens',
            array(
                'user_type' => $user_data['type'],
                'user_id' => $user_data['id'],
                'token' => $token,
                'platform' => $platform,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );

        return rest_ensure_response(array('success' => true));
    }

    // ============================================
    // PARENT HANDLERS
    // ============================================

    public function get_children($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $children = $wpdb->get_results($wpdb->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments e WHERE e.child_id = c.id AND e.status = 'active') as active_courses,
                   (SELECT COALESCE(cp.points, 0) FROM {$wpdb->prefix}ssm_child_points cp WHERE cp.child_id = c.id) as total_points
            FROM {$wpdb->prefix}ssm_children c
            JOIN {$wpdb->prefix}ssm_client_children cc ON c.id = cc.child_id
            WHERE cc.client_id = %d
            ORDER BY c.first_name
        ", $user_data['id']));

        $result = array();
        foreach ($children as $child) {
            $result[] = array(
                'id' => $child->id,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'birth_date' => $child->date_of_birth,
                'age' => $this->calculate_age($child->date_of_birth),
                'gender' => $child->gender,
                'photo' => $child->photo,
                'swimming_level' => $child->swimming_level,
                'medical_notes' => $child->medical_notes,
                'active_courses' => (int) $child->active_courses,
                'total_points' => (int) ($child->total_points ?? 0),
            );
        }

        return rest_ensure_response($result);
    }

    public function get_child_details($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $child_id = $request->get_param('id');

        // Sprawdź czy dziecko należy do rodzica
        $belongs = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$wpdb->prefix}ssm_client_children
             WHERE client_id = %d AND child_id = %d",
            $user_data['id'], $child_id
        ));

        if (!$belongs) {
            return new WP_Error('not_found', 'Dziecko nie znalezione', array('status' => 404));
        }

        $child = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_children WHERE id = %d",
            $child_id
        ));

        // Pobierz aktywne zapisy
        $enrollments = $wpdb->get_results($wpdb->prepare("
            SELECT e.*, c.name as class_name, c.level, f.name as facility_name,
                   CONCAT(i.first_name, ' ', i.last_name) as instructor_name
            FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
            WHERE e.child_id = %d AND e.status = 'active'
        ", $child_id));

        // Pobierz ostatnie osiągnięcia
        $achievements = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, ca.earned_at
            FROM {$wpdb->prefix}ssm_child_achievements ca
            JOIN {$wpdb->prefix}ssm_achievements a ON ca.achievement_id = a.id
            WHERE ca.child_id = %d
            ORDER BY ca.earned_at DESC
            LIMIT 5
        ", $child_id));

        return rest_ensure_response(array(
            'id' => $child->id,
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'birth_date' => $child->date_of_birth,
            'age' => $this->calculate_age($child->date_of_birth),
            'gender' => $child->gender,
            'photo' => $child->photo,
            'swimming_level' => $child->swimming_level,
            'medical_notes' => $child->medical_notes,
            'enrollments' => $enrollments,
            'recent_achievements' => $achievements,
        ));
    }

    public function get_enrollments($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $enrollments = $wpdb->get_results($wpdb->prepare("
            SELECT e.*,
                   ch.first_name as child_first_name, ch.last_name as child_last_name,
                   c.name as class_name, c.level, c.day_of_week, c.time_start, c.time_end,
                   f.name as facility_name,
                   CONCAT(i.first_name, ' ', i.last_name) as instructor_name
            FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
            JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
            WHERE cc.client_id = %d
            ORDER BY e.status = 'active' DESC, c.day_of_week, c.time_start
        ", $user_data['id']));

        return rest_ensure_response($enrollments);
    }

    public function get_parent_schedule($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $date_from = $request->get_param('date_from') ?: date('Y-m-d');
        $date_to = $request->get_param('date_to') ?: date('Y-m-d', strtotime('+14 days'));

        $sessions = $wpdb->get_results($wpdb->prepare("
            SELECT s.*,
                   c.name as class_name, c.level,
                   ch.first_name as child_first_name, ch.last_name as child_last_name,
                   f.name as facility_name, f.address as facility_address,
                   CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                   a.status as attendance_status
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            JOIN {$wpdb->prefix}ssm_enrollments e ON e.class_id = c.id AND e.status = 'active'
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            LEFT JOIN {$wpdb->prefix}ssm_instructors i ON s.instructor_id = i.id
            LEFT JOIN {$wpdb->prefix}ssm_attendance a ON a.session_id = s.id AND a.enrollment_id = e.id
            WHERE cc.client_id = %d
              AND s.session_date BETWEEN %s AND %s
              AND s.status != 'cancelled'
            ORDER BY s.session_date, s.time_start
        ", $user_data['id'], $date_from, $date_to));

        return rest_ensure_response($sessions);
    }

    public function get_payments($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $status = $request->get_param('status') ?: 'all';

        $where = array($wpdb->prepare("p.client_id = %d", $user_data['id']));

        if ($status === 'pending') {
            $where[] = "p.status IN ('pending', 'partial')";
        } elseif ($status === 'paid') {
            $where[] = "p.status = 'paid'";
        } elseif ($status === 'overdue') {
            $where[] = "p.status IN ('pending', 'partial') AND p.due_date < CURDATE()";
        }

        $payments = $wpdb->get_results("
            SELECT p.*,
                   (p.total_amount - p.paid_amount) as remaining_amount
            FROM {$wpdb->prefix}ssm_payments p
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.due_date DESC
        ");

        return rest_ensure_response($payments);
    }

    public function get_payment_details($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $payment_id = $request->get_param('id');

        $payment = $wpdb->get_row($wpdb->prepare("
            SELECT p.*
            FROM {$wpdb->prefix}ssm_payments p
            WHERE p.id = %d AND p.client_id = %d
        ", $payment_id, $user_data['id']));

        if (!$payment) {
            return new WP_Error('not_found', 'Płatność nie znaleziona', array('status' => 404));
        }

        // Pobierz historię wpłat
        $transactions = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}ssm_payment_transactions
            WHERE payment_id = %d
            ORDER BY created_at DESC
        ", $payment_id));

        $payment->transactions = $transactions;
        $payment->remaining_amount = $payment->total_amount - $payment->paid_amount;

        return rest_ensure_response($payment);
    }

    public function get_absences($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $absences = $wpdb->get_results($wpdb->prepare("
            SELECT a.*,
                   s.session_date, s.time_start,
                   c.name as class_name,
                   ch.first_name as child_first_name, ch.last_name as child_last_name
            FROM {$wpdb->prefix}ssm_absences a
            JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
            JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
            JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
            WHERE cc.client_id = %d
            ORDER BY s.session_date DESC
            LIMIT 50
        ", $user_data['id']));

        return rest_ensure_response($absences);
    }

    public function report_absence($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $enrollment_id = $request->get_param('enrollment_id');
        $session_id = $request->get_param('session_id');
        $reason = sanitize_textarea_field($request->get_param('reason'));

        // Sprawdź czy zapis należy do rodzica
        $enrollment = $wpdb->get_row($wpdb->prepare("
            SELECT e.* FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
            WHERE e.id = %d AND cc.client_id = %d
        ", $enrollment_id, $user_data['id']));

        if (!$enrollment) {
            return new WP_Error('not_found', 'Zapis nie znaleziony', array('status' => 404));
        }

        // Sprawdź czy sesja istnieje i jest w przyszłości
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_sessions WHERE id = %d AND session_date >= CURDATE()",
            $session_id
        ));

        if (!$session) {
            return new WP_Error('invalid_session', 'Nie można zgłosić nieobecności na przeszłe zajęcia', array('status' => 400));
        }

        // Sprawdź czy nieobecność już nie istnieje
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_absences WHERE enrollment_id = %d AND session_id = %d",
            $enrollment_id, $session_id
        ));

        if ($exists) {
            return new WP_Error('already_reported', 'Nieobecność została już zgłoszona', array('status' => 400));
        }

        // Zapisz nieobecność
        $wpdb->insert(
            $wpdb->prefix . 'ssm_absences',
            array(
                'enrollment_id' => $enrollment_id,
                'session_id' => $session_id,
                'reason' => $reason,
                'status' => 'reported',
                'can_makeup' => 1,
                'reported_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s')
        );

        return rest_ensure_response(array(
            'success' => true,
            'absence_id' => $wpdb->insert_id,
            'message' => 'Nieobecność została zgłoszona'
        ));
    }

    public function get_makeup_options($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        // Pobierz nieobecności z możliwością odrobienia
        $absences = $wpdb->get_results($wpdb->prepare("
            SELECT a.*,
                   s.session_date as missed_date,
                   c.name as class_name, c.id as class_id,
                   ch.first_name as child_name
            FROM {$wpdb->prefix}ssm_absences a
            JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
            JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
            JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
            WHERE cc.client_id = %d
              AND a.can_makeup = 1
              AND a.makeup_session_id IS NULL
              AND a.status = 'reported'
        ", $user_data['id']));

        // Dla każdej nieobecności znajdź dostępne terminy
        foreach ($absences as &$absence) {
            $absence->available_sessions = $wpdb->get_results($wpdb->prepare("
                SELECT s.*,
                       (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments e2
                        WHERE e2.class_id = s.class_id AND e2.status = 'active') as enrolled_count,
                       c.max_participants
                FROM {$wpdb->prefix}ssm_sessions s
                JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                WHERE c.id = %d
                  AND s.session_date > CURDATE()
                  AND s.status = 'scheduled'
                HAVING enrolled_count < c.max_participants
                ORDER BY s.session_date
                LIMIT 5
            ", $absence->class_id));
        }

        return rest_ensure_response($absences);
    }

    public function book_makeup($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $absence_id = $request->get_param('absence_id');
        $session_id = $request->get_param('session_id');

        // Sprawdź czy nieobecność należy do rodzica i można odrobić
        $absence = $wpdb->get_row($wpdb->prepare("
            SELECT a.* FROM {$wpdb->prefix}ssm_absences a
            JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
            WHERE a.id = %d AND cc.client_id = %d
              AND a.can_makeup = 1 AND a.makeup_session_id IS NULL
        ", $absence_id, $user_data['id']));

        if (!$absence) {
            return new WP_Error('not_found', 'Nieobecność nie znaleziona lub nie można odrobić', array('status' => 404));
        }

        // Zapisz odrobienie
        $wpdb->update(
            $wpdb->prefix . 'ssm_absences',
            array(
                'makeup_session_id' => $session_id,
                'makeup_booked_at' => current_time('mysql')
            ),
            array('id' => $absence_id)
        );

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Odrabianie zajęć zostało zarezerwowane'
        ));
    }

    public function get_child_achievements($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $child_id = $request->get_param('id');

        // Sprawdź czy dziecko należy do rodzica
        $belongs = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d AND child_id = %d",
            $user_data['id'], $child_id
        ));

        if (!$belongs) {
            return new WP_Error('not_found', 'Dziecko nie znalezione', array('status' => 404));
        }

        $achievements = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, ca.earned_at,
                   CONCAT(i.first_name, ' ', i.last_name) as awarded_by_name
            FROM {$wpdb->prefix}ssm_child_achievements ca
            JOIN {$wpdb->prefix}ssm_achievements a ON ca.achievement_id = a.id
            LEFT JOIN {$wpdb->prefix}ssm_instructors i ON ca.awarded_by = i.id
            WHERE ca.child_id = %d
            ORDER BY ca.earned_at DESC
        ", $child_id));

        $total_points = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(points, 0) FROM {$wpdb->prefix}ssm_child_points WHERE child_id = %d",
            $child_id
        ));

        return rest_ensure_response(array(
            'achievements' => $achievements,
            'total_points' => (int) ($total_points ?? 0),
            'achievements_count' => count($achievements)
        ));
    }

    public function get_child_progress($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $child_id = $request->get_param('id');

        // Sprawdź czy dziecko należy do rodzica
        $belongs = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$wpdb->prefix}ssm_client_children WHERE client_id = %d AND child_id = %d",
            $user_data['id'], $child_id
        ));

        if (!$belongs) {
            return new WP_Error('not_found', 'Dziecko nie znalezione', array('status' => 404));
        }

        // Frekwencja
        $attendance_stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_sessions,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent
            FROM {$wpdb->prefix}ssm_attendance a
            JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
            WHERE e.child_id = %d
        ", $child_id));

        // Poziom pływania
        $child = $wpdb->get_row($wpdb->prepare(
            "SELECT swimming_level FROM {$wpdb->prefix}ssm_children WHERE id = %d",
            $child_id
        ));

        return rest_ensure_response(array(
            'swimming_level' => $child->swimming_level,
            'attendance' => array(
                'total' => (int) $attendance_stats->total_sessions,
                'attended' => (int) $attendance_stats->attended,
                'absent' => (int) $attendance_stats->absent,
                'rate' => $attendance_stats->total_sessions > 0
                    ? round(($attendance_stats->attended / $attendance_stats->total_sessions) * 100, 1)
                    : 0
            )
        ));
    }

    // ============================================
    // INSTRUCTOR HANDLERS
    // ============================================

    public function get_instructor_schedule($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $date_from = $request->get_param('date_from') ?: date('Y-m-d');
        $date_to = $request->get_param('date_to') ?: date('Y-m-d', strtotime('+14 days'));

        $sessions = $wpdb->get_results($wpdb->prepare("
            SELECT s.*,
                   c.name as class_name, c.level, c.max_participants,
                   f.name as facility_name, f.address as facility_address,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments e
                    WHERE e.class_id = c.id AND e.status = 'active') as enrolled_count,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance a
                    WHERE a.session_id = s.id) as attendance_marked
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            WHERE s.instructor_id = %d
              AND s.session_date BETWEEN %s AND %s
              AND s.status != 'cancelled'
            ORDER BY s.session_date, s.time_start
        ", $user_data['id'], $date_from, $date_to));

        return rest_ensure_response($sessions);
    }

    public function get_session_details($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $session_id = $request->get_param('id');

        $session = $wpdb->get_row($wpdb->prepare("
            SELECT s.*,
                   c.name as class_name, c.level, c.max_participants, c.description,
                   f.name as facility_name, f.address as facility_address
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            WHERE s.id = %d AND s.instructor_id = %d
        ", $session_id, $user_data['id']));

        if (!$session) {
            return new WP_Error('not_found', 'Sesja nie znaleziona', array('status' => 404));
        }

        // Pobierz listę zapisanych dzieci
        $participants = $wpdb->get_results($wpdb->prepare("
            SELECT e.id as enrollment_id,
                   ch.id as child_id, ch.first_name, ch.last_name, ch.date_of_birth,
                   ch.swimming_level, ch.medical_notes,
                   a.status as attendance_status, a.notes as attendance_notes
            FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            LEFT JOIN {$wpdb->prefix}ssm_attendance a ON a.enrollment_id = e.id AND a.session_id = %d
            WHERE e.class_id = %d AND e.status = 'active'
            ORDER BY ch.first_name
        ", $session_id, $session->class_id));

        $session->participants = $participants;

        return rest_ensure_response($session);
    }

    public function get_session_attendance($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $session_id = $request->get_param('id');

        // Sprawdź czy sesja należy do instruktora
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.id as class_id FROM {$wpdb->prefix}ssm_sessions s
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             WHERE s.id = %d AND s.instructor_id = %d",
            $session_id, $user_data['id']
        ));

        if (!$session) {
            return new WP_Error('not_found', 'Sesja nie znaleziona', array('status' => 404));
        }

        $attendance = $wpdb->get_results($wpdb->prepare("
            SELECT e.id as enrollment_id,
                   ch.id as child_id, ch.first_name, ch.last_name,
                   COALESCE(a.status, 'unmarked') as status,
                   a.notes
            FROM {$wpdb->prefix}ssm_enrollments e
            JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
            LEFT JOIN {$wpdb->prefix}ssm_attendance a ON a.enrollment_id = e.id AND a.session_id = %d
            WHERE e.class_id = %d AND e.status = 'active'
            ORDER BY ch.first_name
        ", $session_id, $session->class_id));

        return rest_ensure_response($attendance);
    }

    public function save_attendance($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $session_id = $request->get_param('id');
        $attendance_data = $request->get_param('attendance');

        // Sprawdź czy sesja należy do instruktora
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_sessions WHERE id = %d AND instructor_id = %d",
            $session_id, $user_data['id']
        ));

        if (!$session) {
            return new WP_Error('not_found', 'Sesja nie znaleziona', array('status' => 404));
        }

        foreach ($attendance_data as $item) {
            $enrollment_id = intval($item['enrollment_id']);
            $status = sanitize_text_field($item['status']);
            $notes = isset($item['notes']) ? sanitize_textarea_field($item['notes']) : '';

            // Sprawdź czy obecność już istnieje
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ssm_attendance WHERE session_id = %d AND enrollment_id = %d",
                $session_id, $enrollment_id
            ));

            if ($existing) {
                $wpdb->update(
                    $wpdb->prefix . 'ssm_attendance',
                    array('status' => $status, 'notes' => $notes, 'marked_at' => current_time('mysql')),
                    array('id' => $existing)
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'ssm_attendance',
                    array(
                        'session_id' => $session_id,
                        'enrollment_id' => $enrollment_id,
                        'status' => $status,
                        'notes' => $notes,
                        'marked_by' => $user_data['id'],
                        'marked_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%s', '%d', '%s')
                );
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Obecność została zapisana'
        ));
    }

    public function get_substitutions($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $type = $request->get_param('type') ?: 'available';

        if ($type === 'available') {
            // Dostępne zastępstwa (zgłoszone przez innych)
            $substitutions = $wpdb->get_results($wpdb->prepare("
                SELECT u.*, s.session_date, s.time_start, s.time_end,
                       c.name as class_name, f.name as facility_name,
                       CONCAT(i.first_name, ' ', i.last_name) as instructor_name
                FROM {$wpdb->prefix}ssm_instructor_unavailability u
                JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
                JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
                JOIN {$wpdb->prefix}ssm_instructors i ON u.instructor_id = i.id
                WHERE u.instructor_id != %d
                  AND u.replacement_instructor_id IS NULL
                  AND s.session_date >= CURDATE()
                ORDER BY s.session_date, s.time_start
            ", $user_data['id']));
        } elseif ($type === 'my_requests') {
            // Moje zgłoszone zastępstwa
            $substitutions = $wpdb->get_results($wpdb->prepare("
                SELECT u.*, s.session_date, s.time_start, s.time_end,
                       c.name as class_name, f.name as facility_name,
                       CONCAT(i.first_name, ' ', i.last_name) as replacement_name
                FROM {$wpdb->prefix}ssm_instructor_unavailability u
                JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
                JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
                LEFT JOIN {$wpdb->prefix}ssm_instructors i ON u.replacement_instructor_id = i.id
                WHERE u.instructor_id = %d
                  AND s.session_date >= CURDATE()
                ORDER BY s.session_date, s.time_start
            ", $user_data['id']));
        } else {
            // Zastępstwa które wziąłem
            $substitutions = $wpdb->get_results($wpdb->prepare("
                SELECT u.*, s.session_date, s.time_start, s.time_end,
                       c.name as class_name, f.name as facility_name,
                       CONCAT(i.first_name, ' ', i.last_name) as original_instructor_name
                FROM {$wpdb->prefix}ssm_instructor_unavailability u
                JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
                JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
                JOIN {$wpdb->prefix}ssm_instructors i ON u.instructor_id = i.id
                WHERE u.replacement_instructor_id = %d
                  AND s.session_date >= CURDATE()
                ORDER BY s.session_date, s.time_start
            ", $user_data['id']));
        }

        return rest_ensure_response($substitutions);
    }

    public function request_substitution($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $session_id = $request->get_param('session_id');
        $reason = sanitize_textarea_field($request->get_param('reason'));

        // Sprawdź czy sesja należy do instruktora
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_sessions WHERE id = %d AND instructor_id = %d AND session_date >= CURDATE()",
            $session_id, $user_data['id']
        ));

        if (!$session) {
            return new WP_Error('not_found', 'Sesja nie znaleziona', array('status' => 404));
        }

        // Sprawdź czy już nie zgłoszono
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ssm_instructor_unavailability WHERE session_id = %d AND instructor_id = %d",
            $session_id, $user_data['id']
        ));

        if ($exists) {
            return new WP_Error('already_exists', 'Zastępstwo zostało już zgłoszone', array('status' => 400));
        }

        $wpdb->insert(
            $wpdb->prefix . 'ssm_instructor_unavailability',
            array(
                'instructor_id' => $user_data['id'],
                'session_id' => $session_id,
                'reason' => $reason,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );

        $unavailability_id = $wpdb->insert_id;

        // Wyślij powiadomienia do innych instruktorów
        do_action('ssm_substitution_created', $unavailability_id, $user_data['id']);

        return rest_ensure_response(array(
            'success' => true,
            'id' => $unavailability_id,
            'message' => 'Prośba o zastępstwo została zgłoszona'
        ));
    }

    public function take_substitution($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);
        $unavailability_id = $request->get_param('id');

        // Sprawdź czy zastępstwo istnieje i jest dostępne
        $unavailability = $wpdb->get_row($wpdb->prepare("
            SELECT u.*, s.session_date
            FROM {$wpdb->prefix}ssm_instructor_unavailability u
            JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
            WHERE u.id = %d
              AND u.instructor_id != %d
              AND u.replacement_instructor_id IS NULL
              AND s.session_date >= CURDATE()
        ", $unavailability_id, $user_data['id']));

        if (!$unavailability) {
            return new WP_Error('not_available', 'Zastępstwo nie jest dostępne', array('status' => 400));
        }

        // Przypisz zastępstwo
        $wpdb->update(
            $wpdb->prefix . 'ssm_instructor_unavailability',
            array(
                'replacement_instructor_id' => $user_data['id'],
                'taken_at' => current_time('mysql')
            ),
            array('id' => $unavailability_id)
        );

        // Zaktualizuj sesję
        $wpdb->update(
            $wpdb->prefix . 'ssm_sessions',
            array('instructor_id' => $user_data['id']),
            array('id' => $unavailability->session_id)
        );

        // Wyślij powiadomienia
        do_action('ssm_substitution_taken', $unavailability_id, $user_data['id']);
        do_action('ssm_instructor_change', $unavailability->session_id, $unavailability->instructor_id, $user_data['id']);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Zastępstwo zostało przyjęte'
        ));
    }

    public function get_salary($request) {
        global $wpdb;

        $user_data = $this->get_authenticated_user($request);

        $month = $request->get_param('month') ?: date('n');
        $year = $request->get_param('year') ?: date('Y');

        $first_day = sprintf('%04d-%02d-01', $year, $month);
        $last_day = date('Y-m-t', strtotime($first_day));

        // Pobierz stawkę instruktora
        $instructor = $wpdb->get_row($wpdb->prepare(
            "SELECT hourly_rate FROM {$wpdb->prefix}ssm_instructors WHERE id = %d",
            $user_data['id']
        ));

        // Przeprowadzone zajęcia
        $sessions = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, c.name as class_name,
                   TIMESTAMPDIFF(MINUTE, s.time_start, s.time_end) as duration_minutes
            FROM {$wpdb->prefix}ssm_sessions s
            JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
            WHERE s.instructor_id = %d
              AND s.session_date BETWEEN %s AND %s
              AND s.status = 'completed'
            ORDER BY s.session_date, s.time_start
        ", $user_data['id'], $first_day, $last_day));

        $total_hours = 0;
        foreach ($sessions as $session) {
            $total_hours += $session->duration_minutes / 60;
        }

        $total_salary = $total_hours * $instructor->hourly_rate;

        return rest_ensure_response(array(
            'month' => (int) $month,
            'year' => (int) $year,
            'hourly_rate' => (float) $instructor->hourly_rate,
            'total_hours' => round($total_hours, 2),
            'total_salary' => round($total_salary, 2),
            'sessions_count' => count($sessions),
            'sessions' => $sessions
        ));
    }

    // ============================================
    // NOTIFICATIONS HANDLERS
    // ============================================

    public function get_notifications($request) {
        global $wpdb;
        $user_data = $this->get_authenticated_user($request);

        $recipient_type = $user_data['type'] === 'parent' ? 'parent' : 'instructor';
        $recipient_id = $user_data['id'];

        // Debug: count all notifications for this user (with multiple recipient types)
        $debug_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_notifications
             WHERE ((recipient_type = %s AND recipient_id = %d)
                    OR (recipient_type = 'client' AND recipient_id = %d)
                    OR (recipient_type = 'user' AND recipient_id = %d))",
            $recipient_type,
            $recipient_id,
            $recipient_id,
            $recipient_id
        ));

        $notification_system = ssm_notification_system();
        $notifications = $notification_system->get_notifications(
            $recipient_type,
            $recipient_id,
            array(
                'limit' => $request->get_param('limit'),
                'offset' => $request->get_param('offset'),
                'unread_only' => $request->get_param('unread_only')
            )
        );

        return rest_ensure_response(array(
            'notifications' => $notifications,
            'debug' => array(
                'user_type' => $user_data['type'],
                'user_id' => $user_data['id'],
                'recipient_type' => $recipient_type,
                'recipient_id' => $recipient_id,
                'total_in_db' => intval($debug_count),
                'returned_count' => count($notifications)
            )
        ));
    }

    public function get_unread_count($request) {
        $user_data = $this->get_authenticated_user($request);

        $notification_system = ssm_notification_system();
        $count = $notification_system->get_unread_count(
            $user_data['type'] === 'parent' ? 'parent' : 'instructor',
            $user_data['id']
        );

        return rest_ensure_response(array('unread_count' => $count));
    }

    public function mark_notification_read($request) {
        $user_data = $this->get_authenticated_user($request);
        $notification_id = $request->get_param('id');

        $notification_system = ssm_notification_system();
        $notification_system->mark_as_read(
            $notification_id,
            $user_data['type'] === 'parent' ? 'parent' : 'instructor',
            $user_data['id']
        );

        return rest_ensure_response(array('success' => true));
    }

    public function mark_all_notifications_read($request) {
        $user_data = $this->get_authenticated_user($request);

        $notification_system = ssm_notification_system();
        $notification_system->mark_all_as_read(
            $user_data['type'] === 'parent' ? 'parent' : 'instructor',
            $user_data['id']
        );

        return rest_ensure_response(array('success' => true));
    }

    // ============================================
    // COMMON HANDLERS
    // ============================================

    public function get_facilities($request) {
        global $wpdb;

        $facilities = $wpdb->get_results(
            "SELECT id, name, address, phone, email, description
             FROM {$wpdb->prefix}ssm_facilities
             WHERE active = 1
             ORDER BY name"
        );

        return rest_ensure_response($facilities);
    }

    public function get_classes($request) {
        global $wpdb;

        $classes = $wpdb->get_results("
            SELECT c.*,
                   f.name as facility_name,
                   CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments e
                    WHERE e.class_id = c.id AND e.status = 'active') as enrolled_count
            FROM {$wpdb->prefix}ssm_classes c
            LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
            LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
            WHERE c.active = 1
            ORDER BY c.name
        ");

        return rest_ensure_response($classes);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function calculate_age($birth_date) {
        $birth = new DateTime($birth_date);
        $today = new DateTime();
        return $birth->diff($today)->y;
    }
}

// Inicjalizacja
function ssm_rest_api() {
    return SSM_REST_API::get_instance();
}

add_action('plugins_loaded', 'ssm_rest_api');
