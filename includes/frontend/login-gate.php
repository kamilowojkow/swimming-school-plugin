<?php
// Shortcode: [swimming_login_gate]
// Bramka logowania i rejestracji dla rodziców
if (!defined('ABSPATH')) exit;

// Obsługa rejestracji
$registration_message = '';
$registration_error = '';

if (isset($_POST['ssm_register'])) {
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Walidacja
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $registration_error = 'Wszystkie pola są wymagane.';
    } elseif ($password !== $password_confirm) {
        $registration_error = 'Hasła nie są identyczne.';
    } elseif (strlen($password) < 6) {
        $registration_error = 'Hasło musi mieć minimum 6 znaków.';
    } elseif (email_exists($email)) {
        $registration_error = 'Ten adres email jest już zarejestrowany.';
    } else {
        global $wpdb;
        
        // Utwórz użytkownika WordPress
        $username = sanitize_user($email);
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $registration_error = 'Błąd podczas tworzenia konta: ' . $user_id->get_error_message();
        } else {
            // Ustaw dane użytkownika
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'role' => 'subscriber'
            ));
            
            // Dodaj do tabeli clients
            $wpdb->insert(
                $wpdb->prefix . 'ssm_clients',
                array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'created_at' => current_time('mysql')
                )
            );
            
            // Automatyczne logowanie
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            $registration_message = 'Konto zostało utworzone! Trwa przekierowanie...';
            
            // Przekierowanie po 2 sekundach
            echo '<meta http-equiv="refresh" content="2">';
        }
    }
}

// Jeśli użytkownik jest zalogowany, przekieruj lub pokaż komunikat
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    ?>
    <div class="ssm-gate-logged-in">
        <div class="ssm-gate-box">
            <div class="ssm-gate-icon">✅</div>
            <h2>Jesteś zalogowany!</h2>
            <p>Witaj, <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
            <p>Email: <?php echo esc_html($current_user->user_email); ?></p>
            <div class="ssm-gate-buttons">
                <a href="<?php echo home_url('/panel-rodzica'); ?>" class="ssm-btn ssm-btn-primary">
                    📋 Przejdź do panelu rodzica
                </a>
                <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="ssm-btn ssm-btn-secondary">
                    Wyloguj się
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}

?>

<div class="ssm-login-gate">
    
    <!-- Nagłówek -->
    <div class="ssm-gate-header">
        <h2>Panel Rodzica - Szkółka Pływania</h2>
        <p>Zaloguj się lub załóż konto aby zobaczyć harmonogram zajęć swoich dzieci</p>
    </div>
    
    <div class="ssm-gate-container">
        
        <!-- Zakładki -->
        <div class="ssm-gate-tabs">
            <button class="ssm-gate-tab active" data-tab="login">
                🔑 Logowanie
            </button>
            <button class="ssm-gate-tab" data-tab="register">
                ✨ Rejestracja
            </button>
        </div>
        
        <!-- Formularz logowania -->
        <div class="ssm-gate-content active" id="gate-login">
            <div class="ssm-gate-box">
                <h3>Zaloguj się</h3>
                
                <?php 
                $login_error = isset($_GET['login']) && $_GET['login'] === 'failed' ? 'Nieprawidłowy email lub hasło.' : '';
                if ($login_error): ?>
                    <div class="ssm-gate-error"><?php echo $login_error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo wp_login_url(get_permalink()); ?>" class="ssm-gate-form">
                    <div class="ssm-form-group">
                        <label for="log">Email</label>
                        <input type="text" name="log" id="log" class="ssm-input" required 
                               placeholder="twoj@email.com">
                    </div>
                    
                    <div class="ssm-form-group">
                        <label for="pwd">Hasło</label>
                        <input type="password" name="pwd" id="pwd" class="ssm-input" required 
                               placeholder="••••••••">
                    </div>
                    
                    <div class="ssm-form-group ssm-form-checkbox">
                        <label>
                            <input type="checkbox" name="rememberme" value="forever">
                            Zapamiętaj mnie
                        </label>
                    </div>
                    
                    <input type="hidden" name="redirect_to" value="<?php echo get_permalink(); ?>">
                    
                    <button type="submit" class="ssm-btn ssm-btn-primary ssm-btn-block">
                        Zaloguj się
                    </button>
                    
                    <div class="ssm-gate-links">
                        <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>">
                            Nie pamiętam hasła
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Formularz rejestracji -->
        <div class="ssm-gate-content" id="gate-register">
            <div class="ssm-gate-box">
                <h3>Załóż konto</h3>
                
                <?php if ($registration_error): ?>
                    <div class="ssm-gate-error"><?php echo $registration_error; ?></div>
                <?php endif; ?>
                
                <?php if ($registration_message): ?>
                    <div class="ssm-gate-success"><?php echo $registration_message; ?></div>
                <?php endif; ?>
                
                <form method="post" class="ssm-gate-form">
                    <div class="ssm-form-row">
                        <div class="ssm-form-group">
                            <label for="reg-first-name">Imię *</label>
                            <input type="text" name="first_name" id="reg-first-name" class="ssm-input" required 
                                   value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
                        </div>
                        
                        <div class="ssm-form-group">
                            <label for="reg-last-name">Nazwisko *</label>
                            <input type="text" name="last_name" id="reg-last-name" class="ssm-input" required 
                                   value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="ssm-form-group">
                        <label for="reg-email">Email *</label>
                        <input type="email" name="email" id="reg-email" class="ssm-input" required 
                               placeholder="twoj@email.com"
                               value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                        <small>Użyjesz tego emaila do logowania</small>
                    </div>
                    
                    <div class="ssm-form-group">
                        <label for="reg-phone">Telefon</label>
                        <input type="tel" name="phone" id="reg-phone" class="ssm-input" 
                               placeholder="+48 123 456 789"
                               value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="ssm-form-row">
                        <div class="ssm-form-group">
                            <label for="reg-password">Hasło *</label>
                            <input type="password" name="password" id="reg-password" class="ssm-input" required 
                                   placeholder="Min. 6 znaków">
                        </div>
                        
                        <div class="ssm-form-group">
                            <label for="reg-password-confirm">Powtórz hasło *</label>
                            <input type="password" name="password_confirm" id="reg-password-confirm" class="ssm-input" required>
                        </div>
                    </div>
                    
                    <div class="ssm-form-group ssm-form-checkbox">
                        <label>
                            <input type="checkbox" name="terms" required>
                            Akceptuję <a href="/regulamin" target="_blank">regulamin</a> i 
                            <a href="/polityka-prywatnosci" target="_blank">politykę prywatności</a>
                        </label>
                    </div>
                    
                    <button type="submit" name="ssm_register" class="ssm-btn ssm-btn-primary ssm-btn-block">
                        Załóż konto
                    </button>
                    
                    <div class="ssm-gate-info">
                        <p><small>Po rejestracji administrator przypisze Twoje dzieci do konta.</small></p>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
    
</div>

<script>
// Przełączanie zakładek
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.ssm-gate-tab');
    const contents = document.querySelectorAll('.ssm-gate-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Usuń active ze wszystkich
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Dodaj active do klikniętego
            this.classList.add('active');
            document.getElementById('gate-' + targetTab).classList.add('active');
        });
    });
    
    // Walidacja haseł
    const password = document.getElementById('reg-password');
    const passwordConfirm = document.getElementById('reg-password-confirm');
    
    if (passwordConfirm) {
        passwordConfirm.addEventListener('input', function() {
            if (password.value !== this.value) {
                this.setCustomValidity('Hasła nie są identyczne');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>
