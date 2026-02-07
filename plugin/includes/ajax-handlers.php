<?php
// AJAX Handlers - includuj ten plik w głównym pliku
if (!defined('ABSPATH')) exit;

// Include w głównym pliku: require_once SSM_PLUGIN_DIR . 'includes/ajax-handlers.php';

// ========================================
// KLIENCI (RODZICE)
// ========================================

add_action('wp_ajax_ssm_save_client', 'ssm_ajax_save_client');
function ssm_ajax_save_client() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ssm_clients';

    $email = sanitize_email($_POST['email']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);

    $data = array(
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
        'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : ''
    );

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing client
        $client_id = intval($_POST['id']);
        $wpdb->update($table, $data, array('id' => $client_id));

        // If client has user_id, update WordPress user info too
        $client = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table WHERE id = %d", $client_id));
        if ($client && $client->user_id) {
            wp_update_user(array(
                'ID' => $client->user_id,
                'user_email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ));
        }

        wp_send_json_success('Rodzic zaktualizowany');
    } else {
        // Create new client
        $data['created_at'] = current_time('mysql');

        // Check if WordPress user with this email already exists
        $existing_user = get_user_by('email', $email);

        if ($existing_user) {
            // User exists - link to existing account
            $data['user_id'] = $existing_user->ID;

            // Add ssm_parent role if not already
            $user = new WP_User($existing_user->ID);
            if (!in_array('ssm_parent', $user->roles)) {
                $user->add_role('ssm_parent');
            }
        } else {
            // Create new WordPress user
            $username = ssm_generate_unique_username($email, $first_name, $last_name);
            $password = wp_generate_password(12, true, true);

            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name,
                'role' => 'ssm_parent'
            ));

            if (!is_wp_error($user_id)) {
                $data['user_id'] = $user_id;

                // Send email with login credentials
                ssm_send_welcome_email($email, $username, $password, $first_name);
            }
        }

        $wpdb->insert($table, $data);
        wp_send_json_success('Rodzic dodany');
    }
}

/**
 * Generate unique username from email or name
 */
function ssm_generate_unique_username($email, $first_name, $last_name) {
    // Try email prefix first
    $base_username = sanitize_user(strtok($email, '@'), true);

    if (empty($base_username)) {
        // Fallback to name
        $base_username = sanitize_user(strtolower($first_name . '.' . $last_name), true);
    }

    if (empty($base_username)) {
        $base_username = 'rodzic';
    }

    $username = $base_username;
    $counter = 1;

    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }

    return $username;
}

/**
 * Send welcome email to new parent with login credentials
 */
function ssm_send_welcome_email($email, $username, $password, $first_name) {
    $site_name = get_bloginfo('name');
    $login_url = wp_login_url();

    $subject = sprintf('[%s] Twoje konto rodzica zostało utworzone', $site_name);

    $message = sprintf(
        "Cześć %s,\n\n" .
        "Twoje konto rodzica w systemie %s zostało utworzone.\n\n" .
        "Dane do logowania:\n" .
        "Login: %s\n" .
        "Hasło: %s\n\n" .
        "Zaloguj się tutaj: %s\n\n" .
        "Zalecamy zmianę hasła po pierwszym logowaniu.\n\n" .
        "Pozdrawiamy,\n%s",
        $first_name,
        $site_name,
        $username,
        $password,
        $login_url,
        $site_name
    );

    wp_mail($email, $subject, $message);
}

add_action('wp_ajax_ssm_delete_client', 'ssm_ajax_delete_client');
function ssm_ajax_delete_client() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'ssm_clients', array('id' => intval($_POST['id'])));
    wp_send_json_success('Usunięto');
}

// ========================================
// DZIECI
// ========================================

add_action('wp_ajax_ssm_save_child', 'ssm_ajax_save_child');
function ssm_ajax_save_child() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_children';
    
    $data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'date_of_birth' => isset($_POST['date_of_birth']) ? sanitize_text_field($_POST['date_of_birth']) : null,
        'medical_notes' => isset($_POST['medical_notes']) ? sanitize_textarea_field($_POST['medical_notes']) : '',
        'skills_description' => isset($_POST['skills_description']) ? sanitize_textarea_field($_POST['skills_description']) : '',
        'active' => isset($_POST['active']) ? 1 : 0
    );
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
        wp_send_json_success('Dziecko zaktualizowane');
    } else {
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($table, $data);
        wp_send_json_success('Dziecko dodane');
    }
}

add_action('wp_ajax_ssm_delete_child', 'ssm_ajax_delete_child');
function ssm_ajax_delete_child() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'ssm_children', array('id' => intval($_POST['id'])));
    wp_send_json_success('Usunięto');
}

add_action('wp_ajax_ssm_assign_parent', 'ssm_ajax_assign_parent');
function ssm_ajax_assign_parent() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_client_children';
    
    $data = array(
        'client_id' => intval($_POST['client_id']),
        'child_id' => intval($_POST['child_id']),
        'relationship' => sanitize_text_field($_POST['relationship']),
        'primary_contact' => intval($_POST['primary_contact']),
        'created_at' => current_time('mysql')
    );
    
    $result = $wpdb->insert($table, $data);
    
    if ($result) {
        wp_send_json_success('Rodzic przypisany');
    } else {
        wp_send_json_error('Błąd: ' . $wpdb->last_error);
    }
}

add_action('wp_ajax_ssm_remove_parent', 'ssm_ajax_remove_parent');
function ssm_ajax_remove_parent() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'ssm_client_children', array('id' => intval($_POST['id'])));
    wp_send_json_success('Usunięto');
}

add_action('wp_ajax_ssm_get_child_parents', 'ssm_ajax_get_child_parents');
function ssm_ajax_get_child_parents() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $child_id = intval($_POST['child_id']);
    
    $parents = $wpdb->get_results($wpdb->prepare(
        "SELECT cc.id, cc.relationship, cc.primary_contact,
                CONCAT(c.first_name, ' ', c.last_name) as name
         FROM {$wpdb->prefix}ssm_client_children cc
         LEFT JOIN {$wpdb->prefix}ssm_clients c ON cc.client_id = c.id
         WHERE cc.child_id = %d",
        $child_id
    ));
    
    wp_send_json_success($parents);
}

