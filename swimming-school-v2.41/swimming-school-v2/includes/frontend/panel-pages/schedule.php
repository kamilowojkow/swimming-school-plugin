<?php
// Harmonogram - szczegółowy harmonogram zajęć wszystkich dzieci
if (!defined('ABSPATH')) exit;

// Pobierz wszystkie przyszłe zajęcia
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
            f.name as facility_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            e.id as enrollment_id
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_enrollments e ON s.class_id = e.class_id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
     WHERE e.client_id = %d 
     AND s.session_date >= CURDATE()
     AND s.status = 'scheduled'
     AND e.status = 'active'
     ORDER BY s.session_date ASC, s.time_start ASC",
    $client->id
));

// Sprawdź czy tabela absences istnieje
$absences_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ssm_absences'") ? true : false;

// Dla każdej sesji dodaj informacje o nieobecnościach jeśli tabela istnieje
if ($absences_table_exists && $sessions) {
    foreach ($sessions as $session) {
        $session->is_absent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
             WHERE session_id = %d AND child_id IN (
                 SELECT child_id FROM {$wpdb->prefix}ssm_enrollments WHERE id = %d
             )",
            $session->id, $session->enrollment_id
        ));
        
        $session->used_absences = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
             WHERE enrollment_id = %d AND can_makeup = 1",
            $session->enrollment_id
        ));
        
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
    foreach ($sessions as $session) {
        $session->is_absent = 0;
        $session->used_absences = 0;
        $session->max_absences = 2;
        $session->allow_makeups = 1;
    }
}
?>

<div class="ssm-page-header">
    <h1>📅 Harmonogram zajęć</h1>
    <p>Wszystkie zaplanowane zajęcia Twoich dzieci</p>
</div>

