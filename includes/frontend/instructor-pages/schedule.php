<?php
/**
 * Panel Instruktora - Moje zajęcia
 */
if (!defined('ABSPATH')) exit;

// Filtry
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d', strtotime('+14 days'));
$show_past = isset($_GET['show_past']) ? 1 : 0;

if ($show_past) {
    $date_from = date('Y-m-d', strtotime('-30 days'));
}

// Pobierz wszystkie zajęcia instruktora
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name,
            f.name as facility_name,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
             WHERE class_id = c.id AND status = 'active') as enrolled_count,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance 
             WHERE session_id = s.id) as attendance_filled,
            u.id as unavailability_id,
            u.status as unavailability_status,
            CONCAT(repl.first_name, ' ', repl.last_name) as replacement_name
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     LEFT JOIN {$wpdb->prefix}ssm_instructor_unavailability u ON u.session_id = s.id AND u.instructor_id = %d
     LEFT JOIN {$wpdb->prefix}ssm_instructors repl ON u.replacement_instructor_id = repl.id
     WHERE (c.instructor_id = %d OR s.instructor_id = %d)
     AND s.session_date BETWEEN %s AND %s
     AND s.status = 'scheduled'
     ORDER BY s.session_date ASC, s.time_start ASC",
    $instructor->id, $instructor->id, $instructor->id, $date_from, $date_to
));

// Grupuj zajęcia po dniach
$sessions_by_date = array();
foreach ($sessions as $session) {
    $date = $session->session_date;
    if (!isset($sessions_by_date[$date])) {
        $sessions_by_date[$date] = array();
    }
    $sessions_by_date[$date][] = $session;
}

// Statystyki
$total_sessions = count($sessions);
$completed_attendance = count(array_filter($sessions, function($s) { return $s->attendance_filled; }));
$unavailable_count = count(array_filter($sessions, function($s) { return $s->unavailability_id; }));

?>

<div class="ssm-page-header">
    <h1>📅 Moje zajęcia</h1>
    <p>Pełny harmonogram zajęć, frekwencja i zarządzanie niedyspozycjami</p>
</div>

<!-- Statystyki -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #2271b1;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Wszystkie zajęcia</div>
        <div style="font-size: 32px; font-weight: 700; color: #2271b1;"><?php echo $total_sessions; ?></div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Frekwencja sprawdzona</div>
        <div style="font-size: 32px; font-weight: 700; color: #10b981;"><?php echo $completed_attendance; ?></div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #ef4444;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Do sprawdzenia</div>
        <div style="font-size: 32px; font-weight: 700; color: #ef4444;"><?php echo $total_sessions - $completed_attendance; ?></div>
    </div>
    
    <?php if ($unavailable_count > 0): ?>
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Niedyspozycje</div>
        <div style="font-size: 32px; font-weight: 700; color: #f59e0b;"><?php echo $unavailable_count; ?></div>
    </div>
    <?php endif; ?>
</div>

<!-- Filtry -->
<div class="ssm-filters" style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <form method="get" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
        <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
        <input type="hidden" name="instructor_page" value="schedule">
        
        <div style="flex: 1; min-width: 200px;">
            <label for="date-from" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1e293b;">
                Od daty:
            </label>
            <input type="date" name="date_from" id="date-from" 
                   value="<?php echo esc_attr($date_from); ?>" 
                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px;">
        </div>
        
        <div style="flex: 1; min-width: 200px;">
            <label for="date-to" style="display: block; font-weight: 600; margin-bottom: 5px; color: #1e293b;">
                Do daty:
            </label>
            <input type="date" name="date_to" id="date-to" 
                   value="<?php echo esc_attr($date_to); ?>" 
                   style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px;">
        </div>
        
        <div>
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="show_past" value="1" <?php checked($show_past, 1); ?>>
                <span style="font-weight: 600; color: #1e293b;">Pokaż przeszłe</span>
            </label>
        </div>
        
        <div>
            <button type="submit" style="padding: 10px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                🔍 Filtruj
            </button>
        </div>
        
        <div>
            <a href="?instructor_page=schedule" style="padding: 10px 24px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
                ↺ Reset
            </a>
        </div>
    </form>
