<?php
/**
 * iFirma.pl API Integration
 * Dokumentacja: https://www.ifirma.pl/pomoc/dokumentacja-api
 */

if (!defined('ABSPATH')) exit;

/**
 * Test połączenia z iFirma.pl
 */
function ssm_ifirma_test_connection() {
    $settings = get_option('ssm_ifirma_settings', array());
    
    if (empty($settings['api_key']) || empty($settings['email']) || empty($settings['hash_key'])) {
        return array(
            'success' => false,
            'message' => 'Nie wszystkie dane autoryzacyjne są wypełnione'
        );
    }
    
    // Testowy request - pobierz konto
    $api_url = 'https://www.ifirma.pl/iapi/abonent.json';
    
    // Przygotuj hash autoryzacji
    $hash = sha1($settings['hash_key'] . 'abonent');
    
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Accept' => 'application/json',
            'Authentication' => $settings['email'] . '/' . $settings['api_key'] . '/' . $hash
        ),
        'timeout' => 15
    ));
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => 'Błąd połączenia: ' . $response->get_error_message()
        );
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($status_code === 200 && isset($body['response'])) {
        return array(
            'success' => true,
            'message' => 'Połączenie nawiązane pomyślnie! Konto: ' . ($body['response']['Nazwa'] ?? 'OK')
        );
    }
    
    if ($status_code === 401) {
        return array(
            'success' => false,
            'message' => 'Błąd autoryzacji - sprawdź klucze API'
        );
    }
    
    return array(
        'success' => false,
        'message' => 'Błąd ' . $status_code . ': ' . ($body['message'] ?? 'Nieznany błąd')
    );
}

/**
 * Wystawienie faktury w iFirma.pl
 * 
 * @param int $payment_id ID płatności
 * @param int|null $installment_id ID raty (opcjonalnie)
 * @return array
 */
