<?php
// Panel Dashboard
if (!defined('ABSPATH')) exit;

global $wpdb;

// Statystyki
$stats = array(
    'clients' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_clients"),
    'children' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_children WHERE active = 1"),
    'facilities' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_facilities"),
    'instructors' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_instructors WHERE active = 1"),
    'classes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_classes WHERE status = 'active'"),
    'enrollments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments WHERE status = 'active'"),
    'sessions_today' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions WHERE session_date = %s AND status = 'scheduled'",
        date('Y-m-d')
    ))
);

// Nadchodzące zajęcia (dziś i najbliższe 7 dni)
$upcoming_sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, f.name as facility_name, 
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name
     FROM {$wpdb->prefix}ssm_sessions s
     LEFT JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     LEFT JOIN {$wpdb->prefix}ssm_instructors i ON s.instructor_id = i.id
     WHERE s.session_date BETWEEN %s AND %s
     AND s.status = 'scheduled'
     ORDER BY s.session_date, s.time_start
     LIMIT 10",
    date('Y-m-d'),
    date('Y-m-d', strtotime('+7 days'))
));

$days_pl = array(1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela');
?>

<div class="wrap">
    <h1>Swimming School Manager v2.0 - Dashboard</h1>
    
    <div class="ssm-dashboard">
        
        <!-- Statystyki -->
        <div class="ssm-stats-grid">
            <div class="ssm-stat-box">
                <div class="ssm-stat-icon">👥</div>
                <div class="ssm-stat-number"><?php echo $stats['clients']; ?></div>
                <div class="ssm-stat-label">Rodzice</div>
            </div>
            
            <div class="ssm-stat-box">
                <div class="ssm-stat-icon">👶</div>
                <div class="ssm-stat-number"><?php echo $stats['children']; ?></div>
                <div class="ssm-stat-label">Dzieci</div>
            </div>
            
            <div class="ssm-stat-box">
                <div class="ssm-stat-icon">🏊</div>
                <div class="ssm-stat-number"><?php echo $stats['instructors']; ?></div>
                <div class="ssm-stat-label">Instruktorzy</div>
            </div>
            
            <div class="ssm-stat-box">
                <div class="ssm-stat-icon">📚</div>
                <div class="ssm-stat-number"><?php echo $stats['classes']; ?></div>
                <div class="ssm-stat-label">Kursy</div>
            </div>
            
            <div class="ssm-stat-box">
                <div class="ssm-stat-icon">✅</div>
                <div class="ssm-stat-number"><?php echo $stats['enrollments']; ?></div>
                <div class="ssm-stat-label">Aktywne zapisy</div>
            </div>
            
            <div class="ssm-stat-box ssm-stat-highlight">
                <div class="ssm-stat-icon">📅</div>
                <div class="ssm-stat-number"><?php echo $stats['sessions_today']; ?></div>
                <div class="ssm-stat-label">Zajęcia dzisiaj</div>
            </div>
        </div>
        
        <!-- Nadchodzące zajęcia -->
        <div class="ssm-section">
            <h2>Nadchodzące zajęcia (7 dni)</h2>
            
            <?php if ($upcoming_sessions): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Dzień</th>
                        <th>Godzina</th>
                        <th>Kurs</th>
                        <th>Obiekt</th>
                        <th>Instruktor</th>
                        <th>Sesja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_sessions as $session): 
                        $date = new DateTime($session->session_date);
                        $dow = $date->format('N');
                        $is_today = ($session->session_date == date('Y-m-d'));
                    ?>
                    <tr <?php echo $is_today ? 'style="background:#fff3cd;"' : ''; ?>>
                        <td>
                            <strong><?php echo date('d.m.Y', strtotime($session->session_date)); ?></strong>
                            <?php if ($is_today): ?>
                            <span style="color:#856404;font-weight:bold;">DZISIAJ</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $days_pl[$dow]; ?></td>
                        <td><?php echo substr($session->time_start, 0, 5) . ' - ' . substr($session->time_end, 0, 5); ?></td>
                        <td><strong><?php echo esc_html($session->class_name); ?></strong></td>
                        <td><?php echo esc_html($session->facility_name); ?></td>
                        <td><?php echo esc_html($session->instructor_name); ?></td>
                        <td>#<?php echo $session->session_number; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Brak zaplanowanych zajęć w najbliższych 7 dniach.</p>
            <?php endif; ?>
        </div>
        
        <!-- Szybkie linki -->
        <div class="ssm-quick-links">
            <h2>Szybkie akcje</h2>
            <div class="ssm-buttons">
                <a href="?page=ssm-children" class="button button-primary button-large">➕ Dodaj dziecko</a>
                <a href="?page=ssm-instructors" class="button button-primary button-large">➕ Dodaj instruktora</a>
                <a href="?page=ssm-classes" class="button button-primary button-large">➕ Dodaj kurs</a>
                <a href="?page=ssm-enrollments" class="button button-primary button-large">➕ Zapisz na kurs</a>
            </div>
        </div>
        
    </div>
</div>

<style>
.ssm-dashboard {
    max-width: 1200px;
}

.ssm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.ssm-stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ssm-stat-box:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.ssm-stat-highlight {
    background: #fff3cd;
    border-color: #ffc107;
}

.ssm-stat-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.ssm-stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #2271b1;
    margin: 10px 0;
}

.ssm-stat-label {
    font-size: 14px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ssm-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.ssm-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
}

.ssm-quick-links {
    margin: 30px 0;
}

.ssm-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.ssm-buttons .button-large {
    padding: 10px 20px;
    height: auto;
}
</style>
