<?php
// Shortcode: [swimming_classes_table]
if (!defined('ABSPATH')) exit;

global $wpdb;

// Znajdź stronę ze shortcode [swimming_login] - raz dla wszystkich kursów
$login_page_id = $wpdb->get_var(
    "SELECT ID FROM {$wpdb->posts} 
     WHERE post_content LIKE '%[swimming_login]%' 
     AND post_status = 'publish' 
     AND post_type = 'page' 
     LIMIT 1"
);

if ($login_page_id) {
    $login_url = get_permalink($login_page_id);
} else {
    // Fallback - użyj bieżącej strony (użytkownik musi sam stworzyć stronę z tym shortcode)
    $login_url = home_url('/logowanie/'); // Możesz zmienić na dowolny URL
}

$classes = $wpdb->get_results("
    SELECT c.*, 
           f.name as facility_name, f.url as facility_url,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
           i.photo as instructor_photo, i.url as instructor_url,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_enrollments WHERE class_id = c.id AND status = 'active') as enrolled_count,
           (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions 
            WHERE class_id = c.id AND session_date >= CURDATE() AND status = 'scheduled') as sessions_remaining
    FROM {$wpdb->prefix}ssm_classes c
    LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
    LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
    WHERE c.status = 'active'
    ORDER BY c.day_of_week, c.time_start
");

// Oblicz proporcjonalną cenę dla każdego kursu
foreach ($classes as $class) {
    $class->sessions_remaining = (int)$class->sessions_remaining;
    
    // Jeśli brak sesji w bazie, użyj domyślnej wartości
    if ($class->sessions_remaining == 0) {
        $class->sessions_remaining = $class->session_count;
    }
    
    // Oblicz cenę proporcjonalną
    $class->price_proportional = $class->price_per_session * $class->sessions_remaining;
}

$days_pl = array(1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela');

if (!$classes): ?>
    <p>Obecnie brak dostępnych kursów.</p>
<?php else: ?>
    <table class="ssm-classes-table">
        <thead>
            <tr>
                <th>Kurs</th>
                <th>Obiekt</th>
                <th>Instruktor</th>
                <th>Termin</th>
                <th>Zajęcia</th>
                <th>Cena</th>
                <th>Wolne miejsca</th>
                <th>Akcja</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $class): 
                $free = $class->max_participants - $class->enrolled_count;
                $sessions_past = $class->session_count - $class->sessions_remaining;
                $is_in_progress = ($sessions_past > 0 && $class->sessions_remaining > 0);
                $is_full = ($free <= 0);
                $has_sessions = ($class->sessions_remaining > 0);
            ?>
            <tr>
                <td><strong><?php echo esc_html($class->name); ?></strong></td>
                <td><?php echo esc_html($class->facility_name); ?></td>
                <td><?php echo esc_html($class->instructor_name); ?></td>
                <td><?php echo $days_pl[$class->day_of_week]; ?> <?php echo substr($class->time_start, 0, 5); ?></td>
                <td>
                    <?php if ($is_in_progress): ?>
                        <span style="color:#d63638; font-weight:600;">
                            Pozostało: <?php echo $class->sessions_remaining; ?>
                        </span>
                        <br><small style="color:#6c757d;">z <?php echo $class->session_count; ?> zajęć</small>
                    <?php else: ?>
                        <?php echo $class->session_count; ?> zajęć
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="color:#2271b1;">
                        <?php echo number_format($class->price_proportional, 2); ?> PLN
                    </strong>
                    <?php if ($is_in_progress && $class->price_proportional < $class->total_price): ?>
                        <br><small style="color:#6c757d; text-decoration:line-through;">
                            <?php echo number_format($class->total_price, 2); ?> PLN
                        </small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($is_full): ?>
                        <span class="ssm-badge ssm-badge-full">Brak miejsc</span>
                    <?php elseif (!$has_sessions): ?>
                        <span class="ssm-badge ssm-badge-full">Zakończony</span>
                    <?php else: ?>
                        <span style="color:#28a745; font-weight:600;">
                            <?php echo max(0, $free); ?> / <?php echo $class->max_participants; ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($is_full || !$has_sessions): ?>
                        <button class="ssm-btn-select" disabled>Niedostępny</button>
                    <?php else: ?>
                        <a href="<?php echo esc_url(add_query_arg('select_class', $class->id, $login_url)); ?>" 
                           class="ssm-btn-select">
                            Wybieram →
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;
