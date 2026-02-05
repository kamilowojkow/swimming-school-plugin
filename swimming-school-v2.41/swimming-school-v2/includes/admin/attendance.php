<?php
/**
 * Panel Admin - Frekwencja (lista podglądowa)
 */
if (!defined('ABSPATH')) exit;

// Sprawdź uprawnienia admina
if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień do tej strony.');
}

global $wpdb;

// Filtry
$selected_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$selected_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d', strtotime('+30 days'));
$selected_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$selected_instructor = isset($_GET['instructor_id']) ? intval($_GET['instructor_id']) : 0;
$show_status = isset($_GET['show_status']) ? sanitize_text_field($_GET['show_status']) : 'all';

// Pobierz listę kursów
$courses = $wpdb->get_results("
    SELECT DISTINCT c.id, c.name 
    FROM {$wpdb->prefix}ssm_classes c
    WHERE c.status = 'active'
    ORDER BY c.name
");

// Pobierz listę instruktorów
$instructors = $wpdb->get_results("
    SELECT id, CONCAT(first_name, ' ', last_name) as name
    FROM {$wpdb->prefix}ssm_instructors
    WHERE active = 1
    ORDER BY last_name, first_name
");

// Buduj query z filtrami
$where_conditions = array("s.session_date BETWEEN %s AND %s");
$query_params = array($selected_date_from, $selected_date_to);

if ($selected_course > 0) {
    $where_conditions[] = "c.id = %d";
    $query_params[] = $selected_course;
}

if ($selected_instructor > 0) {
    $where_conditions[] = "(c.instructor_id = %d OR s.instructor_id = %d)";
    $query_params[] = $selected_instructor;
    $query_params[] = $selected_instructor;
}

$where_sql = implode(' AND ', $where_conditions);

// Pobierz zajęcia z frekwencją
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, 
            c.name as class_name, c.id as class_id,
            f.name as facility_name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments 
             WHERE class_id = c.id AND status = 'active') as enrolled_count,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance 
             WHERE session_id = s.id) as attendance_marked,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance 
             WHERE session_id = s.id AND status = 'present') as present_count,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance 
             WHERE session_id = s.id AND status = 'excused') as excused_count,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance 
             WHERE session_id = s.id AND status = 'absent') as absent_count
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
     WHERE $where_sql
     AND s.status = 'scheduled'
     ORDER BY s.session_date DESC, s.time_start DESC",
    $query_params
));

// Filtruj po statusie
if ($show_status == 'checked') {
    $sessions = array_filter($sessions, function($s) { return $s->attendance_marked > 0; });
} elseif ($show_status == 'unchecked') {
    $sessions = array_filter($sessions, function($s) { return $s->attendance_marked == 0; });
}

// Obsługa podglądu szczegółów
$view_session = null;
$view_attendance = array();
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['session_id'])) {
    $session_id = intval($_GET['session_id']);
    
    $view_session = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, c.name as class_name, c.id as class_id, f.name as facility_name,
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name
         FROM {$wpdb->prefix}ssm_sessions s
         JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
         LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
         LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
         WHERE s.id = %d",
        $session_id
    ));
    
    if ($view_session) {
        $view_attendance = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, 
                    CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
                    CONCAT(cl.first_name, ' ', cl.last_name) as parent_name
             FROM {$wpdb->prefix}ssm_attendance a
             JOIN {$wpdb->prefix}ssm_children ch ON a.child_id = ch.id
             JOIN {$wpdb->prefix}ssm_enrollments e ON e.child_id = ch.id AND e.class_id = %d
             JOIN {$wpdb->prefix}ssm_clients cl ON e.client_id = cl.id
             WHERE a.session_id = %d
             ORDER BY ch.last_name, ch.first_name",
            $view_session->class_id, $session_id
        ));
    }
}

?>

