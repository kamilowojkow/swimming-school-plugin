<?php
/**
 * WordPress Registration with Referral Code
 */

if (!defined('ABSPATH')) exit;

/**
 * Dodaj pole "Kod polecający" do formularza rejestracji
 */
add_action('register_form', 'ssm_add_referral_field_to_registration');
function ssm_add_referral_field_to_registration() {
    $referral_code = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
    ?>
    <p>
        <label for="referral_code">Kod polecający (opcjonalnie):</label>
        <input type="text" 
               name="referral_code" 
               id="referral_code" 
               class="input" 
               value="<?php echo esc_attr($referral_code); ?>" 
               size="25" 
               placeholder="np. ANNA1234"
               style="text-transform: uppercase;">
        <br>
        <small style="color: #666;">Masz kod polecający od znajomego? Wpisz go tutaj, aby otrzymać bonus!</small>
    </p>
    <?php
}

/**
 * Waliduj kod polecający podczas rejestracji
 */
add_filter('registration_errors', 'ssm_validate_referral_code', 10, 3);
function ssm_validate_referral_code($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['referral_code'])) {
        return $errors; // Kod opcjonalny
    }
    
    global $wpdb;
    $referral_code = strtoupper(sanitize_text_field($_POST['referral_code']));
    
    // Sprawdź czy kod istnieje
    $code_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_referrals WHERE referral_code = %s",
        $referral_code
    ));
    
    if (!$code_exists) {
        $errors->add('invalid_referral', '<strong>BŁĄD</strong>: Nieprawidłowy kod polecający.');
    }
    
    return $errors;
}

/**
 * Zapisz kod polecający po pomyślnej rejestracji
 */
add_action('user_register', 'ssm_process_referral_on_registration', 10, 1);
function ssm_process_referral_on_registration($user_id) {
    if (empty($_POST['referral_code'])) {
        return;
    }
    
    global $wpdb;
    $referral_code = strtoupper(sanitize_text_field($_POST['referral_code']));
    
    // Znajdź referral
    $referral = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_referrals 
         WHERE referral_code = %s 
         ORDER BY id DESC LIMIT 1",
        $referral_code
    ));
    
    if (!$referral) {
        return;
    }
    
    // Pobierz dane nowego użytkownika
    $user = get_userdata($user_id);
    
    // Utwórz klienta w systemie szkółki
    $client_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
        $user->user_email
    ));
    
    if (!$client_id) {
        // Stwórz nowego klienta
        $wpdb->insert(
            $wpdb->prefix . 'ssm_clients',
            array(
                'first_name' => $user->first_name ?: $user->user_login,
                'last_name' => $user->last_name ?: '',
                'email' => $user->user_email,
                'created_at' => current_time('mysql')
            )
        );
        $client_id = $wpdb->insert_id;
    }
    
    // Aktualizuj lub utwórz referral
    if ($referral->status == 'code_only' || $referral->status == 'invited') {
        // Aktualizuj istniejący
        $wpdb->update(
            $wpdb->prefix . 'ssm_referrals',
            array(
                'referred_client_id' => $client_id,
                'referred_email' => $user->user_email,
                'referred_name' => trim(($user->first_name ?: '') . ' ' . ($user->last_name ?: '')),
                'status' => 'registered',
                'registered_at' => current_time('mysql')
            ),
            array('id' => $referral->id)
        );
    } else {
        // Utwórz nowy referral
        $wpdb->insert(
            $wpdb->prefix . 'ssm_referrals',
            array(
                'referrer_client_id' => $referral->referrer_client_id,
                'referred_client_id' => $client_id,
                'referral_code' => $referral_code,
                'referred_email' => $user->user_email,
                'referred_name' => trim(($user->first_name ?: '') . ' ' . ($user->last_name ?: '')),
                'status' => 'registered',
                'created_at' => current_time('mysql'),
                'registered_at' => current_time('mysql')
            )
        );
    }
    
    // Email do polecającego
    $referrer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
        $referral->referrer_client_id
    ));
    
    if ($referrer) {
        $to = $referrer->email;
        $subject = "👥 Twoja znajoma/znajomy się zarejestrował!";
        
        $message = "Witaj " . $referrer->first_name . ",\n\n";
        $message .= "Świetne wiadomości! Osoba, którą zaprosiłeś właśnie się zarejestrowała.\n\n";
        $message .= "Email: " . $user->user_email . "\n";
        $message .= "Otrzymasz cashback gdy dokona pierwszej płatności!\n\n";
        $message .= "Dziękujemy za polecanie!\n";
        $message .= "Szkółka Pływania";
        
        wp_mail($to, $subject, $message);
    }
}

/**
 * Dodaj link z kodem do share
 */
function ssm_get_referral_share_link($referral_code) {
    return add_query_arg('ref', $referral_code, wp_registration_url());
}

/**
 * AJAX - Sprawdź kod polecający
 */
add_action('wp_ajax_nopriv_ssm_check_referral_code', 'ssm_ajax_check_referral_code');
add_action('wp_ajax_ssm_check_referral_code', 'ssm_ajax_check_referral_code');
function ssm_ajax_check_referral_code() {
    $code = strtoupper(sanitize_text_field($_POST['code']));
    
    if (empty($code)) {
        wp_send_json_success(array('valid' => null, 'message' => ''));
    }
    
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_referrals WHERE referral_code = %s",
        $code
    ));
    
    if ($exists) {
        wp_send_json_success(array(
            'valid' => true,
            'message' => '✅ Kod prawidłowy! Otrzymasz bonus przy pierwszej płatności.'
        ));
    } else {
        wp_send_json_success(array(
            'valid' => false,
            'message' => '❌ Nieprawidłowy kod polecający.'
        ));
    }
}