function ssm_ifirma_issue_invoice($payment_id, $installment_id = null) {
    global $wpdb;
    
    $settings = get_option('ssm_ifirma_settings', array());
    
    if (!$settings['enabled']) {
        return array(
            'success' => false,
            'error' => 'Integracja iFirma nie jest włączona'
        );
    }
    
    // Pobierz dane płatności
    if ($installment_id) {
        // Faktura za ratę
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, p.*, 
                    ch.first_name as child_first_name, ch.last_name as child_last_name,
                    CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
                    cl.first_name, cl.last_name, cl.email, cl.phone,
                    cl.company_name, cl.nip,
                    cl.invoice_address, cl.invoice_postal_code, cl.invoice_city,
                    c.name as class_name
             FROM {$wpdb->prefix}ssm_payment_installments i
             JOIN {$wpdb->prefix}ssm_payments p ON i.payment_id = p.id
             JOIN {$wpdb->prefix}ssm_children ch ON p.child_id = ch.id
             JOIN {$wpdb->prefix}ssm_clients cl ON p.client_id = cl.id
             JOIN {$wpdb->prefix}ssm_enrollments e ON p.enrollment_id = e.id
             JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
             WHERE i.id = %d",
            $installment_id
        ));
        
        if (!$data) {
            return array('success' => false, 'error' => 'Nie znaleziono raty płatności');
        }
        
        $invoice_amount = $data->amount;
        $invoice_description = "Zajęcia pływackie - {$data->class_name} - {$data->child_name} (rata {$data->installment_number})";
        
    } else {
        // Faktura za całą płatność
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, 
                    ch.first_name as child_first_name, ch.last_name as child_last_name,
                    CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
                    cl.first_name, cl.last_name, cl.email, cl.phone,
                    cl.company_name, cl.nip,
                    cl.invoice_address, cl.invoice_postal_code, cl.invoice_city,
                    c.name as class_name
             FROM {$wpdb->prefix}ssm_payments p
             JOIN {$wpdb->prefix}ssm_children ch ON p.child_id = ch.id
             JOIN {$wpdb->prefix}ssm_clients cl ON p.client_id = cl.id
             JOIN {$wpdb->prefix}ssm_enrollments e ON p.enrollment_id = e.id
             JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
             WHERE p.id = %d",
            $payment_id
        ));
        
        if (!$data) {
            return array('success' => false, 'error' => 'Nie znaleziono płatności');
        }
        
        $invoice_amount = $data->total_amount;
        $invoice_description = "Zajęcia pływackie - {$data->class_name} - {$data->child_name}";
    }
    
    // Przygotuj dane faktury
    $invoice_data = array(
        'Zaplacono' => 1,
        'DataWystawienia' => date('Y-m-d'),
        'DataSprzedazy' => date('Y-m-d'),
        'TerminPlatnosci' => date('Y-m-d'),
        'FormatDatySprzedazy' => 'DDS',
        'RodzajPodpisuOdbiorcy' => 'BPO',
        'SposobZaplaty' => 'PRZ', // Przelew
    );
    
    // Dodaj numer konta jeśli jest
    if (!empty($settings['bank_account'])) {
        $invoice_data['NumerKontaBankowego'] = $settings['bank_account'];
    }
    
    // Nabywca
    $buyer_name = !empty($data->company_name) ? $data->company_name : trim($data->first_name . ' ' . $data->last_name);
    
    $invoice_data['Nabywca'] = array(
        'NazwaPelna' => $buyer_name,
        'Email' => $data->email
    );
    
    // Dodaj NIP jeśli jest
    if (!empty($data->nip)) {
        $invoice_data['Nabywca']['NIP'] = str_replace(array('-', ' '), '', $data->nip);
    }
    
    // Dodaj adres jeśli jest
    if (!empty($data->invoice_address)) {
        $invoice_data['Nabywca']['Ulica'] = $data->invoice_address;
        $invoice_data['Nabywca']['KodPocztowy'] = $data->invoice_postal_code;
        $invoice_data['Nabywca']['Miejscowosc'] = $data->invoice_city;
    }
    
    // Pozycje faktury
    $invoice_data['Pozycje'] = array(
        array(
            'StawkaVat' => $settings['vat_rate'] ?: 'zw',
            'Ilosc' => 1,
            'CenaJednostkowa' => floatval($invoice_amount),
            'NazwaPelna' => $invoice_description,
            'JednostkaMiary' => 'szt'
        )
    );
    
    // Wyślij do iFirma
    $result = ssm_ifirma_api_request('fakturakraj', $invoice_data, 'POST');
    
    if ($result['success']) {
        $invoice_id = $result['data']['response']['Identyfikator'];
        $invoice_number = $result['data']['response']['Pelny'];
        
        // Zapisz fakturę do bazy
        $wpdb->insert(
            $wpdb->prefix . 'ssm_invoices',
            array(
                'payment_id' => $payment_id,
                'installment_id' => $installment_id,
                'client_id' => $data->client_id,
                'ifirma_id' => $invoice_id,
                'invoice_number' => $invoice_number,
                'invoice_date' => date('Y-m-d'),
                'amount' => $invoice_amount,
                'vat_amount' => 0, // Dla zw VAT = 0
                'status' => 'issued',
                'pdf_url' => "https://www.ifirma.pl/iapi/fakturakraj/{$invoice_id}.pdf",
                'created_at' => current_time('mysql'),
                'issued_at' => current_time('mysql')
            )
        );
        
        $new_invoice_id = $wpdb->insert_id;
        
        // Wyślij email do klienta jeśli włączone
        if ($settings['email_client']) {
            ssm_send_invoice_email($data->client_id, $new_invoice_id);
        }
        
        return array(
            'success' => true,
            'invoice_id' => $new_invoice_id,
            'invoice_number' => $invoice_number,
            'ifirma_id' => $invoice_id
        );
        
    } else {
        // Zapisz błąd
        $wpdb->insert(
            $wpdb->prefix . 'ssm_invoices',
            array(
                'payment_id' => $payment_id,
                'installment_id' => $installment_id,
                'client_id' => $data->client_id,
                'status' => 'failed',
                'error_message' => $result['error'],
                'created_at' => current_time('mysql')
            )
        );
        
        return $result;
    }
}

/**
 * Uniwersalna funkcja API request
 */
function ssm_ifirma_api_request($endpoint, $data = null, $method = 'GET') {
    $settings = get_option('ssm_ifirma_settings', array());
    
    $api_url = 'https://www.ifirma.pl/iapi/' . $endpoint . '.json';
    
    // Przygotuj hash
    $json = $data ? json_encode($data) : '';
    $endpoint_name = explode('/', $endpoint)[0]; // fakturakraj lub abonent
    $hash = sha1($settings['hash_key'] . $endpoint_name . $json);
    
    $args = array(
        'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authentication' => $settings['email'] . '/' . $settings['api_key'] . '/' . $hash
        ),
        'timeout' => 30
    );
    
    if ($method === 'POST') {
        $args['body'] = $json;
        $response = wp_remote_post($api_url, $args);
    } else {
        $response = wp_remote_get($api_url, $args);
    }
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'error' => 'Błąd połączenia: ' . $response->get_error_message()
        );
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    // Loguj request/response dla debugowania
    error_log('iFirma API Request: ' . $endpoint);
    error_log('iFirma API Response: ' . wp_remote_retrieve_body($response));
    
    if ($status_code === 200 || $status_code === 201) {
        return array(
            'success' => true,
            'data' => $body
        );
    }
    
    return array(
        'success' => false,
        'error' => 'Błąd ' . $status_code . ': ' . ($body['message'] ?? json_encode($body))
    );
}