</div>

<!-- Lista zajęć pogrupowana po dniach -->
<?php if (!empty($sessions_by_date)): ?>
    
    <?php foreach ($sessions_by_date as $date => $day_sessions): 
        $date_obj = new DateTime($date);
        $is_today = ($date == date('Y-m-d'));
        $is_past = ($date < date('Y-m-d'));
    ?>
    
    <div style="margin-bottom: 30px;">
        <h2 style="margin: 0 0 15px 0; padding: 15px 20px; background: <?php echo $is_today ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 'white'; ?>; color: <?php echo $is_today ? 'white' : '#1e293b'; ?>; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px;">
            📅 <?php echo $date_obj->format('d.m.Y'); ?> 
            <span style="font-weight: 400; font-size: 16px;">(<?php echo ssm_get_day_name_pl($date_obj); ?>)</span>
            <?php if ($is_today): ?>
                <span style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">DZISIAJ</span>
            <?php endif; ?>
            <span style="margin-left: auto; font-size: 14px; font-weight: 400;">
                <?php echo count($day_sessions); ?> <?php echo count($day_sessions) == 1 ? 'zajęcia' : 'zajęć'; ?>
            </span>
        </h2>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($day_sessions as $session): 
                $now = new DateTime();
                $session_start = new DateTime($session->session_date . ' ' . $session->time_start);
                $session_end = new DateTime($session->session_date . ' ' . $session->time_end);
                
                $is_ongoing = ($now >= $session_start && $now <= $session_end);
                $is_upcoming = ($now < $session_start);
                $is_past = ($now > $session_end);
                $has_unavailability = ($session->unavailability_id !== null);
            ?>
            
            <div class="ssm-session-card" style="padding: 20px; border: 2px solid <?php echo $is_ongoing ? '#f59e0b' : ($has_unavailability ? '#dc2626' : '#e2e8f0'); ?>; border-radius: 12px; background: <?php echo $is_past ? '#f8fafc' : 'white'; ?>; <?php echo $has_unavailability ? 'opacity: 0.7;' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 8px 0; color: #1e293b; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <?php echo esc_html($session->class_name); ?>
                            <?php if ($is_ongoing): ?>
                                <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">W TRAKCIE</span>
                            <?php elseif ($is_past && $session->attendance_filled): ?>
                                <span style="background: #10b981; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">ZAKOŃCZONE ✓</span>
                            <?php elseif ($is_past): ?>
                                <span style="background: #64748b; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">ZAKOŃCZONE</span>
                            <?php endif; ?>
                            
                            <?php if ($has_unavailability): ?>
                                <?php if ($session->unavailability_status == 'covered'): ?>
                                    <span style="background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        ZASTĄPIONE → <?php echo esc_html($session->replacement_name); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        🚫 NIEDYSPOZYCJA
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </h3>
                        <div style="display: flex; gap: 20px; color: #64748b; flex-wrap: wrap;">
                            <span>⏰ <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?></span>
                            <span>📍 <?php echo esc_html($session->facility_name); ?></span>
                            <span>👥 <?php echo $session->enrolled_count; ?> zapisanych</span>
                            <?php if ($session->attendance_filled): ?>
                                <span style="color: #10b981; font-weight: 600;">✓ Frekwencja sprawdzona</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: 600;">⚠ Frekwencja nie sprawdzona</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <?php if (!$has_unavailability && $is_upcoming): ?>
                            <button type="button" class="ssm-report-unavailability-btn"
                                    data-session-id="<?php echo $session->id; ?>"
                                    data-class="<?php echo esc_attr($session->class_name); ?>"
                                    data-date="<?php echo date('d.m.Y', strtotime($session->session_date)); ?>"
                                    data-time="<?php echo substr($session->time_start, 0, 5); ?>"
                                    style="padding: 10px 20px; border-radius: 8px; border: 2px solid #dc2626; background: white; color: #dc2626; font-weight: 600; white-space: nowrap; cursor: pointer;">
                                🚫 Zgłoś niedyspozycję
                            </button>
                        <?php endif; ?>
                        
                        <?php if (!$has_unavailability): ?>
                            <?php if ($session->attendance_filled): ?>
                                <a href="?instructor_page=attendance&session_id=<?php echo $session->id; ?>" 
                                   style="padding: 10px 20px; border-radius: 8px; text-decoration: none; background: #10b981; color: white; font-weight: 600; white-space: nowrap;">
                                    👁 Zobacz listę
                                </a>
                            <?php else: ?>
                                <a href="?instructor_page=attendance&session_id=<?php echo $session->id; ?>" 
                                   style="padding: 10px 20px; border-radius: 8px; text-decoration: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; white-space: nowrap;">
                                    📝 Sprawdź obecność
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php endforeach; ?>
    
