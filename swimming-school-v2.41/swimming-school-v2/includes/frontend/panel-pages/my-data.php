<?php
// Moje dane - edycja danych rodzica
if (!defined('ABSPATH')) exit;

// Obsługa zapisu
$success_message = '';
$error_message = '';

if (isset($_POST['ssm_update_my_data'])) {
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $address = sanitize_textarea_field($_POST['address']);
    
    // Aktualizuj dane klienta
    $result = $wpdb->update(
        $wpdb->prefix . 'ssm_clients',
        array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'address' => $address
        ),
        array('id' => $client->id)
    );
    
    if ($result !== false) {
        // Aktualizuj także dane w WordPress
        wp_update_user(array(
            'ID' => $current_user->ID,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        $success_message = '✅ Dane zostały zaktualizowane!';
        
        // Odśwież dane klienta
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE id = %d",
            $client->id
        ));
    } else {
        $error_message = '❌ Wystąpił błąd podczas zapisywania danych.';
    }
}
?>

<div class="ssm-page-header">
    <h1>👤 Moje dane</h1>
    <p>Edytuj swoje dane osobowe</p>
</div>

<?php if ($success_message): ?>
    <div class="ssm-notice ssm-notice-success">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="ssm-notice ssm-notice-error">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="ssm-form-container">
    <form method="post" class="ssm-edit-form">
        
        <div class="ssm-form-section">
            <h3>Dane osobowe</h3>
            
            <div class="ssm-form-row">
                <div class="ssm-form-group">
                    <label for="first_name">Imię *</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?php echo esc_attr($client->first_name); ?>"
                           class="ssm-input">
                </div>
                
                <div class="ssm-form-group">
                    <label for="last_name">Nazwisko *</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?php echo esc_attr($client->last_name); ?>"
                           class="ssm-input">
                </div>
            </div>
            
            <div class="ssm-form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" disabled
                       value="<?php echo esc_attr($client->email); ?>"
                       class="ssm-input">
                <small class="ssm-help-text">Email nie może być zmieniony. Kontakt z administracją.</small>
            </div>
            
            <div class="ssm-form-group">
                <label for="phone">Telefon</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo esc_attr($client->phone); ?>"
                       class="ssm-input"
                       placeholder="+48 123 456 789">
            </div>
            
            <div class="ssm-form-group">
                <label for="address">Adres</label>
                <textarea id="address" name="address" rows="3"
                          class="ssm-input"
                          placeholder="Ulica, numer domu/mieszkania&#10;Kod pocztowy Miasto"><?php echo esc_textarea($client->address); ?></textarea>
            </div>
        </div>
        
        <div class="ssm-form-actions">
            <button type="submit" name="ssm_update_my_data" class="ssm-btn ssm-btn-primary">
                💾 Zapisz zmiany
            </button>
        </div>
        
    </form>
</div>

<!-- Informacje o koncie -->
<div class="ssm-account-info">
    <h3>ℹ️ Informacje o koncie</h3>
    <table class="ssm-info-table">
        <tr>
            <td><strong>Data rejestracji:</strong></td>
            <td><?php echo date('d.m.Y', strtotime($client->created_at)); ?></td>
        </tr>
        <tr>
            <td><strong>Login WordPress:</strong></td>
            <td><?php echo esc_html($current_user->user_login); ?></td>
        </tr>
        <tr>
            <td><strong>ID klienta:</strong></td>
            <td>#<?php echo $client->id; ?></td>
        </tr>
    </table>
</div>

<style>
.ssm-form-container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.ssm-edit-form {
    max-width: 800px;
}

.ssm-form-section {
    margin-bottom: 30px;
}

.ssm-form-section h3 {
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
    color: #2c3e50;
}

.ssm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.ssm-form-group {
    margin-bottom: 20px;
}

.ssm-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.ssm-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.ssm-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.ssm-input:disabled {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

.ssm-help-text {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    color: #6c757d;
}

.ssm-form-actions {
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.ssm-btn {
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.ssm-btn-primary {
    background: #3498db;
    color: white;
}

.ssm-btn-primary:hover {
    background: #2980b9;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

.ssm-notice {
    padding: 15px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.ssm-notice-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}

.ssm-notice-error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
}

.ssm-account-info {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
}

.ssm-account-info h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.ssm-info-table {
    width: 100%;
}

.ssm-info-table td {
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.ssm-info-table td:first-child {
    width: 200px;
    color: #6c757d;
}

@media (max-width: 768px) {
    .ssm-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
