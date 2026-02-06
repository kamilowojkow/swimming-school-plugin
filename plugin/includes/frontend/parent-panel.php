<?php
// Shortcode: [swimming_parent_panel]
// Panel rodzica z zakładkami dzieci i harmonogramem zajęć
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div class="ssm-login-required">';
    echo '<p>Musisz być zalogowany aby zobaczyć panel rodzica.</p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="ssm-login-btn">Zaloguj się</a>';
    echo '</div>';
    return;
}

global $wpdb;
$current_user = wp_get_current_user();

// Znajdź klienta po emailu
$client = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_clients WHERE email = %s",
    $current_user->user_email
));

if (!$client): ?>
    <div class="ssm-no-access">
        <h3>⚠️ Brak dostępu</h3>
        <p>Nie znaleziono Twojego konta w systemie szkółki pływania.</p>
        <p>Twój email w WordPress: <strong><?php echo esc_html($current_user->user_email); ?></strong></p>
        <p>Skontaktuj się z administracją aby dodać Cię do systemu.</p>
    </div>
<?php return; endif;

// Pobierz dzieci klienta
$children = $wpdb->get_results($wpdb->prepare(
    "SELECT ch.*, 
            TIMESTAMPDIFF(YEAR, ch.date_of_birth, CURDATE()) as age
     FROM {$wpdb->prefix}ssm_children ch
     JOIN {$wpdb->prefix}ssm_client_children cc ON ch.id = cc.child_id
     WHERE cc.client_id = %d AND ch.active = 1
     ORDER BY ch.first_name",
    $client->id
));

if (!$children): ?>
    <div class="ssm-no-children">
        <h3>Brak przypisanych dzieci</h3>
        <p>Nie masz przypisanych dzieci w systemie szkółki pływania.</p>
    </div>
<?php return; endif;