<?php else: ?>
    <div style="background: #f0fdf4; padding: 40px; border-radius: 12px; text-align: center; color: #064e3b;">
        <p style="margin: 0; font-size: 16px;">
            📅 Brak zajęć w wybranym okresie.
        </p>
    </div>
<?php endif; ?>

<!-- Szybki dostęp -->
<div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 30px;">
    <h3 style="margin: 0 0 15px 0; color: #1e293b;">Szybki dostęp:</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php
        $quick_periods = array(
            array('label' => 'Dzisiaj', 'from' => date('Y-m-d'), 'to' => date('Y-m-d')),
            array('label' => 'Ten tydzień', 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+7 days'))),
            array('label' => '2 tygodnie', 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+14 days'))),
            array('label' => 'Miesiąc', 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+30 days'))),
        );
        
        foreach ($quick_periods as $period):
            $is_selected = ($date_from == $period['from'] && $date_to == $period['to']);
        ?>
            <a href="?instructor_page=schedule&date_from=<?php echo $period['from']; ?>&date_to=<?php echo $period['to']; ?>" 
               style="padding: 8px 16px; border-radius: 8px; text-decoration: none; <?php echo $is_selected ? 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;' : 'background: #f1f5f9; color: #475569;'; ?> font-weight: 600;">
                <?php echo $period['label']; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    $('.ssm-report-unavailability-btn').on('click', function() {
        var btn = $(this);
        var sessionId = btn.data('session-id');
        var className = btn.data('class');
        var date = btn.data('date');
        var time = btn.data('time');
        
        var reason = prompt(
            'Zgłoś niedyspozycję\n\n' +
            'Zajęcia: ' + className + '\n' +
            'Data: ' + date + ' o ' + time + '\n\n' +
            'Podaj powód (opcjonalnie):',
            ''
        );
        
        if (reason === null) {
            return; // Anulowano
        }
        
        btn.prop('disabled', true).text('Zgłaszam...');
        
        $.post(ajaxurl, {
            action: 'ssm_report_unavailability',
            session_id: sessionId,
            reason: reason
        })
        .done(function(response) {
            if (response.success) {
                alert('✅ Niedyspozycja zgłoszona!\n\nInni instruktorzy zostaną powiadomieni emailem o dostępnym zastępstwie.\n\nMożesz anulować zgłoszenie w zakładce "Zastępstwa".');
                location.reload();
            } else {
                alert('❌ ' + response.data);
                btn.prop('disabled', false).text('🚫 Zgłoś niedyspozycję');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            alert('❌ Błąd połączenia: ' + error + '\n\nSprawdź konsolę (F12) dla szczegółów.');
            btn.prop('disabled', false).text('🚫 Zgłoś niedyspozycję');
        });
    });
});
</script>
