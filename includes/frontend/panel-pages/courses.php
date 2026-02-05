<?php
// Kursy - lista wszystkich kursów dzieci
if (!defined('ABSPATH')) exit;

// Pobierz kursy
$enrollments = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, c.name as class_name, c.day_of_week, c.time_start, c.time_end,
            c.session_count, c.total_price, c.start_date,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            f.name as facility_name,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions 
             WHERE class_id = c.id AND session_date < CURDATE() AND status != 'cancelled') as sessions_past,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions 
             WHERE class_id = c.id AND session_date >= CURDATE() AND status = 'scheduled') as sessions_remaining
     FROM {$wpdb->prefix}ssm_enrollments e
     LEFT JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
     LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
     WHERE e.client_id = %d AND e.status = 'active'
     ORDER BY c.start_date DESC",
    $client->id
));
?>

<div class="ssm-page-header">
    <h1>🏊 Kursy</h1>
    <p>Kursy na które zapisane są Twoje dzieci</p>
</div>

<?php if ($enrollments): ?>
    <div class="ssm-courses-list">
        <?php foreach ($enrollments as $enrollment): ?>
            <div class="ssm-course-card-full">
                <div class="ssm-course-header">
                    <div>
                        <h3><?php echo esc_html($enrollment->class_name); ?></h3>
                        <p class="ssm-child-badge"><?php echo esc_html($enrollment->child_name); ?></p>
                    </div>
                    <div class="ssm-course-progress">
                        <div class="ssm-progress-circle">
                            <?php 
                            $percentage = ($enrollment->sessions_past + $enrollment->sessions_remaining) > 0 
                                ? round(($enrollment->sessions_past / ($enrollment->sessions_past + $enrollment->sessions_remaining)) * 100) 
                                : 0;
                            echo $percentage; 
                            ?>%
                        </div>
                        <small>Ukończono</small>
                    </div>
                </div>
                
                <div class="ssm-course-details">
                    <div class="ssm-detail-row">
                        <span class="ssm-detail-label">📍 Obiekt:</span>
                        <span><?php echo esc_html($enrollment->facility_name); ?></span>
                    </div>
                    <div class="ssm-detail-row">
                        <span class="ssm-detail-label">👨‍🏫 Instruktor:</span>
                        <span><?php echo esc_html($enrollment->instructor_name); ?></span>
                    </div>
                    <div class="ssm-detail-row">
                        <span class="ssm-detail-label">📅 Termin:</span>
                        <span><?php echo $days_pl[$enrollment->day_of_week]; ?>, <?php echo substr($enrollment->time_start, 0, 5); ?>-<?php echo substr($enrollment->time_end, 0, 5); ?></span>
                    </div>
                    <div class="ssm-detail-row">
                        <span class="ssm-detail-label">🎯 Postęp:</span>
                        <span><?php echo $enrollment->sessions_past; ?> z <?php echo $enrollment->session_count; ?> zajęć</span>
                    </div>
                    <div class="ssm-detail-row">
                        <span class="ssm-detail-label">💰 Cena:</span>
                        <span><strong><?php echo number_format($enrollment->total_price, 2, ',', ' '); ?> PLN</strong></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="ssm-empty-state">
        <p>📭 Brak aktywnych kursów</p>
    </div>
<?php endif; ?>

<style>
.ssm-courses-list {
    display: grid;
    gap: 20px;
}

.ssm-course-card-full {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ssm-course-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ssm-course-header h3 {
    margin: 0 0 8px 0;
    font-size: 22px;
}

.ssm-child-badge {
    background: rgba(255,255,255,0.2);
    padding: 5px 12px;
    border-radius: 12px;
    display: inline-block;
    margin: 0;
    font-size: 14px;
}

.ssm-course-progress {
    text-align: center;
}

.ssm-progress-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    margin: 0 auto 5px;
}

.ssm-course-details {
    padding: 25px;
}

.ssm-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.ssm-detail-row:last-child {
    border-bottom: none;
}

.ssm-detail-label {
    color: #6c757d;
    font-weight: 600;
}
</style>
