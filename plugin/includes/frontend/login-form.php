<?php
// Shortcode: [swimming_login]
// Bramka logowania/rejestracji dla rodziców
if (!defined('ABSPATH')) exit;

global $wpdb;

// Sprawdź czy jest wybrany kurs
$selected_class_id = isset($_GET['select_class']) ? intval($_GET['select_class']) : 0;
$selected_class = null;

if ($selected_class_id) {
    $selected_class = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, f.name as facility_name,
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions 
                 WHERE class_id = c.id AND session_date >= CURDATE() AND status = 'scheduled') as sessions_remaining
         FROM {$wpdb->prefix}ssm_classes c
         LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
         LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
         WHERE c.id = %d",
        $selected_class_id
    ));
    
    if ($selected_class) {
        $selected_class->price_proportional = $selected_class->price_per_session * $selected_class->sessions_remaining;
    }
}

// Jeśli użytkownik już zalogowany i ma wybrany kurs - przekieruj do dodania dziecka
if (is_user_logged_in() && $selected_class_id) {
    $current_user = wp_get_current_user();
    
    // Sprawdź czy ma konto klienta
    $client = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
        $current_user->user_email
    ));
    
    if ($client) {
        // OBSŁUGA POST - zapisanie istniejącego dziecka
        if (isset($_POST['enroll_existing'])) {
            $child_id = intval($_POST['existing_child']);
            $class_id = intval($_POST['class_id']);
            $client_id = intval($_POST['client_id']);
            
            // Dodaj zapis
            $result = $wpdb->insert($wpdb->prefix . 'ssm_enrollments', array(
                'client_id' => $client_id,
                'child_id' => $child_id,
                'class_id' => $class_id,
                'enrollment_date' => current_time('mysql'),
                'status' => 'active'
            ));
            
            if ($result) {
                ?>
                <div class="ssm-enrollment-flow">
                    <div class="ssm-flow-header">
                        <h2>Zapis na kurs</h2>
                        <div class="ssm-flow-steps">
                            <div class="ssm-step completed">✓ Konto</div>
                            <div class="ssm-step completed">✓ Dodaj dziecko</div>
                            <div class="ssm-step completed">✓ Potwierdzenie</div>
                        </div>
                    </div>
                    
                    <div class="ssm-notice ssm-notice-success">
                        <h3>✅ Zapis zakończony!</h3>
                        <p>Dziecko zostało zapisane na kurs.</p>
                        <?php
                        $child = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}ssm_children WHERE id = %d",
                            $child_id
                        ));
                        ?>
                        <p><strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong> jest teraz zapisane na kurs <strong><?php echo esc_html($selected_class->name); ?></strong>.</p>
                        <p><a href="<?php echo home_url('/panel-rodzica'); ?>" class="ssm-btn ssm-btn-primary">Przejdź do panelu rodzica</a></p>
                    </div>
                </div>
                <?php
                return;
            }
        }
        
        // OBSŁUGA POST - dodanie nowego dziecka i zapis
        if (isset($_POST['add_child_and_enroll'])) {
            $class_id = intval($_POST['class_id']);
            $client_id = intval($_POST['client_id']);
            
            // Dodaj dziecko
            $wpdb->insert($wpdb->prefix . 'ssm_children', array(
                'first_name' => sanitize_text_field($_POST['child_first_name']),
                'last_name' => sanitize_text_field($_POST['child_last_name']),
                'date_of_birth' => sanitize_text_field($_POST['child_dob']),
                'medical_notes' => sanitize_textarea_field($_POST['child_medical']),
                'active' => 1,
                'created_at' => current_time('mysql')
            ));
            
            $child_id = $wpdb->insert_id;
            
            // Przypisz dziecko do rodzica
            $wpdb->insert($wpdb->prefix . 'ssm_client_children', array(
                'client_id' => $client_id,
                'child_id' => $child_id,
                'relationship' => 'parent',
                'primary_contact' => 1,
                'created_at' => current_time('mysql')
            ));
            
            // Zapisz na kurs
            $wpdb->insert($wpdb->prefix . 'ssm_enrollments', array(
                'client_id' => $client_id,
                'child_id' => $child_id,
                'class_id' => $class_id,
                'enrollment_date' => current_time('mysql'),
                'status' => 'active'
            ));
            
            ?>
            <div class="ssm-enrollment-flow">
                <div class="ssm-flow-header">
                    <h2>Zapis na kurs</h2>
                    <div class="ssm-flow-steps">
                        <div class="ssm-step completed">✓ Konto</div>
                        <div class="ssm-step completed">✓ Dodaj dziecko</div>
                        <div class="ssm-step completed">✓ Potwierdzenie</div>
                    </div>
                </div>
                
                <div class="ssm-notice ssm-notice-success">
                    <h3>✅ Wszystko gotowe!</h3>
                    <p>Dziecko <strong><?php echo esc_html($_POST['child_first_name'] . ' ' . $_POST['child_last_name']); ?></strong> zostało dodane i zapisane na kurs <strong><?php echo esc_html($selected_class->name); ?></strong>.</p>
                    <p><a href="<?php echo home_url('/panel-rodzica'); ?>" class="ssm-btn ssm-btn-primary">Przejdź do panelu rodzica</a></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // FORMULARZ - jeśli nie ma POST
        ?>
        <div class="ssm-enrollment-flow">
            <div class="ssm-flow-header">
                <h2>Zapis na kurs</h2>
                <div class="ssm-flow-steps">
                    <div class="ssm-step completed">✓ Konto</div>
                    <div class="ssm-step active">2. Dodaj dziecko</div>
                    <div class="ssm-step">3. Potwierdzenie</div>
                </div>
            </div>
            
            <?php if ($selected_class): ?>
                <div class="ssm-selected-class">
                    <h3>Wybrany kurs:</h3>
                    <div class="ssm-class-info">
                        <strong><?php echo esc_html($selected_class->name); ?></strong><br>
                        <?php echo esc_html($selected_class->facility_name); ?> | 
                        <?php echo esc_html($selected_class->instructor_name); ?><br>
                        <span style="color:#2271b1; font-size:18px; font-weight:600;">
                            <?php echo number_format($selected_class->price_proportional, 2); ?> PLN
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            // Pobierz dzieci klienta
            $children = $wpdb->get_results($wpdb->prepare(
                "SELECT ch.*, TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as age
                 FROM {$wpdb->prefix}ssm_children ch
                 JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
                 WHERE cc.client_id = %d AND ch.active = 1",
                $client->id
            ));
            
            if ($children): ?>
                <div class="ssm-existing-children">
                    <h3>Twoje dzieci:</h3>
                    <form method="post" action="">
                        <?php foreach ($children as $child): ?>
                            <label class="ssm-child-option">
                                <input type="radio" name="existing_child" value="<?php echo $child->id; ?>" required>
                                <span><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?> (<?php echo $child->age; ?> lat)</span>
                            </label>
                        <?php endforeach; ?>
                        
                        <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                        <input type="hidden" name="client_id" value="<?php echo $client->id; ?>">
                        <button type="submit" name="enroll_existing" class="ssm-btn ssm-btn-primary">
                            Zapisz wybrane dziecko na kurs
                        </button>
                    </form>
                    
                    <p style="text-align:center; margin:20px 0;">lub</p>
                </div>
            <?php endif; ?>
            
            <div class="ssm-add-child-form">
                <h3>Dodaj nowe dziecko:</h3>
                
                <form method="post" class="ssm-login-form">
                    <div class="ssm-form-row">
                        <div class="ssm-form-group">
                            <label for="child_first_name">Imię dziecka *</label>
                            <input type="text" id="child_first_name" name="child_first_name" required class="ssm-input">
                        </div>
                        
                        <div class="ssm-form-group">
                            <label for="child_last_name">Nazwisko dziecka *</label>
                            <input type="text" id="child_last_name" name="child_last_name" required class="ssm-input">
                        </div>
                    </div>
                    
                    <div class="ssm-form-group">
                        <label for="child_dob">Data urodzenia *</label>
                        <input type="date" id="child_dob" name="child_dob" required class="ssm-input">
                    </div>
                    
                    <div class="ssm-form-group">
                        <label for="child_medical">Uwagi medyczne (opcjonalne)</label>
                        <textarea id="child_medical" name="child_medical" class="ssm-input" rows="3"></textarea>
                    </div>
                    
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    <input type="hidden" name="client_id" value="<?php echo $client->id; ?>">
                    
                    <button type="submit" name="add_child_and_enroll" class="ssm-btn ssm-btn-primary ssm-btn-block">
                        Dodaj dziecko i zapisz na kurs
                    </button>
                </form>
            </div>
        </div>
        <?php
        
        return;
    }
}

