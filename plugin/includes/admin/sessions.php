<?php
// Panel Harmonogram - wyświetlany gdy klikniemy "Harmonogram" przy kursie
// Dostęp: ?page=ssm-sessions&class_id=X

if (!defined('ABSPATH')) exit;

// Sprawdź czy mamy class_id
if (!isset($_GET['class_id'])) {
    echo '<div class="wrap"><h1>Harmonogram</h1><p>Brak ID kursu. <a href="?page=ssm-classes">Wróć do kursów</a></p></div>';
    return;
}

global $wpdb;
$class_id = intval($_GET['class_id']);

// Pobierz dane kursu
$class = $wpdb->get_row($wpdb->prepare(
    "SELECT c.*, f.name as facility_name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name
     FROM {$wpdb->prefix}ssm_classes c
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
     WHERE c.id = %d",
    $class_id
));

if (!$class) {
    echo '<div class="wrap"><h1>Harmonogram</h1><p>Nie znaleziono kursu. <a href="?page=ssm-classes">Wróć do kursów</a></p></div>';
    return;
}

// Pobierz sesje
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, CONCAT(i.first_name, ' ', i.last_name) as instructor_name
     FROM {$wpdb->prefix}ssm_sessions s
     LEFT JOIN {$wpdb->prefix}ssm_instructors i ON s.instructor_id = i.id
     WHERE s.class_id = %d
     ORDER BY s.session_number",
    $class_id
));

// Pobierz instruktorów dla selecta
$instructors = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}ssm_instructors WHERE active = 1 ORDER BY last_name");

$days_pl = array(1 => 'Pon', 2 => 'Wt', 3 => 'Śr', 4 => 'Czw', 5 => 'Pt', 6 => 'Sob', 7 => 'Niedz');
?>

<div class="wrap">
    <h1>📅 Harmonogram: <?php echo esc_html($class->name); ?></h1>
    
    <div class="ssm-class-info" style="background:#fff; border:1px solid #ccd0d4; padding:15px; margin:20px 0;">
        <p style="margin:0;">
            <strong>Obiekt:</strong> <?php echo esc_html($class->facility_name); ?> |
            <strong>Instruktor:</strong> <?php echo esc_html($class->instructor_name); ?> |
            <strong>Termin:</strong> <?php echo $days_pl[$class->day_of_week]; ?> <?php echo substr($class->time_start, 0, 5); ?>-<?php echo substr($class->time_end, 0, 5); ?> |
            <strong>Start:</strong> <?php echo date('d.m.Y', strtotime($class->start_date)); ?> |
            <strong>Zajęć:</strong> <?php echo $class->session_count; ?> |
            <strong>Cena:</strong> <?php echo number_format($class->total_price, 2); ?> PLN
        </p>
        <p style="margin:10px 0 0 0;">
            <a href="?page=ssm-classes" class="button">← Wróć do kursów</a>
            <button class="button" id="ssm-regenerate-schedule" data-class-id="<?php echo $class_id; ?>">🔄 Przelicz harmonogram</button>
        </p>
    </div>
    
    <?php if ($sessions): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Data</th>
                <th>Dzień</th>
                <th>Godzina</th>
                <th>Instruktor</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $session): 
                $date = new DateTime($session->session_date);
                $dow = $date->format('N');
                $is_past = (strtotime($session->session_date) < strtotime('today'));
                $row_style = '';
                
                if ($session->status == 'cancelled') {
                    $row_style = 'background:#f8d7da;';
                } elseif ($is_past && $session->status == 'scheduled') {
                    $row_style = 'background:#e7f3ff;';
                }
            ?>
            <tr style="<?php echo $row_style; ?>">
                <td><strong><?php echo $session->session_number; ?></strong></td>
                <td><?php echo date('d.m.Y', strtotime($session->session_date)); ?></td>
                <td><?php echo $days_pl[$dow]; ?></td>
                <td><?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?></td>
                <td>
                    <?php echo esc_html($session->instructor_name); ?>
                    <button class="button button-small ssm-change-instructor-btn" 
                            data-session-id="<?php echo $session->id; ?>"
                            data-current-instructor="<?php echo $session->instructor_id; ?>"
                            style="margin-left:5px;" title="Zmień instruktora">
                        ✏️
                    </button>
                </td>
                <td>
                    <?php if ($session->status == 'scheduled'): ?>
                        <span class="ssm-badge ssm-badge-scheduled">✓ Zaplanowane</span>
                    <?php elseif ($session->status == 'cancelled'): ?>
                        <span class="ssm-badge ssm-badge-cancelled">❌ Pominięte</span>
                    <?php elseif ($session->status == 'completed'): ?>
                        <span class="ssm-badge ssm-badge-completed">✅ Zakończone</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($session->status == 'scheduled'): ?>
                        <button class="button button-small ssm-skip-session" 
                                data-session-id="<?php echo $session->id; ?>"
                                data-class-id="<?php echo $class_id; ?>">
                            ⏭️ Pomiń
                        </button>
                    <?php elseif ($session->status == 'cancelled'): ?>
                        <button class="button button-small ssm-restore-session" 
                                data-session-id="<?php echo $session->id; ?>"
                                data-class-id="<?php echo $class_id; ?>">
                            ↩️ Przywróć
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="notice notice-warning">
        <p>Brak sesji w harmonogramie. <button class="button" id="ssm-regenerate-schedule" data-class-id="<?php echo $class_id; ?>">Wygeneruj harmonogram</button></p>
    </div>
    <?php endif; ?>
    
    <div class="ssm-info-box" style="margin-top:20px;">
        <h3>💡 Jak działają przyciski?</h3>
        <ul>
            <li><strong>⏭️ Pomiń</strong> - Anuluje te zajęcia i przesuwa WSZYSTKIE kolejne zajęcia o 7 dni</li>
            <li><strong>↩️ Przywróć</strong> - Przywraca zajęcia i cofa daty wszystkich kolejnych zajęć o 7 dni</li>
            <li><strong>✏️ (przy instruktorze)</strong> - Zmienia instruktora tylko dla tych konkretnych zajęć</li>
            <li><strong>🔄 Przelicz harmonogram</strong> - Regeneruje cały harmonogram od nowa (usuwa poprzedni!)</li>
        </ul>
        
        <p><strong>Przykład:</strong> Pomijasz zajęcia #3 (24.02) → zajęcia #4 przesuwają się z 03.03 na 10.03, #5 z 10.03 na 17.03 itd.</p>
    </div>