// ========================================
// OBIEKTY (BASENY)
// ========================================

add_action('wp_ajax_ssm_save_facility', 'ssm_ajax_save_facility');
function ssm_ajax_save_facility() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_facilities';
    
    $data = array(
        'name' => sanitize_text_field($_POST['name']),
        'address' => isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '',
        'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : ''
    );
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
        wp_send_json_success('Obiekt zaktualizowany');
    } else {
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($table, $data);
        wp_send_json_success('Obiekt dodany');
    }
}

add_action('wp_ajax_ssm_delete_facility', 'ssm_ajax_delete_facility');
function ssm_ajax_delete_facility() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'ssm_facilities', array('id' => intval($_POST['id'])));
    wp_send_json_success('Usunięto');
}

// ========================================
// INSTRUKTORZY
// ========================================

add_action('wp_ajax_ssm_save_instructor', 'ssm_ajax_save_instructor');
function ssm_ajax_save_instructor() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_instructors';
    
    $data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
        'specialization' => isset($_POST['specialization']) ? sanitize_text_field($_POST['specialization']) : '',
        'hourly_rate' => isset($_POST['hourly_rate']) ? floatval($_POST['hourly_rate']) : 0,
        'photo' => isset($_POST['photo']) ? esc_url_raw($_POST['photo']) : '',
        'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
        'bio' => isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '',
        'active' => isset($_POST['active']) ? 1 : 0
    );
    
    $instructor_id = null;
    $user_id = null;
    
    // Tworzenie/aktualizacja instruktora
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $instructor_id = intval($_POST['id']);
        $result = $wpdb->update($table, $data, array('id' => $instructor_id));
        if ($result === false) {
            wp_send_json_error('Błąd aktualizacji: ' . $wpdb->last_error);
            return;
        }
        $message = 'Instruktor zaktualizowany';
    } else {
        $data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table, $data);
        if ($result === false) {
            wp_send_json_error('Błąd dodawania instruktora: ' . $wpdb->last_error);
            return;
        }
        $instructor_id = $wpdb->insert_id;
        if (!$instructor_id) {
            wp_send_json_error('Błąd: Nie otrzymano ID nowego instruktora');
            return;
        }
        $message = 'Instruktor dodany (ID: ' . $instructor_id . ')';
    }
    
    // Tworzenie konta WordPress
    if (isset($_POST['create_wordpress_user']) && $_POST['create_wordpress_user'] == '1') {
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $email = $data['email'];
        
        // Sprawdź czy użytkownik już istnieje
        if (username_exists($username)) {
            wp_send_json_error('Login "' . $username . '" jest już zajęty');
            return;
        }
        
        if (email_exists($email)) {
            wp_send_json_error('Email jest już używany przez inne konto');
            return;
        }
        
        // Utwórz użytkownika
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('Błąd tworzenia konta: ' . $user_id->get_error_message());
            return;
        }
        
        // Ustaw rolę
        $user = new WP_User($user_id);
        $user->set_role('subscriber'); // Lub inna rola
        
        // Ustaw dane użytkownika
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['first_name'] . ' ' . $data['last_name']
        ));
        
        // Przypisz user_id do instruktora
        $wpdb->update($table, array('user_id' => $user_id), array('id' => $instructor_id));
        
        // Wyślij email z danymi
        if (isset($_POST['send_credentials_email']) && $_POST['send_credentials_email'] == '1') {
            $site_name = get_bloginfo('name');
            $login_url = wp_login_url();
            
            $subject = 'Konto instruktora - ' . $site_name;
            $message_body = "Witaj " . $data['first_name'] . "!\n\n";
            $message_body .= "Utworzono dla Ciebie konto w panelu instruktora.\n\n";
            $message_body .= "Dane logowania:\n";
            $message_body .= "Login: " . $username . "\n";
            $message_body .= "Hasło: " . $password . "\n\n";
            $message_body .= "Link do logowania: " . $login_url . "\n\n";
            $message_body .= "Po zalogowaniu przejdź do panelu instruktora.\n\n";
            $message_body .= "Pozdrawiamy,\n" . $site_name;
            
            wp_mail($email, $subject, $message_body);
        }
        
        $message .= ' + utworzono konto WordPress (User ID: ' . $user_id . ')';
    }
    
    wp_send_json_success($message);
}

add_action('wp_ajax_ssm_delete_instructor', 'ssm_ajax_delete_instructor');
function ssm_ajax_delete_instructor() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'ssm_instructors', array('id' => intval($_POST['id'])));
    wp_send_json_success('Usunięto');
}

add_action('wp_ajax_ssm_reset_instructor_password', 'ssm_ajax_reset_instructor_password');
function ssm_ajax_reset_instructor_password() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $instructor_id = intval($_POST['instructor_id']);
    
    // Pobierz instruktora
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE id = %d",
        $instructor_id
    ));
    
    if (!$instructor || !$instructor->user_id) {
        wp_send_json_error('Instruktor nie ma przypisanego konta WordPress');
        return;
    }
    
    // Wygeneruj nowe hasło
    $new_password = wp_generate_password(12, true);
    
    // Zmień hasło
    wp_set_password($new_password, $instructor->user_id);
    
    // Wyślij email
    $user = get_user_by('id', $instructor->user_id);
    $site_name = get_bloginfo('name');
    $login_url = wp_login_url();
    
    $subject = 'Nowe hasło do panelu instruktora - ' . $site_name;
    $message = "Witaj " . $instructor->first_name . "!\n\n";
    $message .= "Twoje hasło zostało zresetowane.\n\n";
    $message .= "Nowe dane logowania:\n";
    $message .= "Login: " . $user->user_login . "\n";
    $message .= "Hasło: " . $new_password . "\n\n";
    $message .= "Link do logowania: " . $login_url . "\n\n";
    $message .= "Pozdrawiamy,\n" . $site_name;
    
    wp_mail($instructor->email, $subject, $message);
    
    wp_send_json_success('Hasło zresetowane i wysłane na email: ' . $instructor->email);
}

