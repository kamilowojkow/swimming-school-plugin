<?php
// Panel Instruktorzy
if (!defined('ABSPATH')) exit;

global $wpdb;

$instructors = $wpdb->get_results("
    SELECT i.*, 
           (SELECT AVG(rating) FROM {$wpdb->prefix}ssm_session_ratings WHERE instructor_id = i.id) as avg_rating,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_session_ratings WHERE instructor_id = i.id) as total_ratings
    FROM {$wpdb->prefix}ssm_instructors i
    ORDER BY i.active DESC, i.last_name, i.first_name
");
?>

<div class="wrap">
    <h1>Instruktorzy
        <button type="button" class="page-title-action" id="ssm-add-instructor-btn">Dodaj instruktora</button>
    </h1>
    
    <!-- Formularz -->
    <div id="ssm-instructor-form-container" style="display:none; margin:20px 0;">
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; max-width:700px;">
            <h2 id="ssm-form-title">Dodaj instruktora</h2>
            <form id="ssm-instructor-form">
                <input type="hidden" id="instructor-id" name="id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="instructor-first-name">Imię *</label></th>
                        <td><input type="text" id="instructor-first-name" name="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-last-name">Nazwisko *</label></th>
                        <td><input type="text" id="instructor-last-name" name="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-email">Email</label></th>
                        <td><input type="email" id="instructor-email" name="email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-phone">Telefon</label></th>
                        <td><input type="tel" id="instructor-phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-specialization">Specjalizacja</label></th>
                        <td><input type="text" id="instructor-specialization" name="specialization" class="regular-text" 
                                placeholder="np. Kraul, Dzieci 4-6 lat, Zaawansowani"></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-hourly-rate">Stawka za zajęcia (PLN)</label></th>
                        <td>
                            <input type="number" id="instructor-hourly-rate" name="hourly_rate" class="regular-text" 
                                   step="0.01" min="0" value="0.00" placeholder="50.00">
                            <p class="description">Wynagrodzenie za jedno przeprowadzone zajęcia (potwierdzone obecnością)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="instructor-photo">Zdjęcie (URL)</label></th>
                        <td>
                            <input type="url" id="instructor-photo" name="photo" class="regular-text" 
                                   placeholder="https://example.com/photo.jpg">
                            <p class="description">Wklej link do zdjęcia lub użyj: https://ui-avatars.com/api/?name=Imie+Nazwisko</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="instructor-url">Link do profilu</label></th>
                        <td><input type="url" id="instructor-url" name="url" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-bio">Biografia</label></th>
                        <td><textarea id="instructor-bio" name="bio" class="large-text" rows="4"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="instructor-active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="instructor-active" name="active" value="1" checked>
                                Aktywny
                            </label>
                        </td>
                    </tr>
                </table>
                
                <hr style="margin: 30px 0;">
                
                <h3>🔐 Konto dostępowe (Panel Instruktora)</h3>
                <p class="description">Utwórz konto WordPress aby instruktor mógł zalogować się do panelu instruktora.</p>
                
                <div id="instructor-account-section">
                    <table class="form-table">
                        <tr id="existing-user-row" style="display:none;">
                            <th>Powiązany użytkownik</th>
                            <td>
                                <div id="existing-user-info" style="background: #d1fae5; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                                    <strong style="color: #064e3b;">✅ Konto utworzone</strong><br>
                                    <span id="existing-user-details"></span><br>
                                    <button type="button" class="button" id="reset-password-btn" style="margin-top: 10px;">
                                        🔄 Resetuj hasło (wyślij email)
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr id="create-user-row">
                            <th><label for="create-wordpress-user">Utwórz konto WordPress</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="create-wordpress-user" name="create_wordpress_user" value="1">
                                    Tak, utwórz konto dla tego instruktora
                                </label>
                                <p class="description">Automatycznie utworzy użytkownika WordPress z danymi instruktora.</p>
                            </td>
                        </tr>
                        
                        <tr id="username-row" style="display:none;">
                            <th><label for="instructor-username">Login *</label></th>
                            <td>
                                <input type="text" id="instructor-username" name="username" class="regular-text" 
                                       placeholder="np. jan.kowalski">
                                <p class="description">Login do logowania (bez polskich znaków)</p>
                            </td>
                        </tr>
                        
                        <tr id="password-row" style="display:none;">
                            <th><label for="instructor-password">Hasło *</label></th>
                            <td>
                                <input type="password" id="instructor-password" name="password" class="regular-text">
                                <button type="button" class="button" id="generate-password-btn">🎲 Wygeneruj hasło</button>
                                <p class="description">Hasło zostanie wysłane na email instruktora</p>
                                <div id="generated-password" style="display:none; background: #fff3cd; padding: 10px; margin-top: 10px; border-radius: 4px;">
                                    <strong>Wygenerowane hasło:</strong> <code id="password-display"></code>
                                    <button type="button" class="button button-small" id="copy-password-btn">📋 Kopiuj</button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr id="send-email-row" style="display:none;">
                            <th><label for="send-credentials-email">Wyślij dane logowania</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="send-credentials-email" name="send_credentials_email" value="1" checked>
                                    Wyślij email z danymi logowania do instruktora
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Zapisz instruktora</button>
                    <button type="button" class="button" id="ssm-cancel-instructor-btn">Anuluj</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Lista -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:60px;">Avatar</th>
                <th>Imię i nazwisko</th>
                <th>Kontakt</th>
                <th>Specjalizacja</th>
                <th>Stawka/zajęcia</th>
                <th style="width:120px;">⭐ Ocena</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($instructors): ?>
                <?php foreach ($instructors as $instr): ?>
                <tr>
                    <td>
                        <?php if ($instr->photo): ?>
                            <img src="<?php echo esc_url($instr->photo); ?>" 
                                 alt="<?php echo esc_attr($instr->first_name); ?>" 
                                 style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:40px; height:40px; border-radius:50%; background:#2271b1; color:white; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                <?php echo strtoupper(substr($instr->first_name, 0, 1) . substr($instr->last_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($instr->first_name . ' ' . $instr->last_name); ?></strong>
                        <?php if ($instr->url): ?>
                            <br><a href="<?php echo esc_url($instr->url); ?>" target="_blank">🔗 Profil</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($instr->email): ?>
                            📧 <?php echo esc_html($instr->email); ?><br>
                        <?php endif; ?>
                        <?php if ($instr->phone): ?>
                            📱 <?php echo esc_html($instr->phone); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($instr->specialization); ?></td>
                    <td>
                        <strong style="color: #2271b1; font-size: 16px;">
                            <?php echo number_format($instr->hourly_rate ?? 0, 2, ',', ' '); ?> PLN
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($instr->avg_rating): ?>
                            <div style="font-size: 20px; font-weight: 700; color: #f59e0b; margin-bottom: 3px;">
                                <?php echo number_format($instr->avg_rating, 1); ?> ⭐
                            </div>
                            <div style="font-size: 11px; color: #64748b;">
                                (<?php echo $instr->total_ratings; ?> <?php echo $instr->total_ratings == 1 ? 'ocena' : 'ocen'; ?>)
                            </div>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 12px;">Brak ocen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($instr->active): ?>
                            <span style="color:#00a32a;">✓ Aktywny</span>
                        <?php else: ?>
                            <span style="color:#646970;">○ Nieaktywny</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small ssm-edit-instructor" 
                                data-id="<?php echo $instr->id; ?>"
                                data-first-name="<?php echo esc_attr($instr->first_name); ?>"
                                data-last-name="<?php echo esc_attr($instr->last_name); ?>"
                                data-email="<?php echo esc_attr($instr->email); ?>"
                                data-phone="<?php echo esc_attr($instr->phone); ?>"
                                data-specialization="<?php echo esc_attr($instr->specialization); ?>"
                                data-hourly-rate="<?php echo esc_attr($instr->hourly_rate ?? 0); ?>"
                                data-photo="<?php echo esc_attr($instr->photo); ?>"
                                data-url="<?php echo esc_attr($instr->url); ?>"
                                data-bio="<?php echo esc_attr($instr->bio); ?>"
                                data-active="<?php echo $instr->active; ?>"
                                data-user-id="<?php echo $instr->user_id ?? 0; ?>">
                            Edytuj
                        </button>
                        
                        <button class="button button-small button-link-delete ssm-delete-instructor" 
                                data-id="<?php echo $instr->id; ?>">
                            Usuń
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Brak instruktorów. Dodaj pierwszego instruktora!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle sekcji tworzenia konta
    $('#create-wordpress-user').on('change', function() {
        if ($(this).is(':checked')) {
            $('#username-row, #password-row, #send-email-row').slideDown();
            $('#instructor-username, #instructor-password').prop('required', true);
            
            // Auto-wypełnij login na podstawie imienia i nazwiska
            var firstName = $('#instructor-first-name').val().toLowerCase();
            var lastName = $('#instructor-last-name').val().toLowerCase();
            if (firstName && lastName) {
                var username = firstName + '.' + lastName;
                // Usuń polskie znaki
                username = username
                    .replace(/ą/g, 'a').replace(/ć/g, 'c').replace(/ę/g, 'e')
                    .replace(/ł/g, 'l').replace(/ń/g, 'n').replace(/ó/g, 'o')
                    .replace(/ś/g, 's').replace(/ź/g, 'z').replace(/ż/g, 'z')
                    .replace(/[^a-z0-9.]/g, '');
                $('#instructor-username').val(username);
            }
        } else {
            $('#username-row, #password-row, #send-email-row').slideUp();
            $('#instructor-username, #instructor-password').prop('required', false);
        }
    });
    
    // Generuj hasło
    $('#generate-password-btn').on('click', function() {
        var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        var password = "";
        for (var i = 0; i < 12; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        $('#instructor-password').val(password);
        $('#password-display').text(password);
        $('#generated-password').slideDown();
    });
    
    // Kopiuj hasło
    $('#copy-password-btn').on('click', function() {
        var password = $('#password-display').text();
        navigator.clipboard.writeText(password).then(function() {
            alert('Hasło skopiowane do schowka!');
        });
    });
    
    // Reset hasła
    $('#reset-password-btn').on('click', function() {
        var instructorId = $('#instructor-id').val();
        if (!instructorId) return;
        
        if (confirm('Czy na pewno chcesz zresetować hasło? Nowe hasło zostanie wysłane na email instruktora.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ssm_reset_instructor_password',
                    instructor_id: instructorId
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                    } else {
                        alert('❌ ' + response.data);
                    }
                }
            });
        }
    });
});
</script>