</div>

<!-- Modal zmiany instruktora -->
<div id="ssm-instructor-modal" style="display:none;">
    <div class="ssm-modal-overlay"></div>
    <div class="ssm-modal-content" style="max-width:400px;">
        <div class="ssm-modal-header">
            <h2>Zmień instruktora</h2>
            <button class="ssm-modal-close">&times;</button>
        </div>
        <div class="ssm-modal-body">
            <input type="hidden" id="modal-session-id">
            <p>Wybierz nowego instruktora dla tych zajęć:</p>
            <select id="modal-instructor-select" class="regular-text">
                <?php foreach ($instructors as $instructor): ?>
                <option value="<?php echo $instructor->id; ?>">
                    <?php echo esc_html($instructor->first_name . ' ' . $instructor->last_name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <p style="margin-top:15px;">
                <button class="button button-primary" id="ssm-save-instructor-change">Zmień instruktora</button>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Pomiń zajęcia
    $('.ssm-skip-session').on('click', function() {
        if (!confirm('Pominąć te zajęcia?\n\nWszystkie kolejne zajęcia zostaną przesunięte o 7 dni.')) return;
        
        var sessionId = $(this).data('session-id');
        var classId = $(this).data('class-id');
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_skip_session',
            nonce: ssmAdmin.nonce,
            session_id: sessionId,
            class_id: classId
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Przywróć zajęcia
    $('.ssm-restore-session').on('click', function() {
        if (!confirm('Przywrócić te zajęcia?\n\nWszystkie kolejne zajęcia zostaną cofnięte o 7 dni.')) return;
        
        var sessionId = $(this).data('session-id');
        var classId = $(this).data('class-id');
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_restore_session',
            nonce: ssmAdmin.nonce,
            session_id: sessionId,
            class_id: classId
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Regeneruj harmonogram
    $('#ssm-regenerate-schedule').on('click', function() {
        if (!confirm('Regenerować harmonogram?\n\nUWAGA: Obecny harmonogram zostanie usunięty i utworzony od nowa!')) return;
        
        var classId = $(this).data('class-id');
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_regenerate_schedule',
            nonce: ssmAdmin.nonce,
            class_id: classId
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Modal zmiany instruktora
    $('.ssm-change-instructor-btn').on('click', function() {
        var sessionId = $(this).data('session-id');
        var currentInstructor = $(this).data('current-instructor');
        
        $('#modal-session-id').val(sessionId);
        $('#modal-instructor-select').val(currentInstructor);
        $('#ssm-instructor-modal').show();
    });
    
    $('.ssm-modal-close').on('click', function() {
        $('#ssm-instructor-modal').hide();
    });
    
    $('#ssm-save-instructor-change').on('click', function() {
        var sessionId = $('#modal-session-id').val();
        var instructorId = $('#modal-instructor-select').val();
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_change_session_instructor',
            nonce: ssmAdmin.nonce,
            session_id: sessionId,
            instructor_id: instructorId
        }, function(response) {
            if (response.success) {
                alert('✅ Instruktor zmieniony');
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
});
</script>