// ========================================
// KURSY
// ========================================

add_action('wp_ajax_ssm_save_class', 'ssm_ajax_save_class');
function ssm_ajax_save_class() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_classes';
    
    $data = array(
        'name' => sanitize_text_field($_POST['name']),
        'facility_id' => intval($_POST['facility_id']),
        'instructor_id' => intval($_POST['instructor_id']),
        'day_of_week' => intval($_POST['day_of_week']),
        'time_start' => sanitize_text_field($_POST['time_start']),
        'time_end' => sanitize_text_field($_POST['time_end']),
        'start_date' => sanitize_text_field($_POST['start_date']),
        'session_count' => intval($_POST['session_count']),
        'price_per_session' => floatval($_POST['price_per_session']),
        'total_price' => floatval($_POST['total_price']),
        'level' => isset($_POST['level']) ? sanitize_text_field($_POST['level']) : '',
        'max_participants' => isset($_POST['max_participants']) ? intval($_POST['max_participants']) : 10,
        'max_absences' => isset($_POST['max_absences']) ? intval($_POST['max_absences']) : 2,
        'allow_makeups' => isset($_POST['allow_makeups']) ? intval($_POST['allow_makeups']) : 1,
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
        'status' => sanitize_text_field($_POST['status'])
    );
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Edycja - NIE regeneruj automatycznie harmonogramu
        $class_id = intval($_POST['id']);
        $wpdb->update($table, $data, array('id' => $class_id));
        
        wp_send_json_success('Kurs zaktualizowany');
    } else {
        // Nowy kurs - wygeneruj harmonogram automatycznie
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($table, $data);
        $class_id = $wpdb->insert_id;
        
        // Wygeneruj harmonogram
        $sessions_created = ssm_generate_schedule($class_id);
        
        wp_send_json_success('Wygenerowano ' . $sessions_created . ' zajęć w harmonogramie');
    }
}

add_action('wp_ajax_ssm_delete_class', 'ssm_ajax_delete_class');
function ssm_ajax_delete_class() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $class_id = intval($_POST['id']);
    
    // Usuń sesje
    $wpdb->delete($wpdb->prefix . 'ssm_sessions', array('class_id' => $class_id));
    
    // Usuń zapisy
    $wpdb->delete($wpdb->prefix . 'ssm_enrollments', array('class_id' => $class_id));
    
    // Usuń kurs
    $wpdb->delete($wpdb->prefix . 'ssm_classes', array('id' => $class_id));
    
    wp_send_json_success('Kurs i wszystkie powiązane dane usunięte');
}

// ========================================
// FUNKCJA GENEROWANIA HARMONOGRAMU
// ========================================

function ssm_generate_schedule($class_id) {
    global $wpdb;
    
    $class = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_classes WHERE id = %d",
        $class_id
    ));
    
    if (!$class) return 0;
    
    // Usuń stare sesje
    $wpdb->delete($wpdb->prefix . 'ssm_sessions', array('class_id' => $class_id));
    
    // Generuj nowe sesje
    $current_date = new DateTime($class->start_date);
    $sessions_created = 0;
    
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
        
        $sessions_created++;
        
        // Dodaj 7 dni (następny tydzień, ten sam dzień)
        $current_date->modify('+7 days');
    }
    
    return $sessions_created;
}

// ========================================
// HARMONOGRAM - ZARZĄDZANIE SESJAMI
// ========================================

add_action('wp_ajax_ssm_skip_session', 'ssm_ajax_skip_session');
function ssm_ajax_skip_session() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $session_id = intval($_POST['session_id']);
    $class_id = intval($_POST['class_id']);
    
    // Pobierz numer sesji
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_sessions WHERE id = %d",
        $session_id
    ));
    
    if (!$session) {
        wp_send_json_error('Nie znaleziono sesji');
    }
    
    // Oznacz jako pominięte
    $wpdb->update(
        $wpdb->prefix . 'ssm_sessions',
        array('status' => 'cancelled'),
        array('id' => $session_id)
    );
    
    // Przesuń wszystkie kolejne sesje o 7 dni
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}ssm_sessions 
         SET session_date = DATE_ADD(session_date, INTERVAL 7 DAY)
         WHERE class_id = %d 
         AND session_number > %d 
         AND status != 'cancelled'",
        $class_id,
        $session->session_number
    ));
    
    wp_send_json_success('Zajęcia pominięte. Wszystkie kolejne zajęcia przesunięte o 7 dni.');
}

add_action('wp_ajax_ssm_restore_session', 'ssm_ajax_restore_session');
function ssm_ajax_restore_session() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $session_id = intval($_POST['session_id']);
    $class_id = intval($_POST['class_id']);
    
    // Pobierz numer sesji
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_sessions WHERE id = %d",
        $session_id
    ));
    
    if (!$session) {
        wp_send_json_error('Nie znaleziono sesji');
    }
    
    // Przywróć status
    $wpdb->update(
        $wpdb->prefix . 'ssm_sessions',
        array('status' => 'scheduled'),
        array('id' => $session_id)
    );
    
    // Cofnij daty kolejnych sesji o 7 dni
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}ssm_sessions 
         SET session_date = DATE_SUB(session_date, INTERVAL 7 DAY)
         WHERE class_id = %d 
         AND session_number > %d 
         AND status != 'cancelled'",
        $class_id,
        $session->session_number
    ));
    
    wp_send_json_success('Zajęcia przywrócone. Daty kolejnych zajęć cofnięte o 7 dni.');
}