$days_pl = array(1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela');
?>

<div class="ssm-parent-panel">
    
    <!-- Nagłówek z powitaniem -->
    <div class="ssm-panel-header">
        <h2>Witaj, <?php echo esc_html($client->first_name); ?>! 👋</h2>
        <p class="ssm-panel-subtitle">Oto harmonogram zajęć Twoich dzieci</p>
    </div>
    
    <!-- Zakładki dzieci -->
    <div class="ssm-tabs">
        <?php foreach ($children as $index => $child): ?>
            <button class="ssm-tab-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                    data-child-id="<?php echo $child->id; ?>">
                <span class="ssm-tab-icon">👶</span>
                <span class="ssm-tab-name"><?php echo esc_html($child->first_name); ?></span>
                <span class="ssm-tab-age">(<?php echo $child->age; ?> lat)</span>
            </button>
        <?php endforeach; ?>
    </div>
    
    <!-- Zawartość zakładek -->
    <?php foreach ($children as $index => $child): 
        // Pobierz zapisy dziecka
        $enrollments = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, 
                    c.name as class_name, 
                    c.day_of_week,
                    c.time_start,
                    c.time_end,
                    c.total_price,
                    c.session_count,
                    c.start_date,
                    f.name as facility_name,
                    f.address as facility_address,
                    CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                    i.photo as instructor_photo
             FROM {$wpdb->prefix}ssm_enrollments e
             LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
             LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
             LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
             WHERE e.child_id = %d AND e.status = 'active'
             ORDER BY c.day_of_week, c.time_start",
            $child->id
        ));
    ?>
    
    <div class="ssm-tab-content <?php echo $index === 0 ? 'active' : ''; ?>" 
         data-child-id="<?php echo $child->id; ?>">
        
        <?php if (!$enrollments): ?>
            <div class="ssm-no-enrollments">
                <p>📋 Brak aktywnych zapisów na kursy</p>
                <p><small>Skontaktuj się z administracją aby zapisać <?php echo esc_html($child->first_name); ?> na kurs.</small></p>
            </div>
        <?php else: ?>
            
            <!-- Podsumowanie -->
            <div class="ssm-summary">
                <div class="ssm-summary-item">
                    <span class="ssm-summary-label">Aktywne kursy:</span>
                    <span class="ssm-summary-value"><?php echo count($enrollments); ?></span>
                </div>
                <div class="ssm-summary-item">
                    <span class="ssm-summary-label">Łączna wartość:</span>
                    <span class="ssm-summary-value">
                        <?php 
                        $total = array_sum(array_column($enrollments, 'total_price'));
                        echo number_format($total, 2); 
                        ?> PLN
                    </span>
                </div>
            </div>
            
            <!-- Harmonogram - lista kursów -->
            <div class="ssm-courses-list">
                <?php foreach ($enrollments as $enrollment): 
                    // Pobierz najbliższe 5 zajęć dla tego kursu
                    $upcoming_sessions = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ssm_sessions 
                         WHERE class_id = %d 
                         AND session_date >= CURDATE() 
                         AND status = 'scheduled'
                         ORDER BY session_date 
                         LIMIT 5",
                        $enrollment->class_id
                    ));
                ?>
                
                <div class="ssm-course-card">
                    <div class="ssm-course-header">
                        <div class="ssm-course-title">
                            <h3><?php echo esc_html($enrollment->class_name); ?></h3>
                            <div class="ssm-course-meta">
                                <span class="ssm-meta-item">
                                    📍 <?php echo esc_html($enrollment->facility_name); ?>
                                </span>
                                <span class="ssm-meta-item">
                                    📅 <?php echo $days_pl[$enrollment->day_of_week]; ?>
                                </span>
                                <span class="ssm-meta-item">
                                    🕐 <?php echo substr($enrollment->time_start, 0, 5); ?>-<?php echo substr($enrollment->time_end, 0, 5); ?>
                                </span>
                            </div>
                        </div>
                        <div class="ssm-course-instructor">
                            <?php if ($enrollment->instructor_photo): ?>
                                <img src="<?php echo esc_url($enrollment->instructor_photo); ?>" 
                                     alt="<?php echo esc_attr($enrollment->instructor_name); ?>"
                                     class="ssm-instructor-avatar">
                            <?php endif; ?>
                            <div class="ssm-instructor-info">
                                <span class="ssm-instructor-label">Instruktor:</span>
                                <span class="ssm-instructor-name"><?php echo esc_html($enrollment->instructor_name); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Najbliższe zajęcia -->
                    <?php if ($upcoming_sessions): ?>
                    <div class="ssm-sessions-list">
                        <h4>Najbliższe zajęcia:</h4>
                        <table class="ssm-sessions-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Data</th>
                                    <th>Dzień</th>
                                    <th>Godzina</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_sessions as $session): 
                                    $date = new DateTime($session->session_date);
                                    $dow = $date->format('N');
                                    $is_today = ($session->session_date == date('Y-m-d'));
                                    $is_tomorrow = ($session->session_date == date('Y-m-d', strtotime('+1 day')));
                                ?>
                                <tr class="<?php echo $is_today ? 'ssm-today' : ''; ?>">
                                    <td><?php echo $session->session_number; ?></td>
                                    <td>
                                        <strong><?php echo date('d.m.Y', strtotime($session->session_date)); ?></strong>
                                        <?php if ($is_today): ?>
                                            <span class="ssm-badge ssm-badge-today">DZISIAJ</span>
                                        <?php elseif ($is_tomorrow): ?>
                                            <span class="ssm-badge ssm-badge-tomorrow">Jutro</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $days_pl[$dow]; ?></td>
                                    <td><?php echo substr($session->time_start, 0, 5); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="ssm-no-sessions">
                        <p>Brak zaplanowanych zajęć w najbliższym czasie.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Stopka kursu -->
                    <div class="ssm-course-footer">
                        <div class="ssm-course-price">
                            <span class="ssm-price-label">Cena kursu:</span>
                            <span class="ssm-price-value"><?php echo number_format($enrollment->total_price, 2); ?> PLN</span>
                        </div>
                        <div class="ssm-course-sessions">
                            <span class="ssm-sessions-label">Liczba zajęć:</span>
                            <span class="ssm-sessions-value"><?php echo $enrollment->session_count; ?>x</span>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            
        <?php endif; ?>
    </div>
    
    <?php endforeach; ?>
    
</div>

<script>
// Obsługa zakładek dzieci
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.ssm-tab-btn');
    const tabContents = document.querySelectorAll('.ssm-tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const childId = this.getAttribute('data-child-id');
            
            // Usuń active ze wszystkich
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Dodaj active do klikniętego
            this.classList.add('active');
            document.querySelector('.ssm-tab-content[data-child-id="' + childId + '"]').classList.add('active');
        });
    });
});
</script>