<div class="wrap">
    <h1>✓ Frekwencja - Podgląd</h1>
    <p class="description">Lista zajęć z informacją o obecności. Instruktorzy sprawdzają obecność w swoim panelu.</p>
    
    <!-- Filtry -->
    <div class="ssm-filters" style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <form method="get">
            <input type="hidden" name="page" value="ssm-attendance">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data od:</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($selected_date_from); ?>" 
                           class="regular-text">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data do:</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($selected_date_to); ?>" 
                           class="regular-text">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Kurs:</label>
                    <select name="course_id" class="regular-text">
                        <option value="0">-- Wszystkie --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course->id; ?>" <?php selected($selected_course, $course->id); ?>>
                                <?php echo esc_html($course->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Instruktor:</label>
                    <select name="instructor_id" class="regular-text">
                        <option value="0">-- Wszyscy --</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor->id; ?>" <?php selected($selected_instructor, $instructor->id); ?>>
                                <?php echo esc_html($instructor->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Status:</label>
                    <select name="show_status" class="regular-text">
                        <option value="all" <?php selected($show_status, 'all'); ?>>Wszystkie</option>
                        <option value="checked" <?php selected($show_status, 'checked'); ?>>Sprawdzone</option>
                        <option value="unchecked" <?php selected($show_status, 'unchecked'); ?>>Niesprawdzone</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="button button-primary">
                🔍 Filtruj zajęcia
            </button>
            
            <a href="?page=ssm-attendance" class="button">
                ↺ Reset filtrów
            </a>
            
            <a href="?page=ssm-attendance&date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d', strtotime('+7 days')); ?>" 
               class="button">
                🔄 Resetuj
            </a>
        </form>
    </div>
    
    <!-- Statystyki -->
    <?php 
    $total = count($sessions);
    $checked = count(array_filter($sessions, function($s) { return $s->attendance_marked > 0; }));
    $unchecked = $total - $checked;
    ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #2271b1;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Wszystkie zajęcia</div>
            <div style="font-size: 32px; font-weight: 700; color: #2271b1;"><?php echo $total; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Frekwencja sprawdzona</div>
            <div style="font-size: 32px; font-weight: 700; color: #10b981;"><?php echo $checked; ?></div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #dc3232;">
            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Niesprawdzona</div>
            <div style="font-size: 32px; font-weight: 700; color: #dc3232;"><?php echo $unchecked; ?></div>
        </div>
    </div>
    
    <!-- Lista zajęć -->
    <?php if (empty($sessions)): ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 16px;">
                📅 Brak zajęć w wybranym okresie
            </p>
        </div>
    <?php else: ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 120px;">Data</th>
                    <th style="width: 100px;">Godzina</th>
                    <th>Kurs</th>
                    <th>Miejsce</th>
                    <th>Instruktor</th>
                    <th style="width: 80px; text-align: center;">Zapisanych</th>
                    <th style="width: 250px;">Frekwencja</th>
                    <th style="width: 100px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): 
                    $date = new DateTime($session->session_date);
                    $is_today = ($session->session_date == date('Y-m-d'));
                    $is_past = ($session->session_date < date('Y-m-d'));
                    $is_checked = ($session->attendance_marked > 0);
                ?>
                <tr style="<?php echo $is_today ? 'background: #fff3cd;' : ''; ?>">
                    <td>
                        <strong><?php echo $date->format('d.m.Y'); ?></strong><br>
                        <small style="color: #666;"><?php echo ssm_get_day_name_pl($date); ?></small>
                        <?php if ($is_today): ?>
                            <br><span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">DZISIAJ</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo substr($session->time_start, 0, 5); ?><br>
                        <small style="color: #666;"><?php echo substr($session->time_end, 0, 5); ?></small>
                    </td>
                    <td><strong><?php echo esc_html($session->class_name); ?></strong></td>
                    <td><?php echo esc_html($session->facility_name); ?></td>
                    <td><?php echo esc_html($session->instructor_name); ?></td>
                    <td style="text-align: center;">
                        <span style="background: #e5e7eb; padding: 4px 12px; border-radius: 12px; font-weight: 600; display: inline-block;">
                            <?php echo $session->enrolled_count; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($is_checked): ?>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <span style="background: #d1fae5; color: #059669; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                    ✓ <?php echo $session->present_count; ?>
                                </span>
                                <?php if ($session->excused_count > 0): ?>
                                    <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                        ⚠ <?php echo $session->excused_count; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($session->absent_count > 0): ?>
                                    <span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                        ✗ <?php echo $session->absent_count; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #9ca3af; font-style: italic; font-size: 13px;">
                                <?php echo $is_past ? '⚠ Niesprawdzona' : '— Jeszcze niesprawdzona'; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_checked): ?>
                            <a href="?page=ssm-attendance&action=view&session_id=<?php echo $session->id; ?>&date_from=<?php echo urlencode($selected_date_from); ?>&date_to=<?php echo urlencode($selected_date_to); ?>&course_id=<?php echo $selected_course; ?>&instructor_id=<?php echo $selected_instructor; ?>&show_status=<?php echo $show_status; ?>" 
                               class="button button-small">
                                👁 Podgląd
                            </a>
                        <?php else: ?>
                            <span style="color: #9ca3af; font-size: 11px;">
                                Sprawdza<br>instruktor
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    <?php endif; ?>
</div>

<?php
// Modal podglądu szczegółów
if ($view_session):
    $date = new DateTime($view_session->session_date);
?>
    
    <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 99999; display: flex; align-items: center; justify-content: center;" 
         onclick="if(event.target === this) window.location='?page=ssm-attendance&date_from=<?php echo urlencode($selected_date_from); ?>&date_to=<?php echo urlencode($selected_date_to); ?>&course_id=<?php echo $selected_course; ?>&instructor_id=<?php echo $selected_instructor; ?>&show_status=<?php echo $show_status; ?>'">
        <div style="background: white; border-radius: 12px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);" 
             onclick="event.stopPropagation()">
            
            <!-- Header -->
            <div style="padding: 30px; border-bottom: 2px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h2 style="margin: 0 0 10px 0;">Szczegóły frekwencji</h2>
                        <p style="margin: 0; color: #666; line-height: 1.6;">
                            <strong><?php echo esc_html($view_session->class_name); ?></strong><br>
                            <?php echo $date->format('d.m.Y'); ?> (<?php echo ssm_get_day_name_pl($date); ?>) • 
                            <?php echo substr($view_session->time_start, 0, 5); ?>-<?php echo substr($view_session->time_end, 0, 5); ?><br>
                            <?php echo esc_html($view_session->facility_name); ?> • 
                            <?php echo esc_html($view_session->instructor_name); ?>
                        </p>
                    </div>
                    <a href="?page=ssm-attendance&date_from=<?php echo urlencode($selected_date_from); ?>&date_to=<?php echo urlencode($selected_date_to); ?>&course_id=<?php echo $selected_course; ?>&instructor_id=<?php echo $selected_instructor; ?>&show_status=<?php echo $show_status; ?>" 
                       style="text-decoration: none; color: #666; font-size: 28px; line-height: 1; font-weight: 300;">&times;</a>
                </div>
            </div>
            
            <!-- Statystyki -->
            <div style="padding: 20px 30px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Obecnych</div>
                        <div style="font-size: 28px; font-weight: 700; color: #10b981;">
                            <?php echo count(array_filter($view_attendance, function($a) { return $a->status == 'present'; })); ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Zgłoszonych</div>
                        <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">
                            <?php echo count(array_filter($view_attendance, function($a) { return $a->status == 'excused'; })); ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Nieobecnych</div>
                        <div style="font-size: 28px; font-weight: 700; color: #dc2626;">
                            <?php echo count(array_filter($view_attendance, function($a) { return $a->status == 'absent'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista -->
            <div style="padding: 20px 30px; max-height: 400px; overflow-y: auto;">
                <?php if (empty($view_attendance)): ?>
                    <p style="text-align: center; color: #666; padding: 40px 0;">Brak danych frekwencji</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e5e7eb; position: sticky; top: 0; background: white;">
                                <th style="padding: 10px; text-align: left; font-weight: 600; width: 40px;">#</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600;">Dziecko</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600;">Rodzic</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            foreach ($view_attendance as $item): 
                                $status_colors = array(
                                    'present' => array('bg' => '#d1fae5', 'text' => '#059669', 'label' => '✓ Obecny'),
                                    'excused' => array('bg' => '#fef3c7', 'text' => '#d97706', 'label' => '⚠ Zgłoszony'),
                                    'absent' => array('bg' => '#fee2e2', 'text' => '#dc2626', 'label' => '✗ Nieobecny')
                                );
                                $status = $status_colors[$item->status] ?? array('bg' => '#e5e7eb', 'text' => '#666', 'label' => $item->status);
                            ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 12px; color: #9ca3af;"><?php echo $counter++; ?></td>
                                <td style="padding: 12px;"><strong><?php echo esc_html($item->child_name); ?></strong></td>
                                <td style="padding: 12px; color: #666;"><?php echo esc_html($item->parent_name); ?></td>
                                <td style="padding: 12px;">
                                    <span style="background: <?php echo $status['bg']; ?>; color: <?php echo $status['text']; ?>; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; display: inline-block;">
                                        <?php echo $status['label']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div style="padding: 20px 30px; border-top: 1px solid #e5e7eb; text-align: right; background: #f9fafb;">
                <a href="?page=ssm-attendance&date_from=<?php echo urlencode($selected_date_from); ?>&date_to=<?php echo urlencode($selected_date_to); ?>&course_id=<?php echo $selected_course; ?>&instructor_id=<?php echo $selected_instructor; ?>&show_status=<?php echo $show_status; ?>" 
                   class="button button-primary">Zamknij</a>
            </div>
            
        </div>
    </div>
    
<?php endif; ?>