// Jeśli użytkownik już zalogowany (bez wybranego kursu)
if (is_user_logged_in()) {
    echo '<div class="ssm-already-logged">';
    echo '<p>✅ Jesteś już zalogowany!</p>';
    $current_user = wp_get_current_user();
    echo '<p>Witaj, <strong>' . esc_html($current_user->display_name) . '</strong></p>';
    echo '<p><a href="' . home_url('/panel-rodzica') . '" class="ssm-btn ssm-btn-primary">Przejdź do panelu rodzica</a></p>';
    echo '<p><a href="' . wp_logout_url(get_permalink()) . '" class="ssm-btn ssm-btn-secondary">Wyloguj się</a></p>';
    echo '</div>';
    return;
}

// Obsługa rejestracji
$registration_errors = array();
$registration_success = false;

if (isset($_POST['ssm_register_submit'])) {
    // Walidacja
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    if (empty($first_name)) $registration_errors[] = 'Podaj imię';
    if (empty($last_name)) $registration_errors[] = 'Podaj nazwisko';
    if (empty($email)) $registration_errors[] = 'Podaj email';
    if (!is_email($email)) $registration_errors[] = 'Nieprawidłowy email';
    if (email_exists($email)) $registration_errors[] = 'Ten email jest już zarejestrowany';
    if (empty($password)) $registration_errors[] = 'Podaj hasło';
    if (strlen($password) < 6) $registration_errors[] = 'Hasło musi mieć minimum 6 znaków';
    if ($password !== $password_confirm) $registration_errors[] = 'Hasła się nie zgadzają';
    
    if (empty($registration_errors)) {
        // Utwórz konto WordPress
        $user_id = wp_create_user($email, $password, $email);
        
        if (!is_wp_error($user_id)) {
            // Zaktualizuj dane użytkownika
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ));
            
            // Dodaj użytkownika do bazy klientów
            $wpdb->insert($wpdb->prefix . 'ssm_clients', array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'created_at' => current_time('mysql')
            ));
            
            // Zaloguj automatycznie
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            // Przekieruj z parametrem kursu jeśli był wybrany
            if ($selected_class_id) {
                wp_safe_redirect(add_query_arg('select_class', $selected_class_id, get_permalink()));
                exit;
            }
            
            $registration_success = true;
        } else {
            $registration_errors[] = 'Błąd podczas tworzenia konta: ' . $user_id->get_error_message();
        }
    }
}

