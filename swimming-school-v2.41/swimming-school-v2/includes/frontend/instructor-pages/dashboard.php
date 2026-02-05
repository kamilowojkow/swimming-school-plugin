<?php
/**
 * Panel Instruktora - Dashboard
 */
if (!defined('ABSPATH')) exit;

// Statystyki
$today = date('Y-m-d');
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

// Zajęcia dzisiaj
$today_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end, f.name as facility_name,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
             WHERE class_id = c.id AND status = 'active') as enrolled_count
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE c.instructor_id = %d 
     AND s.session_date = %s
     AND s.status = 'scheduled'
     ORDER BY s.time_start",
    $instructor->id, $today
));

// Statystyki miesiąca
$month_stats = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN s.id END) as completed_sessions,
        SUM(CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END) as total_present
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_attendance a ON s.id = a.session_id AND a.status = 'present'
     WHERE c.instructor_id = %d 
     AND s.session_date BETWEEN %s AND %s
     AND s.status = 'scheduled'",
    $instructor->id, $this_month_start, $this_month_end
));

// Wylicz wynagrodzenie
$salary = $month_stats->completed_sessions * ($instructor->hourly_rate ?? 0);

?>

<div class="ssm-page-header">
    <h1>Panel główny</h1>
    <p>Witaj, <?php echo esc_html($instructor->first_name); ?>! Oto podsumowanie Twojej pracy.</p>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid">
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon">📅</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value"><?php echo count($today_sessions); ?></div>
            <div class="ssm-stat-label">Zajęcia dzisiaj</div>
        </div>
    </div>
    
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon">✅</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value"><?php echo $month_stats->completed_sessions; ?></div>
            <div class="ssm-stat-label">Przeprowadzonych w tym miesiącu</div>
        </div>
    </div>
    
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon">👥</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value"><?php echo $month_stats->total_present; ?></div>
            <div class="ssm-stat-label">Dzieci uczestniczyło</div>
        </div>
    </div>
    
    <div class="ssm-stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
        <div class="ssm-stat-icon" style="color: white;">💰</div>
        <div class="ssm-stat-content">
            <div class="ssm-stat-value" style="color: white;"><?php echo number_format($salary, 2, ',', ' '); ?> PLN</div>
            <div class="ssm-stat-label" style="color: rgba(255,255,255,0.9);">Wynagrodzenie (<?php echo date('m/Y'); ?>)</div>
        </div>
    </div>
</div>

<!-- Dzisiejsze zajęcia -->
<div class="ssm-section">
    <h2>📅 Dzisiejsze zajęcia</h2>
    
    <?php if (!empty($today_sessions)): ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($today_sessions as $session): 
                $now = new DateTime();
                $session_start = new DateTime($session->session_date . ' ' . $session->time_start);
                $session_end = new DateTime($session->session_date . ' ' . $session->time_end);
                
                $is_ongoing = ($now >= $session_start && $now <= $session_end);
                $is_upcoming = ($now < $session_start);
                $is_past = ($now > $session_end);
                
                // Sprawdź czy wypełniona frekwencja
                $attendance_filled = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance WHERE session_id = %d",
                    $session->id
                ));
            ?>
            
            <div class="ssm-session-card" style="background: white; padding: 20px; border-radius: 12px; border-left: 4px solid <?php echo $is_ongoing ? '#f59e0b' : ($is_past ? '#64748b' : '#3b82f6'); ?>; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 8px 0; color: #1e293b;">
                            <?php echo esc_html($session->class_name); ?>
                            <?php if ($is_ongoing): ?>
                                <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; margin-left: 10px;">W TRAKCIE</span>
                            <?php elseif ($is_past): ?>
                                <span style="background: #64748b; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; margin-left: 10px;">ZAKOŃCZONE</span>
                            <?php endif; ?>
                        </h3>
                        <p style="margin: 0; color: #64748b;">
                            ⏰ <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?> • 
                            📍 <?php echo esc_html($session->facility_name); ?> • 
                            👥 <?php echo $session->enrolled_count; ?> zapisanych
                        </p>
                    </div>
                    
                    <div>
                        <?php if ($attendance_filled): ?>
                            <a href="?instructor_page=attendance&session_id=<?php echo $session->id; ?>" 
                               class="ssm-btn" style="background: #10b981; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; display: inline-block;">
                                ✅ Zobacz frekwencję
                            </a>
                        <?php else: ?>
                            <a href="?instructor_page=attendance&session_id=<?php echo $session->id; ?>" 
                               class="ssm-btn ssm-btn-primary" style="text-decoration: none; padding: 10px 20px; border-radius: 8px; display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                📝 Sprawdź obecność
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: #f0fdf4; padding: 40px; border-radius: 12px; text-align: center; color: #064e3b;">
            <p style="margin: 0; font-size: 16px;">
                ✅ Brak zajęć dzisiaj. Miłego dnia!
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Nadchodzące zajęcia -->
<?php
$upcoming_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end, f.name as facility_name
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     WHERE c.instructor_id = %d 
     AND s.session_date > %s
     AND s.session_date <= DATE_ADD(%s, INTERVAL 7 DAY)
     AND s.status = 'scheduled'
     ORDER BY s.session_date, s.time_start
     LIMIT 5",
    $instructor->id, $today, $today
));
?>

<?php if (!empty($upcoming_sessions)): ?>
<div class="ssm-section">
    <h2>📆 Nadchodzące zajęcia (7 dni)</h2>
    
    <table class="wp-list-table widefat" style="background: white; border-radius: 8px; overflow: hidden;">
        <thead>
            <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <th style="padding: 12px;">Data</th>
                <th>Godzina</th>
                <th>Kurs</th>
                <th>Miejsce</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($upcoming_sessions as $session): 
                $date = new DateTime($session->session_date);
            ?>
            <tr>
                <td style="padding: 12px;">
                    <strong><?php echo $date->format('d.m.Y'); ?></strong><br>
                    <small style="color: #64748b;"><?php echo ssm_get_day_name_pl($date); ?></small>
                </td>
                <td><?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?></td>
                <td><strong><?php echo esc_html($session->class_name); ?></strong></td>
                <td><?php echo esc_html($session->facility_name); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