<?php if ($sessions):
    $months_pl = array(1 => 'stycznia', 2 => 'lutego', 3 => 'marca', 4 => 'kwietnia', 5 => 'maja', 6 => 'czerwca', 7 => 'lipca', 8 => 'sierpnia', 9 => 'września', 10 => 'października', 11 => 'listopada', 12 => 'grudnia');
    $days_full_pl = array(1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela');
    $current_date = '';
?>
    <div class="ssm-vtimeline">
        <?php foreach ($sessions as $session):
            $date = new DateTime($session->session_date);
            $date_label = $date->format('Y-m-d');
            $is_today = ($date_label == date('Y-m-d'));
            $is_tomorrow = ($date_label == date('Y-m-d', strtotime('+1 day')));
            $is_new_date = ($date_label != $current_date);
            $current_date = $date_label;

            $session_datetime = new DateTime($session->session_date . ' ' . $session->time_start);
            $now = new DateTime();
            $hours_until = ($session_datetime->getTimestamp() - $now->getTimestamp()) / 3600;
            $can_report = ($hours_until >= 24);
            $is_absent = ($session->is_absent > 0);
            $absences_left = max(0, $session->max_absences - $session->used_absences);
        ?>

            <?php if ($is_new_date): ?>
                <div class="ssm-vtimeline-date <?php echo $is_today ? 'is-today' : ''; ?>">
                    <div class="ssm-vtimeline-date-dot"></div>
                    <div class="ssm-vtimeline-date-label">
                        <?php if ($is_today): ?>
                            <span class="ssm-vtimeline-badge-today">Dzisiaj</span>
                        <?php elseif ($is_tomorrow): ?>
                            <span class="ssm-vtimeline-badge-tomorrow">Jutro</span>
                        <?php endif; ?>
                        <span class="ssm-vtimeline-day-name"><?php echo $days_full_pl[(int)$date->format('N')]; ?></span>
                        <span class="ssm-vtimeline-day-date"><?php echo $date->format('d'); ?> <?php echo $months_pl[(int)$date->format('n')]; ?> <?php echo $date->format('Y'); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="ssm-vtimeline-item <?php echo $is_today ? 'is-today' : ''; ?> <?php echo $is_absent ? 'is-absent' : ''; ?>">
                <div class="ssm-vtimeline-item-dot"></div>
                <div class="ssm-vtimeline-card">
                    <div class="ssm-vtimeline-card-time">
                        <span class="ssm-vtimeline-clock">🕐</span>
                        <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?>
                    </div>
                    <div class="ssm-vtimeline-card-body">
                        <h4 class="ssm-vtimeline-card-title"><?php echo esc_html($session->class_name); ?></h4>
                        <div class="ssm-vtimeline-card-details">
                            <div class="ssm-vtimeline-detail">
                                <span class="ssm-vtimeline-detail-icon">👶</span>
                                <span><?php echo esc_html($session->child_name); ?></span>
                            </div>
                            <div class="ssm-vtimeline-detail">
                                <span class="ssm-vtimeline-detail-icon">📍</span>
                                <span><?php echo esc_html($session->facility_name); ?></span>
                            </div>
                            <div class="ssm-vtimeline-detail">
                                <span class="ssm-vtimeline-detail-icon">🏊</span>
                                <span><?php echo esc_html($session->instructor_name); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="ssm-vtimeline-card-action">
                        <?php if ($is_absent): ?>
                            <span class="ssm-vtimeline-status-absent">Nieobecność zgłoszona</span>
                        <?php elseif ($session->allow_makeups && $can_report && $absences_left > 0): ?>
                            <button class="ssm-btn-absence-report"
                                    data-session-id="<?php echo $session->id; ?>"
                                    data-enrollment-id="<?php echo $session->enrollment_id; ?>"
                                    data-child-name="<?php echo esc_attr($session->child_name); ?>"
                                    data-class-name="<?php echo esc_attr($session->class_name); ?>"
                                    data-date="<?php echo $date->format('d.m.Y'); ?>">
                                Zgłoś nieobecność
                            </button>
                            <span class="ssm-vtimeline-absences-left">Pozostało: <?php echo $absences_left; ?>/<?php echo $session->max_absences; ?></span>
                        <?php elseif ($absences_left == 0): ?>
                            <span class="ssm-vtimeline-status-nolimit">Wykorzystano limit nieobecności</span>
                        <?php elseif (!$can_report): ?>
                            <span class="ssm-vtimeline-status-toolate">Zgłoszenie niedostępne (&lt;24h)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="ssm-empty-state">
        <p>📭 Brak zaplanowanych zajęć</p>
    </div>
<?php endif; ?>

<!-- Modal (ten sam co w dashboard) -->
<div class="ssm-absence-modal" id="absence-modal">
    <div class="ssm-modal-content">
        <div class="ssm-modal-header">
            <h3>Zgłoś nieobecność</h3>
        </div>
        <div class="ssm-modal-body">
            <div class="ssm-modal-info" id="absence-info"></div>
            
            <div class="ssm-form-group">
                <label for="absence-reason">Powód nieobecności (opcjonalnie)</label>
                <textarea id="absence-reason" rows="3" class="ssm-input" 
                          placeholder="np. Choroba, wyjazd..."></textarea>
            </div>
            
            <div class="ssm-notice" style="background:#fff3cd; border-left:4px solid #f39c12; padding:12px; margin-top:15px;">
                <p style="margin:0; font-size:14px;">
                    ⚠️ <strong>Ważne:</strong> Zgłoszona nieobecność będzie mogła być odrobiona na innych zajęciach.
                </p>
            </div>
        </div>
        <div class="ssm-modal-footer">
            <button type="button" class="ssm-btn ssm-btn-secondary" id="cancel-absence">Anuluj</button>
            <button type="button" class="ssm-btn ssm-btn-primary" id="confirm-absence">Zgłoś nieobecność</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    let currentSessionId = 0;
    let currentEnrollmentId = 0;
    
    $('.ssm-btn-absence-report').on('click', function() {
        currentSessionId = $(this).data('session-id');
        currentEnrollmentId = $(this).data('enrollment-id');
        
        $('#absence-info').html(`
            <p><strong>Dziecko:</strong> ${$(this).data('child-name')}</p>
            <p><strong>Kurs:</strong> ${$(this).data('class-name')}</p>
            <p><strong>Data zajęć:</strong> ${$(this).data('date')}</p>
        `);
        
        $('#absence-reason').val('');
        $('#absence-modal').addClass('active');
    });
    
    $('#cancel-absence, #absence-modal').on('click', function(e) {
        if (e.target === this) {
            $('#absence-modal').removeClass('active');
        }
    });
    
    $('#confirm-absence').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Zgłaszam...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ssm_report_absence',
                session_id: currentSessionId,
                enrollment_id: currentEnrollmentId,
                reason: $('#absence-reason').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    location.reload();
                } else {
                    alert('❌ ' + response.data);
                    btn.prop('disabled', false).text('Zgłoś nieobecność');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);
                alert('❌ Błąd połączenia. Status: ' + status);
                btn.prop('disabled', false).text('Zgłoś nieobecność');
            }
        });
    });
});
</script>

