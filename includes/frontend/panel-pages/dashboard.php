<?php
// Panel główny - Dashboard
if (!defined('ABSPATH')) exit;

// Pobierz zapisy
$enrollments = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, c.name as class_name, c.day_of_week, c.time_start,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            f.name as facility_name
     FROM {$wpdb->prefix}ssm_enrollments e
     LEFT JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE e.client_id = %d AND e.status = 'active'",
    $client->id
));

// Pobierz najbliższe zajęcia
$upcoming = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
            f.name as facility_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            e.id as enrollment_id
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_enrollments e ON s.class_id = e.class_id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE e.client_id = %d 
     AND s.session_date >= CURDATE()
     AND s.status = 'scheduled'
     ORDER BY s.session_date ASC, s.time_start ASC
     LIMIT 5",
    $client->id
));

// Sprawdź czy tabela absences istnieje
$absences_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ssm_absences'") ? true : false;

// Dla każdej sesji dodaj informacje o nieobecnościach jeśli tabela istnieje
if ($absences_table_exists && $upcoming) {
    foreach ($upcoming as $session) {
        // Sprawdź czy jest nieobecność
        $session->is_absent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
             WHERE session_id = %d AND child_id IN (
                 SELECT child_id FROM {$wpdb->prefix}ssm_enrollments WHERE id = %d
             )",
            $session->id, $session->enrollment_id
        ));
        
        // Ile wykorzystano nieobecności
        $session->used_absences = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
             WHERE enrollment_id = %d AND can_makeup = 1",
            $session->enrollment_id
        ));
        
        // Pobierz max_absences i allow_makeups z klasy
        $class_settings = $wpdb->get_row($wpdb->prepare(
            "SELECT max_absences, allow_makeups FROM {$wpdb->prefix}ssm_classes WHERE id = (
                SELECT class_id FROM {$wpdb->prefix}ssm_sessions WHERE id = %d
            )",
            $session->id
        ));
        
        $session->max_absences = $class_settings ? ($class_settings->max_absences ?? 2) : 2;
        $session->allow_makeups = $class_settings ? ($class_settings->allow_makeups ?? 1) : 1;
    }
} else {
    // Jeśli tabeli nie ma, ustaw domyślne wartości
    foreach ($upcoming as $session) {
        $session->is_absent = 0;
        $session->used_absences = 0;
        $session->max_absences = 2;
        $session->allow_makeups = 1;
    }
}
?>

<div class="ssm-page-header">
    <h1>Witaj, <?php echo esc_html($client->first_name); ?>! 👋</h1>
    <p>Oto Twój panel rodzica</p>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid">
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon">👶</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value"><?php echo count($children); ?></div>
            <div class="ssm-stat-label">Dzieci</div>
        </div>
    </div>
    
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon">🏊</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value"><?php echo count($enrollments); ?></div>
            <div class="ssm-stat-label">Aktywne kursy</div>
        </div>
    </div>
    
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon">📅</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value"><?php echo count($upcoming); ?></div>
            <div class="ssm-stat-label">Najbliższe zajęcia</div>
        </div>
    </div>
</div>

<?php 
// Sprawdź nieobecności do odrobienia
$pending_makeups = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     WHERE e.client_id = %d AND a.can_makeup = 1 
     AND a.makeup_session_id IS NULL AND a.status = 'reported'",
    $client->id
));

if ($pending_makeups > 0):
?>
    <div class="ssm-notice ssm-notice-warning" style="margin: 20px 0;">
        <strong>⚠️ Masz <?php echo $pending_makeups; ?> nieobecność<?php echo $pending_makeups > 1 ? 'i' : 'ć'; ?> do odrobienia!</strong><br>
        Zapisz się na zajęcia zastępcze w zakładce 
        <a href="?panel_page=makeup" style="color: #d97706; font-weight: 600; text-decoration: underline;">Odrabianie</a>.
    </div>
<?php endif; ?>