// Obsługa logowania
$login_error = '';
if (isset($_POST['ssm_login_submit'])) {
    $email = sanitize_email($_POST['login_email']);
    $password = $_POST['login_password'];
    
    $user = wp_authenticate($email, $password);
    
    if (is_wp_error($user)) {
        $login_error = 'Nieprawidłowy email lub hasło';
    } else {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Przekieruj z parametrem kursu jeśli był wybrany
        if ($selected_class_id) {
            wp_safe_redirect(add_query_arg('select_class', $selected_class_id, get_permalink()));
        } else {
            wp_safe_redirect(get_permalink());
        }
        exit;
    }
}
?>

<div class="ssm-login-page">
    
    <?php if ($selected_class): ?>
        <div class="ssm-selected-course-banner">
            <h3>📚 Wybrany kurs:</h3>
            <div class="ssm-course-details">
                <strong><?php echo esc_html($selected_class->name); ?></strong><br>
                <?php echo esc_html($selected_class->facility_name); ?> | 
                <?php echo esc_html($selected_class->instructor_name); ?><br>
                <span style="color:#28a745; font-size:20px; font-weight:700;">
                    <?php echo number_format($selected_class->price_proportional, 2); ?> PLN
                </span>
            </div>
            <p class="ssm-banner-instruction">
                👇 Zaloguj się lub załóż konto aby zapisać dziecko na ten kurs
            </p>
        </div>
    <?php endif; ?>
    
    <?php if ($registration_success): ?>
        <div class="ssm-notice ssm-notice-success">
            <h3>✅ Rejestracja zakończona pomyślnie!</h3>
            <p>Twoje konto zostało utworzone i jesteś teraz zalogowany.</p>
            <?php if ($selected_class_id): ?>
                <p>Teraz możesz dodać dziecko i zapisać je na wybrany kurs.</p>
            <?php else: ?>
                <p><a href="<?php echo home_url('/panel-rodzica'); ?>" class="ssm-btn ssm-btn-primary">Przejdź do panelu rodzica</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
    
    <div class="ssm-login-tabs">
        <button class="ssm-login-tab active" data-tab="login">Logowanie</button>
        <button class="ssm-login-tab" data-tab="register">Rejestracja</button>
    </div>
    
    <!-- Reszta formularzy bez zmian... -->
    <div class="ssm-tab-panel active" id="login-panel">
        <div class="ssm-login-form-wrapper">
            <h2>Zaloguj się</h2>
            
            <?php if ($login_error): ?>
                <div class="ssm-notice ssm-notice-error">
                    <p>❌ <?php echo esc_html($login_error); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" class="ssm-login-form">
                <div class="ssm-form-group">
                    <label for="login_email">Email *</label>
                    <input type="email" id="login_email" name="login_email" required 
                           class="ssm-input" placeholder="twoj@email.com">
                </div>
                
                <div class="ssm-form-group">
                    <label for="login_password">Hasło *</label>
                    <input type="password" id="login_password" name="login_password" required 
                           class="ssm-input" placeholder="••••••••">
                </div>
                
                <button type="submit" name="ssm_login_submit" class="ssm-btn ssm-btn-primary ssm-btn-block">
                    Zaloguj się
                </button>
                
                <p class="ssm-form-footer">
                    <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>">Zapomniałeś hasła?</a>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Formularz rejestracji -->
    <div class="ssm-tab-panel" id="register-panel">
        <div class="ssm-login-form-wrapper">
            <h2>Zarejestruj się</h2>
            <p class="ssm-form-description">Utwórz konto aby uzyskać dostęp do panelu rodzica</p>
            
            <?php if (!empty($registration_errors)): ?>
                <div class="ssm-notice ssm-notice-error">
                    <ul style="margin:0; padding-left:20px;">
                        <?php foreach ($registration_errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" class="ssm-login-form">
                <div class="ssm-form-row">
                    <div class="ssm-form-group">
                        <label for="first_name">Imię *</label>
                        <input type="text" id="first_name" name="first_name" required 
                               class="ssm-input" placeholder="Jan"
                               value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
                    </div>
                    
                    <div class="ssm-form-group">
                        <label for="last_name">Nazwisko *</label>
                        <input type="text" id="last_name" name="last_name" required 
                               class="ssm-input" placeholder="Kowalski"
                               value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="ssm-form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           class="ssm-input" placeholder="jan.kowalski@email.com"
                           value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                    <small class="ssm-help-text">Będzie używany do logowania</small>
                </div>
                
                <div class="ssm-form-group">
                    <label for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" 
                           class="ssm-input" placeholder="123-456-789"
                           value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="ssm-form-group">
                    <label for="password">Hasło *</label>
                    <input type="password" id="password" name="password" required 
                           class="ssm-input" placeholder="Minimum 6 znaków">
                    <small class="ssm-help-text">Minimum 6 znaków</small>
                </div>
                
                <div class="ssm-form-group">
                    <label for="password_confirm">Powtórz hasło *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required 
                           class="ssm-input" placeholder="Powtórz hasło">
                </div>
                
                <button type="submit" name="ssm_register_submit" class="ssm-btn ssm-btn-primary ssm-btn-block">
                    Zarejestruj się
                </button>
                
                <p class="ssm-form-footer">
                    Masz już konto? <a href="#" class="ssm-switch-tab" data-tab="login">Zaloguj się</a>
                </p>
            </form>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<style>
.ssm-login-page {
    max-width: 500px;
    margin: 40px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ssm-login-tabs {
    display: flex;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.ssm-login-tab {
    flex: 1;
    padding: 15px;
    background: transparent;
    border: none;
    font-size: 16px;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s;
}

.ssm-login-tab:hover {
    background: #e9ecef;
}

.ssm-login-tab.active {
    background: #fff;
    color: #2271b1;
    border-bottom: 3px solid #2271b1;
}

.ssm-tab-panel {
    display: none;
    padding: 30px;
}

.ssm-tab-panel.active {
    display: block;
}

.ssm-login-form-wrapper h2 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

.ssm-form-description {
    margin: 0 0 20px 0;
    color: #6c757d;
}

.ssm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.ssm-form-group {
    margin-bottom: 20px;
}

.ssm-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #495057;
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
    border-color: #2271b1;
    box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
}

.ssm-help-text {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    color: #6c757d;
}

.ssm-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.ssm-btn-primary {
    background: #2271b1;
    color: white;
}

.ssm-btn-primary:hover {
    background: #1557a0;
    color: white;
}

.ssm-btn-secondary {
    background: #6c757d;
    color: white;
}

.ssm-btn-secondary:hover {
    background: #545b62;
}

.ssm-btn-block {
    width: 100%;
    text-align: center;
}

.ssm-form-footer {
    margin-top: 20px;
    text-align: center;
    color: #6c757d;
}

.ssm-form-footer a {
    color: #2271b1;
    text-decoration: none;
}

.ssm-form-footer a:hover {
    text-decoration: underline;
}

.ssm-notice {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.ssm-notice-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.ssm-notice-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.ssm-notice-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.ssm-notice h3 {
    margin: 0 0 10px 0;
}

.ssm-already-logged {
    padding: 40px;
    text-align: center;
}

.ssm-already-logged p {
    margin: 10px 0;
}

@media (max-width: 576px) {
    .ssm-login-page {
        margin: 20px;
    }
    
    .ssm-tab-panel {
        padding: 20px;
    }
    
    .ssm-form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Przełączanie zakładek
    const tabs = document.querySelectorAll('.ssm-login-tab');
    const panels = document.querySelectorAll('.ssm-tab-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(targetTab + '-panel').classList.add('active');
        });
    });
    
    // Link przełączający zakładki
    const switchLinks = document.querySelectorAll('.ssm-switch-tab');
    switchLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            const targetButton = document.querySelector('.ssm-login-tab[data-tab="' + targetTab + '"]');
            if (targetButton) {
                targetButton.click();
            }
        });
    });
});
</script>
