<?php
/**
 * Panel Rodzica - Odrabianie zajęć
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Sprawdź czy zalogowany
if (!is_user_logged_in()) {
    echo '<p>Musisz być zalogowany.</p>';
    return;
}

$current_user = wp_get_current_user();

// Pobierz dane klienta
$client = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
    $current_user->user_email
));

if (!$client) {
    echo '<p>Nie znaleziono danych klienta.</p>';
    return;
}

// Obsługa zapisu na zajęcia odrabiające
if (isset($_POST['book_makeup']) && isset($_POST['absence_id']) && isset($_POST['makeup_session_id'])) {
    $absence_id = intval($_POST['absence_id']);
    $makeup_session_id = intval($_POST['makeup_session_id']);
    
    // Sprawdź czy nieobecność należy do klienta
    $absence = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, e.client_id 
         FROM {$wpdb->prefix}ssm_absences a
         JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
         WHERE a.id = %d AND e.client_id = %d",
        $absence_id, $client->id
    ));
    
    if ($absence) {
        // Sprawdź czy są wolne miejsca
        $makeup_session = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.max_participants,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
                     WHERE class_id = c.id AND status = 'active') as enrolled_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
                     WHERE session_id = s.id AND makeup_session_id IS NULL AND status = 'reported') as absences_count
             FROM {$wpdb->prefix}ssm_sessions s
             JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
             WHERE s.id = %d",
            $makeup_session_id
        ));
        
        if ($makeup_session) {
            $available_spots = $makeup_session->max_participants - $makeup_session->enrolled_count + $makeup_session->absences_count;
            
            if ($available_spots > 0) {
                // Zapisz
                $wpdb->update(
                    $wpdb->prefix . 'ssm_absences',
                    array(
                        'makeup_session_id' => $makeup_session_id,
                        'status' => 'makeup_scheduled'
                    ),
                    array('id' => $absence_id)
                );
                
                echo '<div class="ssm-notice ssm-notice-success">
                    ✅ Zajęcia odrabiające zostały zarezerwowane!
                </div>';
            } else {
                echo '<div class="ssm-notice ssm-notice-error">
                    ❌ Brak wolnych miejsc na te zajęcia.
                </div>';
            }
        }
    }
}

// Pobierz zgłoszone nieobecności bez przypisanych zajęć odrabiających
$pending_absences = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, 
            s.session_date, s.time_start, s.time_end,
            c.name as class_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            c.id as class_id
     FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON a.child_id = ch.id
     WHERE e.client_id = %d 
     AND a.can_makeup = 1
     AND a.makeup_session_id IS NULL
     AND a.status = 'reported'
     ORDER BY s.session_date DESC",
    $client->id
));

// Pobierz zaplanowane odrobienia
$scheduled_makeups = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, 
            s.session_date as original_date, s.time_start as original_time,
            c.name as class_name,
            ms.session_date as makeup_date, ms.time_start as makeup_time, ms.time_end as makeup_time_end,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            f.name as facility_name
     FROM {$wpdb->prefix}ssm_absences a
     JOIN {$wpdb->prefix}ssm_enrollments e ON a.enrollment_id = e.id
     JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON a.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_sessions ms ON a.makeup_session_id = ms.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE e.client_id = %d 
     AND a.makeup_session_id IS NOT NULL
     ORDER BY ms.session_date ASC",
    $client->id
));

?>

<div class="ssm-section">
    <h2>🔄 Odrabianie zajęć</h2>
    
    <?php if (!empty($pending_absences)): ?>
        <div class="ssm-notice ssm-notice-warning" style="margin-bottom: 20px;">
            ⚠️ <strong>Masz <?php echo count($pending_absences); ?> nieobecność<?php echo count($pending_absences) > 1 ? 'i' : 'ć'; ?> do odrobienia!</strong>
            Wybierz termin zajęć zastępczych poniżej.
        </div>
    <?php endif; ?>
    
    <p style="color: var(--text-muted); margin-bottom: 30px;">
        Tutaj możesz zapisać się na zajęcia zastępcze dla zgłoszonych nieobecności. 
        Zajęcia odrabiające odbywają się w tej samej grupie, w innym terminie.
    </p>
    
    <!-- Nieobecności do odrobienia -->
    <?php if (!empty($pending_absences)): ?>
        <h3 style="margin-top: 30px; color: var(--text-dark);">Nieobecności wymagające odrobienia</h3>
        
        <?php foreach ($pending_absences as $absence): 
            $absence_date = new DateTime($absence->session_date);
            
            // Pobierz dostępne zajęcia do odrobienia (ta sama klasa, przyszłe zajęcia)
            $available_sessions = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, 
                        c.max_participants,
                        f.name as facility_name,
                        (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
                         WHERE class_id = c.id AND status = 'active') as enrolled_count,
                        (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences 
                         WHERE session_id = s.id AND makeup_session_id IS NULL AND status = 'reported') as absences_count
                 FROM {$wpdb->prefix}ssm_sessions s
                 JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
                 JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
                 WHERE s.class_id = %d 
                 AND s.session_date > CURDATE()
                 AND s.status = 'scheduled'
                 AND s.id != %d
                 ORDER BY s.session_date ASC
                 LIMIT 10",
                $absence->class_id, $absence->session_id
            ));
            
            // Filtruj tylko te z wolnymi miejscami
            $available_sessions = array_filter($available_sessions, function($session) {
                $available_spots = $session->max_participants - $session->enrolled_count + $session->absences_count;
                return $available_spots > 0;
            });
        ?>
        
        <div class="ssm-makeup-card" style="background: #fff3cd; padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                <div>
                    <h4 style="margin: 0 0 8px 0; color: var(--text-dark);">
                        <?php echo esc_html($absence->child_name); ?> - <?php echo esc_html($absence->class_name); ?>
                    </h4>
                    <p style="margin: 0; color: #78350f;">
                        <strong>Nieobecność:</strong> <?php echo $absence_date->format('d.m.Y'); ?> • 
                        <?php echo substr($absence->time_start, 0, 5); ?> - <?php echo substr($absence->time_end, 0, 5); ?>
                    </p>
                    <?php if ($absence->reason): ?>
                        <p style="margin: 8px 0 0 0; color: #78350f; font-size: 14px;">
                            <em>Powód: <?php echo esc_html($absence->reason); ?></em>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($available_sessions)): ?>
                <div style="background: white; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <h5 style="margin: 0 0 12px 0; color: var(--text-dark);">
                        Dostępne terminy zastępcze (<?php echo count($available_sessions); ?>):
                    </h5>
                    
                    <div style="display: grid; gap: 12px;">
                        <?php foreach ($available_sessions as $session): 
                            $session_date = new DateTime($session->session_date);
                            $available_spots = $session->max_participants - $session->enrolled_count + $session->absences_count;
                            $is_today = ($session->session_date == date('Y-m-d'));
                        ?>
                        
                        <form method="post" style="margin: 0;">
                            <input type="hidden" name="book_makeup" value="1">
                            <input type="hidden" name="absence_id" value="<?php echo $absence->id; ?>">
                            <input type="hidden" name="makeup_session_id" value="<?php echo $session->id; ?>">
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 4px;">
                                        <?php echo $session_date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($session_date); ?>)
                                        <?php if ($is_today): ?>
                                            <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 8px;">DZISIAJ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 14px;">
                                        <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?> • 
                                        <?php echo esc_html($session->facility_name); ?> • 
                                        <span style="color: <?php echo $available_spots <= 2 ? '#ef4444' : '#10b981'; ?>; font-weight: 600;">
                                            <?php echo $available_spots; ?> wolne miejsce/a
                                        </span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="ssm-btn ssm-btn-primary" style="white-space: nowrap;">
                                    Zapisz się
                                </button>
                            </div>
                        </form>
                        
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin-top: 15px; color: #7f1d1d;">
                    <p style="margin: 0;">
                        ❌ Brak dostępnych terminów zastępczych. Skontaktuj się z nami aby ustalić inny termin.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php endforeach; ?>
        
    <?php else: ?>
        <div style="background: #f0fdf4; padding: 20px; border-radius: 12px; text-align: center; color: #064e3b;">
            <p style="margin: 0; font-size: 16px;">
                ✅ Brak nieobecności wymagających odrobienia.
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Zaplanowane odrobienia -->
    <?php if (!empty($scheduled_makeups)): ?>
        <h3 style="margin-top: 40px; color: var(--text-dark);">Zaplanowane zajęcia odrabiające</h3>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($scheduled_makeups as $makeup): 
                $original_date = new DateTime($makeup->original_date);
                $makeup_date = new DateTime($makeup->makeup_date);
                $is_future = ($makeup->makeup_date >= date('Y-m-d'));
            ?>
            
            <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #10b981;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 8px 0; color: var(--text-dark);">
                            <?php echo esc_html($makeup->child_name); ?> - <?php echo esc_html($makeup->class_name); ?>
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 12px;">
                            <div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                                    Nieobecność:
                                </div>
                                <div style="color: #dc2626; font-weight: 600;">
                                    ❌ <?php echo $original_date->format('d.m.Y'); ?> • 
                                    <?php echo substr($makeup->original_time, 0, 5); ?>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                                    Zajęcia zastępcze:
                                </div>
                                <div style="color: #059669; font-weight: 600;">
                                    ✅ <?php echo $makeup_date->format('d.m.Y'); ?> • 
                                    <?php echo substr($makeup->makeup_time, 0, 5); ?> - <?php echo substr($makeup->makeup_time_end, 0, 5); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 12px; color: var(--text-muted); font-size: 14px;">
                            📍 <?php echo esc_html($makeup->facility_name); ?>
                        </div>
                    </div>
                    
                    <?php if ($is_future): ?>
                        <span style="background: #10b981; color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                            ZAPLANOWANE
                        </span>
                    <?php else: ?>
                        <span style="background: #64748b; color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                            ZAKOŃCZONE
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.ssm-makeup-card {
    transition: all 0.3s ease;
}

.ssm-makeup-card:hover {
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}
</style>