add_action('wp_ajax_ssm_change_session_instructor', 'ssm_ajax_change_session_instructor');
function ssm_ajax_change_session_instructor() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $session_id = intval($_POST['session_id']);
    $instructor_id = intval($_POST['instructor_id']);
    
    $wpdb->update(
        $wpdb->prefix . 'ssm_sessions',
        array('instructor_id' => $instructor_id),
        array('id' => $session_id)
    );
    
    wp_send_json_success('Instruktor zmieniony');
}

add_action('wp_ajax_ssm_regenerate_schedule', 'ssm_ajax_regenerate_schedule');
function ssm_ajax_regenerate_schedule() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    $class_id = intval($_POST['class_id']);
    $sessions_created = ssm_generate_schedule($class_id);
    
    wp_send_json_success('Harmonogram regenerowany. Utworzono ' . $sessions_created . ' zajęć.');
}

// ========================================
// ZAPISY
// ========================================

add_action('wp_ajax_ssm_get_client_children', 'ssm_ajax_get_client_children');
function ssm_ajax_get_client_children() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $client_id = intval($_POST['client_id']);
    
    $children = $wpdb->get_results($wpdb->prepare(
        "SELECT ch.id, ch.first_name, ch.last_name,
                CONCAT(ch.first_name, ' ', ch.last_name) as name,
                TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as age
         FROM {$wpdb->prefix}ssm_children ch
         JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
         WHERE cc.client_id = %d AND ch.active = 1
         ORDER BY ch.first_name",
        $client_id
    ));
    
    wp_send_json_success($children);
}

add_action('wp_ajax_ssm_enroll_child', 'ssm_ajax_enroll_child');
function ssm_ajax_enroll_child() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    
    $data = array(
        'client_id' => intval($_POST['client_id']),
        'child_id' => intval($_POST['child_id']),
        'class_id' => intval($_POST['class_id']),
        'enrollment_date' => current_time('mysql'),
        'status' => 'active',
        'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : ''
    );
    
    // Sprawdź czy dziecko nie jest już zapisane na ten kurs
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
         WHERE child_id = %d AND class_id = %d AND status = 'active'",
        $data['child_id'],
        $data['class_id']
    ));
    
    if ($exists) {
        wp_send_json_error('To dziecko jest już zapisane na ten kurs!');
    }
    
    // Sprawdź czy kurs nie jest pełny
    $class = $wpdb->get_row($wpdb->prepare(
        "SELECT max_participants FROM {$wpdb->prefix}ssm_classes WHERE id = %d",
        $data['class_id']
    ));
    
    $enrolled_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
         WHERE class_id = %d AND status = 'active'",
        $data['class_id']
    ));
    
    if ($enrolled_count >= $class->max_participants) {
        wp_send_json_error('Kurs jest pełny! (' . $enrolled_count . '/' . $class->max_participants . ')');
    }
    
    $result = $wpdb->insert($wpdb->prefix . 'ssm_enrollments', $data);
    
    if ($result) {
        wp_send_json_success('Dziecko zapisane na kurs!');
    } else {
        wp_send_json_error('Błąd zapisu: ' . $wpdb->last_error);
    }
}

add_action('wp_ajax_ssm_unenroll_child', 'ssm_ajax_unenroll_child');
function ssm_ajax_unenroll_child() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    
    $wpdb->update(
        $wpdb->prefix . 'ssm_enrollments',
        array('status' => 'cancelled'),
        array('id' => intval($_POST['id']))
    );
    
    wp_send_json_success('Dziecko wypisane');
}

add_action('wp_ajax_ssm_delete_enrollment', 'ssm_ajax_delete_enrollment');
function ssm_ajax_delete_enrollment() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'ssm_enrollments', array('id' => intval($_POST['id'])));
    
    wp_send_json_success('Zapis usunięty');
}

// ========================================
// GALERIA
// ========================================

add_action('wp_ajax_ssm_save_gallery', 'ssm_ajax_save_gallery');
function ssm_ajax_save_gallery() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_gallery';
    
    $data = array(
        'title' => sanitize_text_field($_POST['title']),
        'description' => sanitize_textarea_field($_POST['description']),
        'class_id' => !empty($_POST['class_id']) ? intval($_POST['class_id']) : null,
        'status' => sanitize_text_field($_POST['status'])
    );
    
    // Obsługa uploadu obrazu
    if (!empty($_FILES['image']['name'])) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['image'];
        $upload_overrides = array('test_form' => false);
        
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            $data['image_url'] = $movefile['url'];
        } else {
            wp_send_json_error('Błąd uploadu: ' . $movefile['error']);
            return;
        }
    }
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Edycja
        $wpdb->update($table, $data, array('id' => intval($_POST['id'])));
        wp_send_json_success('Zdjęcie zaktualizowane');
    } else {
        // Nowe zdjęcie
        $data['created_at'] = current_time('mysql');
        $wpdb->insert($table, $data);
        wp_send_json_success('Zdjęcie dodane');
    }
}

add_action('wp_ajax_ssm_delete_gallery', 'ssm_ajax_delete_gallery');
function ssm_ajax_delete_gallery() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    
    // Pobierz URL obrazu przed usunięciem
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT image_url FROM {$wpdb->prefix}ssm_gallery WHERE id = %d",
        intval($_POST['id'])
    ));
    
    // Usuń z bazy
    $wpdb->delete($wpdb->prefix . 'ssm_gallery', array('id' => intval($_POST['id'])));
    
    // Opcjonalnie usuń plik (jeśli jest w uploads WordPress)
    if ($item && strpos($item->image_url, wp_upload_dir()['baseurl']) !== false) {
        $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $item->image_url);
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
    
    wp_send_json_success('Usunięto');
}

// ========================================
// NIEOBECNOŚCI
// ========================================

