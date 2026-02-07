<?php
/**
 * Referral System - Program Poleceń
 */

if (!defined('ABSPATH')) exit;

/**
 * Generuj unikalny kod polecający dla klienta
 */
function ssm_generate_referral_code($client_id) {
    global $wpdb;
    
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT first_name, last_name FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
        $client_id
    ));
    
    if (!$client) {
        return false;
    }
    
    // Generuj kod: IMIE + 4 cyfry losowe
    $base = strtoupper(substr($client->first_name, 0, 4));
    $max_attempts = 10;
    
    for ($i = 0; $i < $max_attempts; $i++) {
        $code = $base . rand(1000, 9999);
        
        // Sprawdź czy kod już istnieje
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_referrals WHERE referral_code = %s",
            $code
        ));
        
        if (!$exists) {
            return $code;
        }
    }
    
    // Jeśli nie udało się wygenerować, użyj timestamp
    return $base . substr(time(), -4);
}

/**
 * Pobierz lub utwórz kod polecający dla klienta
 */
function ssm_get_client_referral_code($client_id) {
    global $wpdb;
    
    // Sprawdź czy klient już ma kod
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT referral_code FROM {$wpdb->prefix}ssm_referrals 
         WHERE referrer_client_id = %d 
         LIMIT 1",
        $client_id
    ));
    
    if ($existing) {
        return $existing;
    }
    
    // Wygeneruj nowy kod
    $code = ssm_generate_referral_code($client_id);
    
    // Zapisz jako pierwszy referral (pending)
    $wpdb->insert(
        $wpdb->prefix . 'ssm_referrals',
        array(
            'referrer_client_id' => $client_id,
            'referral_code' => $code,
            'status' => 'code_only',
            'created_at' => current_time('mysql')
        )
    );
    
    return $code;
}

/**
 * Pobierz lub utwórz portfel dla klienta
 */
function ssm_get_or_create_wallet($client_id) {
    global $wpdb;
    
    $wallet = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_wallet WHERE client_id = %d",
        $client_id
    ));
    
    if ($wallet) {
        return $wallet;
    }
    
    // Utwórz nowy portfel
    $wpdb->insert(
        $wpdb->prefix . 'ssm_wallet',
        array(
            'client_id' => $client_id,
            'balance' => 0.00,
            'total_earned' => 0.00,
            'total_spent' => 0.00,
            'updated_at' => current_time('mysql')
        )
    );
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_wallet WHERE client_id = %d",
        $client_id
    ));
}

/**
 * Dodaj środki do portfela (cashback)
 */
function ssm_wallet_add_funds($client_id, $amount, $description, $referral_id = null) {
    global $wpdb;
    
    $wallet = ssm_get_or_create_wallet($client_id);
    
    // Dodaj transakcję
    $wpdb->insert(
        $wpdb->prefix . 'ssm_wallet_transactions',
        array(
            'wallet_id' => $wallet->id,
            'type' => 'earned',
            'amount' => $amount,
            'description' => $description,
            'referral_id' => $referral_id,
            'created_at' => current_time('mysql')
        )
    );
    
    // Aktualizuj saldo
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}ssm_wallet 
         SET balance = balance + %f,
             total_earned = total_earned + %f,
             updated_at = %s
         WHERE id = %d",
        $amount, $amount, current_time('mysql'), $wallet->id
    ));
    
    return true;
}

/**
 * Wykorzystaj środki z portfela
 */
function ssm_wallet_spend_funds($client_id, $amount, $description, $payment_id = null) {
    global $wpdb;
    
    $wallet = ssm_get_or_create_wallet($client_id);
    
    // Sprawdź czy wystarczające saldo
    if ($wallet->balance < $amount) {
        return false;
    }
    
    // Dodaj transakcję
    $wpdb->insert(
        $wpdb->prefix . 'ssm_wallet_transactions',
        array(
            'wallet_id' => $wallet->id,
            'type' => 'spent',
            'amount' => $amount,
            'description' => $description,
            'payment_id' => $payment_id,
            'created_at' => current_time('mysql')
        )
    );
    
    // Zmniejsz saldo
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}ssm_wallet 
         SET balance = balance - %f,
             total_spent = total_spent + %f,
             updated_at = %s
         WHERE id = %d",
        $amount, $amount, current_time('mysql'), $wallet->id
    ));
    
    return true;
}

/**
 * Wyślij zaproszenie email
 */