<!-- Modal zgłaszania nieobecności -->
<div class="ssm-absence-modal" id="absence-modal">
    <div class="ssm-modal-content">
        <div class="ssm-modal-header">
            <h3>Zgłoś nieobecność</h3>
        </div>
        <div class="ssm-modal-body">
            <div class="ssm-modal-info" id="absence-info">
                <!-- Informacje o zajęciach wypełniane przez JS -->
            </div>
            
            <div class="ssm-form-group">
                <label for="absence-reason">Powód nieobecności (opcjonalnie)</label>
                <textarea id="absence-reason" rows="3" class="ssm-input" 
                          placeholder="np. Choroba, wyjazd..."></textarea>
            </div>
            
            <div class="ssm-notice" style="background:#fff3cd; border-left:4px solid #f39c12; padding:12px; margin-top:15px;">
                <p style="margin:0; font-size:14px;">
                    ⚠️ <strong>Ważne:</strong> Zgłoszona nieobecność będzie mogła być odrobiona na innych zajęciach. 
                    Skontaktujemy się z Tobą w celu ustalenia terminu.
                </p>
            </div>
        </div>
        <div class="ssm-modal-footer">
            <button type="button" class="ssm-btn ssm-btn-secondary" id="cancel-absence">
                Anuluj
            </button>
            <button type="button" class="ssm-btn ssm-btn-primary" id="confirm-absence">
                Zgłoś nieobecność
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Automatyczne wykrycie URL do admin-ajax.php
    var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    
    // Fallback - jeśli PHP nie zadziała, użyj window.location
    if (!ajaxurl || ajaxurl.indexOf('<?php') !== -1) {
        var baseUrl = window.location.protocol + '//' + window.location.hostname;
        var path = window.location.pathname;
        // Usuń wszystko po /wp-content lub /panel-rodzica
        var wpPath = path.substring(0, path.indexOf('/wp-content') !== -1 ? path.indexOf('/wp-content') : path.indexOf('/panel-rodzica'));
        if (!wpPath) wpPath = '';
        ajaxurl = baseUrl + wpPath + '/wp-admin/admin-ajax.php';
    }
    
    console.log('AJAX URL detected:', ajaxurl);
    console.log('Window location:', window.location.href);
    
    // Sprawdź czy jest komunikat o statusie nieobecności
    var urlParams = new URLSearchParams(window.location.search);
    var absenceStatus = urlParams.get('absence_status');
    if (absenceStatus === 'success') {
        alert('✅ Nieobecność została zgłoszona. Skontaktujemy się w celu ustalenia terminu odrobienia.');
        // Usuń parametr z URL
        window.history.replaceState({}, document.title, window.location.pathname + '?panel_page=dashboard');
        location.reload();
    } else if (absenceStatus === 'error') {
        alert('❌ Wystąpił błąd podczas zgłaszania nieobecności. Spróbuj ponownie.');
        window.history.replaceState({}, document.title, window.location.pathname + '?panel_page=dashboard');
    }
    
    let currentSessionId = 0;
    let currentEnrollmentId = 0;
    
    // Otwórz modal
    $('.ssm-btn-absence').on('click', function() {
        currentSessionId = $(this).data('session-id');
        currentEnrollmentId = $(this).data('enrollment-id');
        
        console.log('Session ID:', currentSessionId);
        console.log('Enrollment ID:', currentEnrollmentId);
        console.log('Button data:', $(this).data());
        
        const childName = $(this).data('child-name');
        const className = $(this).data('class-name');
        const date = $(this).data('date');
        
        $('#absence-info').html(`
            <p><strong>Dziecko:</strong> ${childName}</p>
            <p><strong>Kurs:</strong> ${className}</p>
            <p><strong>Data zajęć:</strong> ${date}</p>
            <p style="color:#999; font-size:12px;">Session ID: ${currentSessionId}, Enrollment ID: ${currentEnrollmentId}</p>
        `);
        
        $('#absence-reason').val('');
        $('#absence-modal').addClass('active');
    });
    
    // Zamknij modal
    $('#cancel-absence, #absence-modal').on('click', function(e) {
        if (e.target === this) {
            $('#absence-modal').removeClass('active');
        }
    });
    
    // Potwierdź nieobecność
    $('#confirm-absence').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Zgłaszam...');
        
        console.log('Wysyłam nieobecność:', currentSessionId, currentEnrollmentId);
        
        // WORKAROUND: Użyj ukrytego formularza zamiast AJAX
        // To omija CORS i CSP restrictions
        var form = $('<form>', {
            method: 'POST',
            action: ajaxurl,
            style: 'display:none;'
        });
        
        form.append($('<input>', {type: 'hidden', name: 'action', value: 'ssm_report_absence'}));
        form.append($('<input>', {type: 'hidden', name: 'session_id', value: currentSessionId}));
        form.append($('<input>', {type: 'hidden', name: 'enrollment_id', value: currentEnrollmentId}));
        form.append($('<input>', {type: 'hidden', name: 'reason', value: $('#absence-reason').val()}));
        form.append($('<input>', {type: 'hidden', name: 'redirect_back', value: window.location.href}));
        
        $('body').append(form);
        form.submit();
        
        // Formularz wyśle i strona się odświeży automatycznie
    });
});
</script>