add_action('wp_ajax_ssm_report_absence', 'ssm_ajax_report_absence');
function ssm_ajax_report_absence() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Musisz być zalogowany');
    }
    
    global $wpdb;
    
    // Loguj wszystkie otrzymane dane
    error_log('SSM Absence Report - POST data: ' . print_r($_POST, true));
    
    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    $enrollment_id = isset($_POST['enrollment_id']) ? intval($_POST['enrollment_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    
    // Debug info
    if ($session_id == 0) {
        wp_send_json_error('Nie otrzymano session_id. POST data: ' . json_encode($_POST));
    }
    
    if ($enrollment_id == 0) {
        wp_send_json_error('Nie otrzymano enrollment_id. POST data: ' . json_encode($_POST));
    }
    
    // Sprawdź czy tabela sesji istnieje
    $table_name = $wpdb->prefix . 'ssm_sessions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        wp_send_json_error('Tabela ssm_sessions nie istnieje!');
    }
    
    // Sprawdź czy sesja istnieje
    $session_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions WHERE id = %d",
        $session_id
    ));
    
    if ($session_exists == 0) {
        // Pokaż wszystkie dostępne sesje dla tego użytkownika
        $current_user = wp_get_current_user();
        $available_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.session_date, c.name 
             FROM {$wpdb->prefix}ssm_sessions s
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             JOIN {$wpdb->prefix}ssm_enrollments e ON s.class_id = e.class_id
             JOIN {$wpdb->prefix}ssm_clients cl ON e.client_id = cl.id
             WHERE cl.email = %s
             LIMIT 5",
            $current_user->user_email
        ));
        
        $debug_msg = 'Sesja o ID ' . $session_id . ' nie istnieje. ';
        if ($available_sessions) {
            $debug_msg .= 'Dostępne sesje: ';
            foreach ($available_sessions as $as) {
                $debug_msg .= 'ID:' . $as->id . '(' . $as->name . '), ';
            }
        } else {
            $debug_msg .= 'Brak dostępnych sesji dla tego użytkownika.';
        }
        
        wp_send_json_error($debug_msg);
    }
    
    // Pobierz szczegóły sesji
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, s.time_start as session_time_start
         FROM {$wpdb->prefix}ssm_sessions s
         WHERE s.id = %d",
        $session_id
    ));
    
    if (!$session) {
        wp_send_json_error('Nie można pobrać danych sesji');
    }
    
    // Pobierz ustawienia kursu
    $class_settings = $wpdb->get_row($wpdb->prepare(
        "SELECT id, max_absences, allow_makeups, time_start
         FROM {$wpdb->prefix}ssm_classes 
         WHERE id = %d",
        $session->class_id
    ));
    
    // Ustaw domyślne wartości jeśli kolumny nie istnieją
    $max_absences = isset($class_settings->max_absences) ? $class_settings->max_absences : 2;
    $allow_makeups = isset($class_settings->allow_makeups) ? $class_settings->allow_makeups : 1;
    $class_time_start = isset($class_settings->time_start) ? $class_settings->time_start : $session->session_time_start;
    
    if (!$allow_makeups) {
        wp_send_json_error('Ten kurs nie zezwala na odrabianie');
    }
    
    // Sprawdź 24h - używamy czasu z klasy lub sesji
    $session_datetime_string = $session->session_date . ' ' . $class_time_start;
    $session_datetime = new DateTime($session_datetime_string);
    $now = new DateTime();
    $hours_until = ($session_datetime->getTimestamp() - $now->getTimestamp()) / 3600;
    
    if ($hours_until < 24) {
        wp_send_json_error('Można zgłosić min. 24h przed zajęciami (zostało: ' . round($hours_until, 1) . 'h)');
    }
    
    // Sprawdź enrollment
    $enrollment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_enrollments WHERE id = %d",
        $enrollment_id
    ));
    
    if (!$enrollment) {
        wp_send_json_error('Zapis nie istnieje');
    }
    
    // Sprawdź uprawnienia
    $current_user = wp_get_current_user();
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s AND id = %d",
        $current_user->user_email,
        $enrollment->client_id
    ));
    
    if (!$client) {
        wp_send_json_error('Brak uprawnień');
    }
    
    // Sprawdź limit
    $used_absences = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
         WHERE enrollment_id = %d AND can_makeup = 1",
        $enrollment_id
    ));
    
    if ($used_absences >= $max_absences) {
        wp_send_json_error('Wykorzystano limit (' . $max_absences . ')');
    }
    
    // Sprawdź duplikat
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
         WHERE session_id = %d AND child_id = %d",
        $session_id,
        $enrollment->child_id
    ));
    
    if ($existing > 0) {
        wp_send_json_error('Już zgłoszono');
    }
    
    // Zapisz
    $result = $wpdb->insert(
        $wpdb->prefix . 'ssm_absences',
        array(
            'enrollment_id' => $enrollment_id,
            'session_id' => $session_id,
            'child_id' => $enrollment->child_id,
            'reported_at' => current_time('mysql'),
            'reported_by' => $current_user->ID,
            'reason' => $reason,
            'can_makeup' => 1,
            'status' => 'reported'
        )
    );
    
    if ($result) {
        // Sprawdź czy to request z formularza (redirect_back)
        if (isset($_POST['redirect_back']) && !empty($_POST['redirect_back'])) {
            // Przekieruj z komunikatem
            $redirect_url = add_query_arg('absence_status', 'success', $_POST['redirect_back']);
            wp_redirect($redirect_url);
            exit;
        }
        
        // Normalny AJAX response
        wp_send_json_success('Zgłoszono. Skontaktujemy się w celu ustalenia terminu odrobienia.');
    } else {
        // Sprawdź czy to request z formularza
        if (isset($_POST['redirect_back']) && !empty($_POST['redirect_back'])) {
            $redirect_url = add_query_arg('absence_status', 'error', $_POST['redirect_back']);
            wp_redirect($redirect_url);
            exit;
        }
        
        wp_send_json_error('Błąd zapisu');
    }
}

// ========================================
// PŁATNOŚCI
// ========================================