function ssm_send_referral_invitation($referrer_client_id, $referred_email, $referred_name) {
    global $wpdb;
    
    $referrer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
        $referrer_client_id
    ));
    
    $code = ssm_get_client_referral_code($referrer_client_id);
    
    // Zapisz zaproszenie
    $wpdb->insert(
        $wpdb->prefix . 'ssm_referrals',
        array(
            'referrer_client_id' => $referrer_client_id,
            'referral_code' => $code,
            'referred_email' => $referred_email,
            'referred_name' => $referred_name,
            'status' => 'invited',
            'created_at' => current_time('mysql')
        )
    );
    
    // Wyślij email
    $to = $referred_email;
    $subject = "Zaproszenie do Szkółki Pływania od " . $referrer->first_name;
    
    $message = "Cześć " . $referred_name . ",\n\n";
    $message .= $referrer->first_name . " " . $referrer->last_name . " poleca Ci naszą szkółkę pływania!\n\n";
    $message .= "Zarejestruj się używając kodu: " . $code . "\n";
    $message .= "Link do rejestracji: " . home_url('/rejestracja') . "\n\n";
    $message .= "Przy rejestracji wpisz kod polecający, a otrzymasz specjalną zniżkę!\n\n";
    $message .= "Do zobaczenia na basenie!\n";
    $message .= "Szkółka Pływania";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Nalicz cashback za polecenie
 */
function ssm_process_referral_cashback($referral_id) {
    global $wpdb;
    
    $settings = get_option('ssm_referral_settings', array(
        'enabled' => 0,
        'cashback_amount' => 50,
        'cashback_type' => 'fixed', // fixed lub percentage
        'max_cashback' => 200
    ));
    
    if (!$settings['enabled']) {
        return false;
    }
    
    $referral = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_referrals WHERE id = %d",
        $referral_id
    ));
    
    if (!$referral || $referral->status == 'credited') {
        return false;
    }
    
    // Oblicz cashback
    if ($settings['cashback_type'] == 'percentage') {
        // Pobierz kwotę pierwszej płatności
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT total_amount FROM {$wpdb->prefix}ssm_payments WHERE id = %d",
            $referral->payment_id
        ));
        
        $cashback = ($payment->total_amount * $settings['cashback_amount']) / 100;
        
        // Ogranicz do max
        if ($cashback > $settings['max_cashback']) {
            $cashback = $settings['max_cashback'];
        }
    } else {
        $cashback = $settings['cashback_amount'];
    }
    
    // Dodaj do portfela
    ssm_wallet_add_funds(
        $referral->referrer_client_id,
        $cashback,
        "Cashback za polecenie: " . ($referral->referred_name ?: $referral->referred_email),
        $referral_id
    );
    
    // Aktualizuj referral
    $wpdb->update(
        $wpdb->prefix . 'ssm_referrals',
        array(
            'status' => 'credited',
            'cashback_amount' => $cashback,
            'credited_at' => current_time('mysql')
        ),
        array('id' => $referral_id)
    );
    
    // Wyślij email do polecającego
    $referrer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
        $referral->referrer_client_id
    ));
    
    $to = $referrer->email;
    $subject = "🎉 Otrzymałeś cashback!";
    
    $message = "Witaj " . $referrer->first_name . ",\n\n";
    $message .= "Świetna wiadomość! Osoba, którą poleciłeś właśnie dokonała pierwszej płatności.\n\n";
    $message .= "Otrzymujesz cashback: " . number_format($cashback, 2, ',', ' ') . " zł\n";
    $message .= "Możesz go wykorzystać przy następnej płatności!\n\n";
    $message .= "Aktualne saldo portfela: " . number_format(ssm_get_or_create_wallet($referral->referrer_client_id)->balance, 2, ',', ' ') . " zł\n\n";
    $message .= "Dziękujemy za polecanie naszej szkółki!\n";
    $message .= "Szkółka Pływania";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    wp_mail($to, $subject, $message, $headers);
    
    return true;
}

/**
 * Hook - sprawdź polecenia po pierwszej płatności nowego klienta
 */
add_action('ssm_installment_marked_paid', 'ssm_check_referral_for_payment', 20, 2);
function ssm_check_referral_for_payment($installment_id, $payment_id) {
    global $wpdb;
    
    // Pobierz dane płatności
    $payment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_payments WHERE id = %d",
        $payment_id
    ));
    
    if (!$payment) {
        return;
    }
    
    // Sprawdź czy to pierwsza płatność tego klienta
    $previous_payments = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_payments 
         WHERE client_id = %d AND id < %d AND status IN ('paid', 'partial')",
        $payment->client_id, $payment_id
    ));
    
    if ($previous_payments > 0) {
        return; // Nie pierwsza płatność
    }
    
    // Znajdź referral dla tego klienta
    $referral = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_referrals 
         WHERE referred_client_id = %d AND status IN ('registered', 'invited')
         ORDER BY id DESC LIMIT 1",
        $payment->client_id
    ));
    
    if (!$referral) {
        return; // Brak polecenia
    }
    
    // Aktualizuj referral z payment_id
    $wpdb->update(
        $wpdb->prefix . 'ssm_referrals',
        array(
            'payment_id' => $payment_id,
            'status' => 'paid',
            'paid_at' => current_time('mysql')
        ),
        array('id' => $referral->id)
    );
    
    // Nalicz cashback
    ssm_process_referral_cashback($referral->id);
}