<!-- Najbliższe zajęcia -->
<div class="ssm-section">
    <h2>📅 Najbliższe zajęcia</h2>
    
    <?php if ($upcoming): ?>
        <div class="ssm-upcoming-list">
            <?php foreach ($upcoming as $session): 
                $date = new DateTime($session->session_date);
                $is_today = ($session->session_date == date('Y-m-d'));
                $is_tomorrow = ($session->session_date == date('Y-m-d', strtotime('+1 day')));
                
                // Sprawdź czy można zgłosić nieobecność (min 24h przed)
                $session_datetime = new DateTime($session->session_date . ' ' . $session->time_start);
                $now = new DateTime();
                $hours_until = ($session_datetime->getTimestamp() - $now->getTimestamp()) / 3600;
                $can_report = ($hours_until >= 24);
                
                // Sprawdź czy już zgłoszono nieobecność
                $is_absent = ($session->is_absent > 0);
                
                // Sprawdź czy wykorzystano limit nieobecności
                $absences_left = max(0, $session->max_absences - $session->used_absences);
            ?>
                <div class="ssm-upcoming-item <?php echo $is_today ? 'is-today' : ''; ?> <?php echo $is_absent ? 'is-absent' : ''; ?>">
                    <div class="ssm-upcoming-date">
                        <div class="ssm-date-day"><?php echo $date->format('d'); ?></div>
                        <div class="ssm-date-month"><?php echo $date->format('M'); ?></div>
                    </div>
                    <div class="ssm-upcoming-details">
                        <h4><?php echo esc_html($session->class_name); ?></h4>
                        <p>
                            <strong><?php echo esc_html($session->child_name); ?></strong> • 
                            <?php echo esc_html($session->facility_name); ?> • 
                            <?php echo substr($session->time_start, 0, 5); ?>-<?php echo substr($session->time_end, 0, 5); ?>
                        </p>
                        <?php if ($is_absent): ?>
                            <span class="ssm-badge ssm-badge-absent">Zgłoszona nieobecność</span>
                        <?php endif; ?>
                    </div>
                    <div class="ssm-upcoming-actions">
                        <?php if ($is_today): ?>
                            <div class="ssm-badge ssm-badge-today">DZISIAJ</div>
                        <?php elseif ($is_tomorrow): ?>
                            <div class="ssm-badge ssm-badge-soon">Jutro</div>
                        <?php endif; ?>
                        
                        <?php if (!$is_absent && $session->allow_makeups): ?>
                            <?php if ($can_report && $absences_left > 0): ?>
                                <button class="ssm-btn-absence" 
                                        data-session-id="<?php echo $session->id; ?>"
                                        data-enrollment-id="<?php echo $session->enrollment_id; ?>"
                                        data-child-name="<?php echo esc_attr($session->child_name); ?>"
                                        data-class-name="<?php echo esc_attr($session->class_name); ?>"
                                        data-date="<?php echo $date->format('d.m.Y'); ?>">
                                    🚫 Zgłoś nieobecność
                                </button>
                                <small style="color:#6c757d;">Pozostało: <?php echo $absences_left; ?>/<?php echo $session->max_absences; ?></small>
                            <?php elseif ($absences_left == 0): ?>
                                <small style="color:#e74c3c;">Wykorzystano limit nieobecności</small>
                            <?php else: ?>
                                <small style="color:#e74c3c;">Za późno (min. 24h wcześniej)</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Brak zaplanowanych zajęć w najbliższym czasie.</p>
    <?php endif; ?>
</div>

<!-- Twoje dzieci -->
<div class="ssm-section">
    <h2>👶 Twoje dzieci</h2>
    
    <?php if ($children): ?>
        <div class="ssm-children-grid">
            <?php foreach ($children as $child): 
                // Ile kursów ma dziecko
                $child_courses = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
                     WHERE child_id = %d AND status = 'active'",
                    $child->id
                ));
            ?>
                <div class="ssm-child-card">
                    <div class="ssm-child-avatar">
                        <?php echo strtoupper(substr($child->first_name, 0, 1)); ?>
                    </div>
                    <h4><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></h4>
                    <p><?php echo $child->age; ?> lat</p>
                    <div class="ssm-child-courses">
                        <?php echo $child_courses; ?> <?php echo $child_courses == 1 ? 'kurs' : 'kursy'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Nie masz przypisanych dzieci.</p>
    <?php endif; ?>
</div>