add_action('wp_ajax_ssm_save_payment', 'ssm_ajax_save_payment');
function ssm_ajax_save_payment() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ssm_payments';
    
    $data = array(
        'client_id' => intval($_POST['client_id']),
        'title' => sanitize_text_field($_POST['title']),
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'total_amount' => floatval($_POST['total_amount']),
        'status' => sanitize_text_field($_POST['status']),
        'due_date' => isset($_POST['due_date']) && !empty($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : null
    );
    
    $payment_id = null;
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Aktualizacja
        $payment_id = intval($_POST['id']);
        $result = $wpdb->update($table, $data, array('id' => $payment_id));
        
        if ($result === false) {
            wp_send_json_error('Błąd aktualizacji płatności: ' . $wpdb->last_error);
            return;
        }
        
        $message = 'Płatność zaktualizowana';
    } else {
        // Nowa płatność
        $data['created_at'] = current_time('mysql');
        $data['paid_amount'] = 0.00;
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            wp_send_json_error('Błąd dodawania płatności: ' . $wpdb->last_error);
            return;
        }
        
        $payment_id = $wpdb->insert_id;
        
        if (!$payment_id) {
            wp_send_json_error('Błąd: Nie otrzymano ID płatności');
            return;
        }
        
        $message = 'Płatność dodana (ID: ' . $payment_id . ')';
    }
    
    // Obsługa rat
    $installments_count = isset($_POST['installments_count']) ? intval($_POST['installments_count']) : 1;
    
    if ($installments_count > 1) {
        // Usuń stare raty (jeśli aktualizacja)
        $wpdb->delete($wpdb->prefix . 'ssm_payment_installments', array('payment_id' => $payment_id));
        
        $total_amount = floatval($_POST['total_amount']);
        $installment_amount = $total_amount / $installments_count;
        
        for ($i = 1; $i <= $installments_count; $i++) {
            // Sprawdź czy są custom wartości
            $amount = isset($_POST['installment_amount_' . $i]) ? floatval($_POST['installment_amount_' . $i]) : $installment_amount;
            $due_date = isset($_POST['installment_date_' . $i]) && !empty($_POST['installment_date_' . $i]) 
                ? sanitize_text_field($_POST['installment_date_' . $i]) 
                : null;
            
            $wpdb->insert(
                $wpdb->prefix . 'ssm_payment_installments',
                array(
                    'payment_id' => $payment_id,
                    'installment_number' => $i,
                    'amount' => $amount,
                    'due_date' => $due_date,
                    'paid_amount' => 0.00,
                    'status' => 'pending'
                )
            );
        }
        
        $message .= ' + utworzono ' . $installments_count . ' rat';
    }
    
    wp_send_json_success($message);
}

add_action('wp_ajax_ssm_delete_payment', 'ssm_ajax_delete_payment');
function ssm_ajax_delete_payment() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $payment_id = intval($_POST['id']);
    
    // Usuń raty
    $wpdb->delete($wpdb->prefix . 'ssm_payment_installments', array('payment_id' => $payment_id));
    
    // Usuń płatność
    $wpdb->delete($wpdb->prefix . 'ssm_payments', array('id' => $payment_id));
    
    wp_send_json_success('Płatność usunięta');
}

add_action('wp_ajax_ssm_update_installment_status', 'ssm_ajax_update_installment_status');
function ssm_ajax_update_installment_status() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $installment_id = intval($_POST['installment_id']);
    $status = sanitize_text_field($_POST['status']);
    $paid_amount = floatval($_POST['paid_amount']);
    
    $data = array(
        'status' => $status,
        'paid_amount' => $paid_amount
    );
    
    if ($status == 'paid') {
        $data['paid_at'] = current_time('mysql');
    }
    
    $wpdb->update(
        $wpdb->prefix . 'ssm_payment_installments',
        $data,
        array('id' => $installment_id)
    );
    
    // Przelicz paid_amount dla głównej płatności
    $installment = $wpdb->get_row($wpdb->prepare(
        "SELECT payment_id FROM {$wpdb->prefix}ssm_payment_installments WHERE id = %d",
        $installment_id
    ));
    
    if ($installment) {
        $total_paid = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(paid_amount) FROM {$wpdb->prefix}ssm_payment_installments WHERE payment_id = %d",
            $installment->payment_id
        ));
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT total_amount FROM {$wpdb->prefix}ssm_payments WHERE id = %d",
            $installment->payment_id
        ));
        
        // Aktualizuj status głównej płatności
        $new_status = 'pending';
        if ($total_paid >= $payment->total_amount) {
            $new_status = 'paid';
        } elseif ($total_paid > 0) {
            $new_status = 'partial';
        }
        
        $wpdb->update(
            $wpdb->prefix . 'ssm_payments',
            array(
                'paid_amount' => $total_paid,
                'status' => $new_status
            ),
            array('id' => $installment->payment_id)
        );
        
        // Hook dla iFirma - wystawianie faktury
        if ($status == 'paid') {
            do_action('ssm_installment_marked_paid', $installment_id, $installment->payment_id);
        }
    }
    
    wp_send_json_success('Status raty zaktualizowany');
}

add_action('wp_ajax_ssm_update_payment_status', 'ssm_ajax_update_payment_status');
function ssm_ajax_update_payment_status() {
    check_ajax_referer('ssm_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Brak uprawnień');
    }
    
    global $wpdb;
    $payment_id = intval($_POST['payment_id']);
    $paid_amount = floatval($_POST['paid_amount']);
    $status = sanitize_text_field($_POST['status']);
    
    $wpdb->update(
        $wpdb->prefix . 'ssm_payments',
        array(
            'paid_amount' => $paid_amount,
            'status' => $status
        ),
        array('id' => $payment_id)
    );
    
    wp_send_json_success('Płatność zaktualizowana');
}

// ========================================
// NIEDYSPOZYCJE INSTRUKTORÓW
// ========================================