/**
 * Pobierz PDF faktury
 */
function ssm_ifirma_get_invoice_pdf($invoice_id) {
    global $wpdb;
    
    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_invoices WHERE id = %d",
        $invoice_id
    ));
    
    if (!$invoice || !$invoice->ifirma_id) {
        return false;
    }
    
    $settings = get_option('ssm_ifirma_settings', array());
    $pdf_url = "https://www.ifirma.pl/iapi/fakturakraj/{$invoice->ifirma_id}.pdf";
    
    // Hash dla PDF
    $hash = sha1($settings['hash_key'] . 'fakturakraj' . $invoice->ifirma_id . '.pdf');
    
    $response = wp_remote_get($pdf_url, array(
        'headers' => array(
            'Authentication' => $settings['email'] . '/' . $settings['api_key'] . '/' . $hash
        ),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    if (wp_remote_retrieve_response_code($response) === 200) {
        return wp_remote_retrieve_body($response);
    }
    
    return false;
}

/**
 * Wyślij email z fakturą do klienta
 */
function ssm_send_invoice_email($client_id, $invoice_id) {
    global $wpdb;
    
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
        $client_id
    ));
    
    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_invoices WHERE id = %d",
        $invoice_id
    ));
    
    if (!$client || !$invoice) {
        return false;
    }
    
    $to = $client->email;
    $subject = "Faktura {$invoice->invoice_number} - Szkółka Pływania";
    
    $message = "Witaj " . $client->first_name . ",\n\n";
    $message .= "W załączniku przesyłamy fakturę za zajęcia pływackie.\n\n";
    $message .= "Numer faktury: {$invoice->invoice_number}\n";
    $message .= "Data wystawienia: " . date('d.m.Y', strtotime($invoice->invoice_date)) . "\n";
    $message .= "Kwota: " . number_format($invoice->amount, 2, ',', ' ') . " zł\n\n";
    $message .= "Fakturę możesz również pobrać w swoim panelu rodzica.\n\n";
    $message .= "Pozdrawiamy,\n";
    $message .= "Szkółka Pływania";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    // Pobierz PDF jako załącznik
    $pdf_content = ssm_ifirma_get_invoice_pdf($invoice_id);
    
    if ($pdf_content) {
        // Zapisz tymczasowo
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/faktury/' . $invoice->invoice_number . '.pdf';
        
        // Utwórz katalog jeśli nie istnieje
        if (!file_exists(dirname($temp_file))) {
            wp_mkdir_p(dirname($temp_file));
        }
        
        file_put_contents($temp_file, $pdf_content);
        
        $attachments = array($temp_file);
        $result = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Usuń tymczasowy plik
        @unlink($temp_file);
        
        return $result;
    }
    
    // Jeśli nie ma PDF, wyślij sam email z linkiem
    $message .= "\nLink do faktury: {$invoice->pdf_url}\n";
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Hook - automatyczne wystawianie po oznaczeniu jako opłacona
 */
add_action('ssm_installment_marked_paid', 'ssm_auto_issue_invoice_for_installment', 10, 2);
function ssm_auto_issue_invoice_for_installment($installment_id, $payment_id) {
    global $wpdb;
    
    $settings = get_option('ssm_ifirma_settings', array());
    
    // Sprawdź czy automatyczne wystawianie włączone
    if (!$settings['enabled'] || !$settings['auto_issue']) {
        return;
    }
    
    // Sprawdź czy klient chce fakturę
    $wants_invoice = $wpdb->get_var($wpdb->prepare(
        "SELECT cl.wants_invoice
         FROM {$wpdb->prefix}ssm_payments p
         JOIN {$wpdb->prefix}ssm_clients cl ON p.client_id = cl.id
         WHERE p.id = %d",
        $payment_id
    ));
    
    if (!$wants_invoice) {
        return;
    }
    
    // Sprawdź czy faktura już nie istnieje
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ssm_invoices 
         WHERE installment_id = %d AND status = 'issued'",
        $installment_id
    ));
    
    if ($existing) {
        return; // Już wystawiona
    }
    
    // Wystaw fakturę
    ssm_ifirma_issue_invoice($payment_id, $installment_id);
}