add_action('wp_ajax_ssm_report_unavailability', 'ssm_ajax_report_unavailability');
function ssm_ajax_report_unavailability() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Musisz być zalogowany');
    }
    
    global $wpdb;
    $current_user = wp_get_current_user();
    
    // Pobierz instruktora
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
        $current_user->ID,
        $current_user->user_email
    ));
    
    if (!$instructor) {
        wp_send_json_error('Nie znaleziono profilu instruktora');
    }
    
    $session_id = intval($_POST['session_id']);
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    
    // Sprawdź czy zajęcia należą do tego instruktora
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, c.instructor_id, c.name as class_name
         FROM {$wpdb->prefix}ssm_sessions s
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         WHERE s.id = %d",
        $session_id
    ));
    
    if (!$session || $session->instructor_id != $instructor->id) {
        wp_send_json_error('To nie są Twoje zajęcia');
    }
    
    // Sprawdź czy to przyszłe zajęcia
    if ($session->session_date < date('Y-m-d')) {
        wp_send_json_error('Nie można zgłosić niedyspozycji na przeszłe zajęcia');
    }
    
    // Sprawdź czy nie została już zgłoszona
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_instructor_unavailability 
         WHERE instructor_id = %d AND session_id = %d",
        $instructor->id, $session_id
    ));
    
    if ($existing > 0) {
        wp_send_json_error('Niedyspozycja na te zajęcia została już zgłoszona');
    }
    
    // Zapisz niedyspozycję
    $result = $wpdb->insert(
        $wpdb->prefix . 'ssm_instructor_unavailability',
        array(
            'instructor_id' => $instructor->id,
            'session_id' => $session_id,
            'reported_at' => current_time('mysql'),
            'reason' => $reason,
            'status' => 'pending'
        )
    );
    
    if ($result === false) {
        wp_send_json_error('Błąd zapisu: ' . $wpdb->last_error);
    }
    
    // Wyślij powiadomienia do innych instruktorów
    ssm_notify_instructors_about_substitution($session_id, $instructor->id);

    // Trigger powiadomień systemowych
    $unavailability_id = $wpdb->insert_id;
    do_action('ssm_substitution_created', $unavailability_id, $instructor->id);

    wp_send_json_success('Niedyspozycja zgłoszona');
}

add_action('wp_ajax_ssm_cancel_unavailability', 'ssm_ajax_cancel_unavailability');
function ssm_ajax_cancel_unavailability() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Musisz być zalogowany');
    }
    
    global $wpdb;
    $current_user = wp_get_current_user();
    
    // Pobierz instruktora
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
        $current_user->ID,
        $current_user->user_email
    ));
    
    if (!$instructor) {
        wp_send_json_error('Nie znaleziono profilu instruktora');
    }
    
    $unavailability_id = intval($_POST['unavailability_id']);
    
    // Sprawdź czy to niedyspozycja tego instruktora
    $unavailability = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_instructor_unavailability WHERE id = %d",
        $unavailability_id
    ));
    
    if (!$unavailability || $unavailability->instructor_id != $instructor->id) {
        wp_send_json_error('To nie jest Twoja niedyspozycja');
    }
    
    if ($unavailability->status == 'covered') {
        wp_send_json_error('Nie można anulować - zastępstwo już przejęte');
    }
    
    // Usuń niedyspozycję
    $wpdb->delete(
        $wpdb->prefix . 'ssm_instructor_unavailability',
        array('id' => $unavailability_id)
    );
    
    wp_send_json_success('Zgłoszenie anulowane');
}

add_action('wp_ajax_ssm_take_substitution', 'ssm_ajax_take_substitution');
function ssm_ajax_take_substitution() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Musisz być zalogowany');
    }
    
    global $wpdb;
    $current_user = wp_get_current_user();
    
    // Pobierz instruktora
    $instructor = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_instructors WHERE user_id = %d OR email = %s",
        $current_user->ID,
        $current_user->user_email
    ));
    
    if (!$instructor) {
        wp_send_json_error('Nie znaleziono profilu instruktora');
    }
    
    $unavailability_id = intval($_POST['unavailability_id']);
    
    // Pobierz niedyspozycję
    $unavailability = $wpdb->get_row($wpdb->prepare(
        "SELECT u.*, s.session_date, s.time_start, s.time_end, s.class_id
         FROM {$wpdb->prefix}ssm_instructor_unavailability u
         JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
         WHERE u.id = %d",
        $unavailability_id
    ));
    
    if (!$unavailability) {
        wp_send_json_error('Niedyspozycja nie istnieje');
    }
    
    if ($unavailability->status != 'pending') {
        wp_send_json_error('Zastępstwo już przejęte lub niedostępne');
    }
    
    if ($unavailability->instructor_id == $instructor->id) {
        wp_send_json_error('To Twoje własne zajęcia');
    }
    
    // Sprawdź czy instruktor nie ma w tym czasie innych zajęć
    $conflict = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions s
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         WHERE c.instructor_id = %d
         AND s.session_date = %s
         AND s.id != %d
         AND (
             (s.time_start < %s AND s.time_end > %s) OR
             (s.time_start < %s AND s.time_end > %s) OR
             (s.time_start >= %s AND s.time_end <= %s)
         )",
        $instructor->id,
        $unavailability->session_date,
        $unavailability->session_id,
        $unavailability->time_end, $unavailability->time_start,
        $unavailability->time_end, $unavailability->time_end,
        $unavailability->time_start, $unavailability->time_end
    ));
    
    if ($conflict > 0) {
        wp_send_json_error('Masz już zajęcia w tym czasie');
    }
    
    // Sprawdź czy kurs zastępcy już nie istnieje dla tego instruktora
    $existing_class = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_classes 
         WHERE instructor_id = %d 
         AND id = %d",
        $instructor->id,
        $unavailability->class_id
    ));
    
    // Jeśli zastępca nie ma tego kursu, tworzymy tymczasowy kurs dla niego
    $substitute_class_id = null;
    
    if (!$existing_class) {
        // Pobierz dane oryginalnego kursu
        $original_class = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_classes WHERE id = %d",
            $unavailability->class_id
        ));
        
        // Stwórz kurs zastępczy (kopia z nowym instruktorem)
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'ssm_classes',
            array(
                'name' => $original_class->name . ' (zastępstwo)',
                'instructor_id' => $instructor->id,
                'facility_id' => $original_class->facility_id,
                'day_of_week' => $original_class->day_of_week,
                'time_start' => $original_class->time_start,
                'time_end' => $original_class->time_end,
                'start_date' => $unavailability->session_date,
                'session_count' => 1,
                'status' => 'active'
            )
        );
        
        if ($insert_result === false) {
            wp_send_json_error('Błąd tworzenia kursu: ' . $wpdb->last_error);
        }
        
        $substitute_class_id = $wpdb->insert_id;
        
        // Stwórz sesję zastępczą
        $session_result = $wpdb->insert(
            $wpdb->prefix . 'ssm_sessions',
            array(
                'class_id' => $substitute_class_id,
                'session_number' => 1,
                'session_date' => $unavailability->session_date,
                'time_start' => $unavailability->time_start,
                'time_end' => $unavailability->time_end,
                'instructor_id' => $instructor->id,
                'status' => 'scheduled'
            )
        );
        
        if ($session_result === false) {
            wp_send_json_error('Błąd tworzenia sesji: ' . $wpdb->last_error);
        }
    }
    
    // Aktualizuj niedyspozycję
    $wpdb->update(
        $wpdb->prefix . 'ssm_instructor_unavailability',
        array(
            'replacement_instructor_id' => $instructor->id,
            'status' => 'covered',
            'notes' => 'Zastępstwo przejęte. ' . ($substitute_class_id ? 'Utworzono kurs zastępczy ID: ' . $substitute_class_id : '')
        ),
        array('id' => $unavailability_id)
    );
    
    // Wyślij powiadomienie do oryginalnego instruktora
    ssm_notify_instructor_substitution_taken($unavailability_id);

    // Trigger powiadomień systemowych
    do_action('ssm_substitution_taken', $unavailability_id, $instructor->id);
    do_action('ssm_instructor_change', $unavailability->session_id, $unavailability->instructor_id, $instructor->id);

    wp_send_json_success('Zastępstwo przejęte - zajęcia dodane do harmonogramu');
}

// Funkcja wysyłania powiadomień
function ssm_notify_instructors_about_substitution($session_id, $reporting_instructor_id) {
    global $wpdb;
    
    // Pobierz dane zajęć
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, c.name as class_name, f.name as facility_name,
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name
         FROM {$wpdb->prefix}ssm_sessions s
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
         JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
         WHERE s.id = %d",
        $session_id
    ));
    
    if (!$session) return;
    
    // Pobierz wszystkich aktywnych instruktorów (oprócz zgłaszającego)
    $instructors = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_instructors 
         WHERE id != %d AND active = 1 AND email != ''",
        $reporting_instructor_id
    ));
    
    if (empty($instructors)) return;
    
    $date = new DateTime($session->session_date);
    $subject = 'Zastępstwo - ' . $session->class_name . ' (' . $date->format('d.m.Y') . ')';
    
    $message = sprintf(
        "%s zgłosił/a niedyspozycję na zajęcia:\n\n" .
        "📋 Kurs: %s\n" .
        "📅 Data: %s (%s)\n" .
        "⏰ Godzina: %s-%s\n" .
        "📍 Miejsce: %s\n\n" .
        "Czy możesz poprowadzić te zajęcia?\n" .
        "Zaloguj się do panelu instruktora i przejdź do zakładki \"Zastępstwa\" aby przejąć zastępstwo.\n\n" .
        "---\n" .
        "Szkółka Pływania",
        $session->instructor_name,
        $session->class_name,
        $date->format('d.m.Y'),
        ssm_get_day_name_pl($date),
        substr($session->time_start, 0, 5),
        substr($session->time_end, 0, 5),
        $session->facility_name
    );
    
    foreach ($instructors as $instructor) {
        wp_mail($instructor->email, $subject, $message);
    }
}

function ssm_notify_instructor_substitution_taken($unavailability_id) {
    global $wpdb;
    
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT u.*, 
                s.session_date, s.time_start, s.time_end,
                c.name as class_name,
                CONCAT(orig.first_name, ' ', orig.last_name) as original_instructor,
                orig.email as original_email,
                CONCAT(repl.first_name, ' ', repl.last_name) as replacement_instructor
         FROM {$wpdb->prefix}ssm_instructor_unavailability u
         JOIN {$wpdb->prefix}ssm_sessions s ON u.session_id = s.id
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         JOIN {$wpdb->prefix}ssm_instructors orig ON u.instructor_id = orig.id
         JOIN {$wpdb->prefix}ssm_instructors repl ON u.replacement_instructor_id = repl.id
         WHERE u.id = %d",
        $unavailability_id
    ));
    
    if (!$data || !$data->original_email) return;
    
    $date = new DateTime($data->session_date);
    $subject = 'Zastępstwo przejęte - ' . $data->class_name;
    
    $message = sprintf(
        "Twoje zajęcia przejmie %s:\n\n" .
        "📋 Kurs: %s\n" .
        "📅 Data: %s (%s)\n" .
        "⏰ Godzina: %s-%s\n\n" .
        "Dziękujemy za zgłoszenie niedyspozycji.\n\n" .
        "---\n" .
        "Szkółka Pływania",
        $data->replacement_instructor,
        $data->class_name,
        $date->format('d.m.Y'),
        ssm_get_day_name_pl($date),
        substr($data->time_start, 0, 5),
        substr($data->time_end, 0, 5)
    );
    
    wp_mail($data->original_email, $subject, $message);
}

// Pomocnicza funkcja dla dni tygodnia
if (!function_exists('ssm_get_day_name_pl')) {
    function ssm_get_day_name_pl($date) {
        $days = array(
            'Monday' => 'poniedziałek',
            'Tuesday' => 'wtorek',
            'Wednesday' => 'środa',
            'Thursday' => 'czwartek',
            'Friday' => 'piątek',
            'Saturday' => 'sobota',
            'Sunday' => 'niedziela'
        );
        return $days[$date->format('l')] ?? '';
    }
}
