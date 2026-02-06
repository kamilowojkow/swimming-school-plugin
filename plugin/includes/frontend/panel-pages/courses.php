<?php
// Kursy - lista wszystkich kursów dzieci
if (!defined('ABSPATH')) exit;

// ID kursu do szczegółów
$viewing_course_id = isset($_GET['view_course']) ? intval($_GET['view_course']) : 0;
$viewing_enrollment = null;

if ($viewing_course_id) {
    $viewing_enrollment = $wpdb->get_row($wpdb->prepare(
        "SELECT e.*, c.id as class_id, c.name as class_name, c.day_of_week, c.time_start, c.time_end,
                c.session_count, c.total_price, c.start_date, c.description as class_description,
                CONCAT(ch.first_name, ' ', ch.last_name) as child_name, ch.id as child_id,
                f.name as facility_name, f.address as facility_address,
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                i.email as instructor_email, i.phone as instructor_phone,
                (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions
                 WHERE class_id = c.id AND session_date < CURDATE() AND status != 'cancelled') as sessions_past,
                (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_sessions
                 WHERE class_id = c.id AND session_date >= CURDATE() AND status = 'scheduled') as sessions_remaining,
                (SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance a
                 JOIN {$wpdb->prefix}ssm_sessions s ON a.session_id = s.id
                 WHERE s.class_id = c.id AND a.child_id = ch.id AND a.status = 'present') as sessions_attended
         FROM {$wpdb->prefix}ssm_enrollments e
         LEFT JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
         LEFT JOIN {$wpdb->prefix}ssm_classes c ON e.class_id = c.id
         LEFT JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
         LEFT JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
         WHERE e.id = %d AND e.client_id = %d",
        $viewing_course_id,
        $client->id
    ));
}

// Pobierz kursy
$enrollments = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, c.name as class_name, c.day_of_week, c.time_start, c.time_end,
            c.session_count, c.total_price, c.start_date,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            ch.id as child_id,
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

// Polish day names
$days_pl = array(
    'Monday' => 'Poniedziałek',
    'Tuesday' => 'Wtorek',
    'Wednesday' => 'Środa',
    'Thursday' => 'Czwartek',
    'Friday' => 'Piątek',
    'Saturday' => 'Sobota',
    'Sunday' => 'Niedziela'
);
?>

<div class="ssm-page-header">
    <h1><i class="ri-swimming-line"></i> <?php echo ssm_t('courses'); ?></h1>
    <p><?php echo ssm_t('courses_description'); ?></p>
</div>

<?php if ($viewing_enrollment): ?>

    <!-- Szczegółowy widok kursu -->
    <?php
    $percentage = ($viewing_enrollment->sessions_past + $viewing_enrollment->sessions_remaining) > 0
        ? round(($viewing_enrollment->sessions_past / ($viewing_enrollment->sessions_past + $viewing_enrollment->sessions_remaining)) * 100)
        : 0;
    $attendance_rate = $viewing_enrollment->sessions_past > 0
        ? round(($viewing_enrollment->sessions_attended / $viewing_enrollment->sessions_past) * 100)
        : 100;

    // Pobierz najbliższe zajęcia
    $upcoming_sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*,
                (SELECT a.status FROM {$wpdb->prefix}ssm_attendance a WHERE a.session_id = s.id AND a.child_id = %d) as attendance_status
         FROM {$wpdb->prefix}ssm_sessions s
         WHERE s.class_id = %d
         AND s.session_date >= CURDATE()
         AND s.status = 'scheduled'
         ORDER BY s.session_date ASC
         LIMIT 5",
        $viewing_enrollment->child_id,
        $viewing_enrollment->class_id
    ));
    ?>

    <div class="ssm-course-detail-view">
        <a href="<?php echo remove_query_arg('view_course'); ?>" class="ssm-back-link">
            <i class="ri-arrow-left-line"></i> <?php echo ssm_t('back_to_list'); ?>
        </a>

        <!-- Header z danymi kursu -->
        <div class="ssm-course-detail-header">
            <div class="ssm-course-detail-icon">
                <i class="ri-swimming-line"></i>
            </div>
            <div class="ssm-course-detail-info">
                <h2><?php echo esc_html($viewing_enrollment->class_name); ?></h2>
                <div class="ssm-course-detail-meta">
                    <span><i class="ri-user-line"></i> <?php echo esc_html($viewing_enrollment->child_name); ?></span>
                    <span><i class="ri-map-pin-line"></i> <?php echo esc_html($viewing_enrollment->facility_name); ?></span>
                    <span class="ssm-status-badge ssm-badge-active">
                        <i class="ri-checkbox-circle-line"></i> <?php echo ssm_t('status_active'); ?>
                    </span>
                </div>
            </div>
            <div class="ssm-course-detail-progress">
                <div class="ssm-progress-circle-large">
                    <svg viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                        <circle cx="50" cy="50" r="45" fill="none" stroke="var(--ssm-primary)" stroke-width="8"
                                stroke-dasharray="<?php echo $percentage * 2.83; ?> 283"
                                stroke-linecap="round" transform="rotate(-90 50 50)"/>
                    </svg>
                    <div class="ssm-progress-value"><?php echo $percentage; ?>%</div>
                </div>
                <span class="ssm-progress-label"><?php echo ssm_t('completed'); ?></span>
            </div>
        </div>

        <!-- Statystyki -->
        <div class="ssm-stats-grid">
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon blue">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo $viewing_enrollment->sessions_past; ?>/<?php echo $viewing_enrollment->session_count; ?></h3>
                    <p><?php echo ssm_t('sessions_completed'); ?></p>
                </div>
            </div>
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon green">
                    <i class="ri-calendar-todo-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo $viewing_enrollment->sessions_remaining; ?></h3>
                    <p><?php echo ssm_t('sessions_remaining'); ?></p>
                </div>
            </div>
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon orange">
                    <i class="ri-user-follow-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo $attendance_rate; ?>%</h3>
                    <p><?php echo ssm_t('attendance_rate'); ?></p>
                </div>
            </div>
            <div class="ssm-stat-card">
                <div class="ssm-stat-icon purple">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
                <div class="ssm-stat-content">
                    <h3><?php echo number_format($viewing_enrollment->total_price, 0, ',', ' '); ?> zł</h3>
                    <p><?php echo ssm_t('course_price'); ?></p>
                </div>
            </div>
        </div>

        <!-- Szczegóły kursu -->
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-information-line"></i>
                    <?php echo ssm_t('course_details'); ?>
                </h3>
            </div>
            <div class="ssm-course-details-grid">
                <div class="ssm-detail-item">
                    <div class="ssm-detail-icon">
                        <i class="ri-calendar-line"></i>
                    </div>
                    <div class="ssm-detail-content">
                        <span class="ssm-detail-label"><?php echo ssm_t('schedule'); ?></span>
                        <span class="ssm-detail-value"><?php echo $days_pl[$viewing_enrollment->day_of_week]; ?>, <?php echo substr($viewing_enrollment->time_start, 0, 5); ?> - <?php echo substr($viewing_enrollment->time_end, 0, 5); ?></span>
                    </div>
                </div>
                <div class="ssm-detail-item">
                    <div class="ssm-detail-icon">
                        <i class="ri-map-pin-line"></i>
                    </div>
                    <div class="ssm-detail-content">
                        <span class="ssm-detail-label"><?php echo ssm_t('facility'); ?></span>
                        <span class="ssm-detail-value"><?php echo esc_html($viewing_enrollment->facility_name); ?></span>
                        <?php if ($viewing_enrollment->facility_address): ?>
                        <span class="ssm-detail-sub"><?php echo esc_html($viewing_enrollment->facility_address); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ssm-detail-item">
                    <div class="ssm-detail-icon">
                        <i class="ri-user-star-line"></i>
                    </div>
                    <div class="ssm-detail-content">
                        <span class="ssm-detail-label"><?php echo ssm_t('instructor'); ?></span>
                        <span class="ssm-detail-value"><?php echo esc_html($viewing_enrollment->instructor_name); ?></span>
                    </div>
                </div>
                <div class="ssm-detail-item">
                    <div class="ssm-detail-icon">
                        <i class="ri-calendar-event-line"></i>
                    </div>
                    <div class="ssm-detail-content">
                        <span class="ssm-detail-label"><?php echo ssm_t('start_date'); ?></span>
                        <span class="ssm-detail-value"><?php echo date('d.m.Y', strtotime($viewing_enrollment->start_date)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Najbliższe zajęcia -->
        <?php if ($upcoming_sessions): ?>
        <div class="ssm-section">
            <div class="ssm-section-header">
                <h3 class="ssm-section-title">
                    <i class="ri-calendar-check-line"></i>
                    <?php echo ssm_t('upcoming_classes'); ?>
                </h3>
                <a href="<?php echo add_query_arg('panel_page', 'schedule', get_permalink()); ?>" class="ssm-widget-header-link">
                    <?php echo ssm_t('view_full_schedule'); ?> <i class="ri-arrow-right-s-line"></i>
                </a>
            </div>
            <div class="ssm-upcoming-sessions-list">
                <?php foreach ($upcoming_sessions as $session):
                    $date = new DateTime($session->session_date);
                    $is_today = ($session->session_date == date('Y-m-d'));
                ?>
                <div class="ssm-session-item <?php echo $is_today ? 'is-today' : ''; ?>">
                    <div class="ssm-session-date">
                        <span class="day"><?php echo $date->format('d'); ?></span>
                        <span class="month"><?php echo ssm_get_month_name_pl($date); ?></span>
                    </div>
                    <div class="ssm-session-info">
                        <span class="ssm-session-day"><?php echo $days_pl[$date->format('l')]; ?></span>
                        <span class="ssm-session-time">
                            <i class="ri-time-line"></i>
                            <?php echo substr($viewing_enrollment->time_start, 0, 5); ?> - <?php echo substr($viewing_enrollment->time_end, 0, 5); ?>
                        </span>
                    </div>
                    <?php if ($is_today): ?>
                    <span class="ssm-session-badge today"><?php echo ssm_t('today'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>

<!-- Lista kursów jako karty -->
<div class="ssm-courses-cards">
    <?php if ($enrollments): ?>
        <?php foreach ($enrollments as $enrollment):
            $percentage = ($enrollment->sessions_past + $enrollment->sessions_remaining) > 0
                ? round(($enrollment->sessions_past / ($enrollment->sessions_past + $enrollment->sessions_remaining)) * 100)
                : 0;

            // Określ kolor na podstawie postępu
            $progress_color = '#3b82f6';
            if ($percentage >= 75) $progress_color = '#10b981';
            elseif ($percentage >= 50) $progress_color = '#f59e0b';
            elseif ($percentage >= 25) $progress_color = '#6366f1';
        ?>
            <div class="ssm-course-card">
                <!-- Badges na górze -->
                <div class="ssm-card-badges">
                    <span class="ssm-badge-status ssm-badge-active">
                        <i class="ri-checkbox-circle-fill"></i> <?php echo ssm_t('status_active'); ?>
                    </span>
                    <span class="ssm-badge-day">
                        <i class="ri-calendar-fill"></i> <?php echo $days_pl[$enrollment->day_of_week]; ?>
                    </span>
                </div>

                <!-- Icon / Avatar -->
                <div class="ssm-card-avatar" style="background: linear-gradient(135deg, <?php echo $progress_color; ?> 0%, <?php echo $progress_color; ?>99 100%);">
                    <i class="ri-swimming-line"></i>
                </div>

                <!-- Nazwa kursu -->
                <h3 class="ssm-card-name"><?php echo esc_html($enrollment->class_name); ?></h3>

                <!-- Info o dziecku -->
                <p class="ssm-card-child">
                    <i class="ri-user-line"></i> <?php echo esc_html($enrollment->child_name); ?>
                </p>

                <!-- Szczegóły -->
                <div class="ssm-card-details">
                    <span><i class="ri-time-line"></i> <?php echo substr($enrollment->time_start, 0, 5); ?> - <?php echo substr($enrollment->time_end, 0, 5); ?></span>
                    <span><i class="ri-map-pin-line"></i> <?php echo esc_html($enrollment->facility_name); ?></span>
                </div>

                <!-- Pasek postępu -->
                <div class="ssm-card-progress">
                    <div class="ssm-card-progress-header">
                        <span><?php echo ssm_t('progress'); ?></span>
                        <span><?php echo $enrollment->sessions_past; ?>/<?php echo $enrollment->session_count; ?> <?php echo ssm_t('sessions'); ?></span>
                    </div>
                    <div class="ssm-card-progress-bar">
                        <div class="ssm-card-progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $progress_color; ?>;"></div>
                    </div>
                    <div class="ssm-card-progress-text">
                        <?php echo $percentage; ?>% <?php echo ssm_t('completed'); ?>
                    </div>
                </div>

                <!-- Instruktor -->
                <div class="ssm-card-instructor">
                    <i class="ri-user-star-line"></i>
                    <span><?php echo esc_html($enrollment->instructor_name); ?></span>
                </div>

                <!-- Przycisk szczegóły -->
                <a href="<?php echo add_query_arg('view_course', $enrollment->id); ?>" class="ssm-card-btn">
                    <i class="ri-eye-line"></i> <?php echo ssm_t('view_details'); ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="ssm-empty-state" style="grid-column: 1 / -1;">
            <i class="ri-swimming-line"></i>
            <p><?php echo ssm_t('no_active_courses'); ?></p>
            <small><?php echo ssm_t('contact_admin_to_enroll'); ?></small>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<style>
/* ========================================
   KARTY KURSÓW - FILA STYLE
   ======================================== */

.ssm-courses-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.ssm-course-card {
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    padding: 24px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.ssm-course-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--ssm-shadow-lg);
}

/* Badges */
.ssm-course-card .ssm-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-bottom: 20px;
}

.ssm-badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-badge-status.ssm-badge-active {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-badge-day {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: var(--ssm-info-light);
    color: var(--ssm-info);
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Avatar / Icon */
.ssm-course-card .ssm-card-avatar {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    box-shadow: var(--ssm-shadow-md);
}

.ssm-course-card .ssm-card-avatar i {
    font-size: 42px;
    color: white;
}

/* Name */
.ssm-course-card .ssm-card-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--ssm-text);
    margin: 0 0 8px 0;
}

/* Child info */
.ssm-card-child {
    font-size: 14px;
    color: var(--ssm-primary);
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

/* Details */
.ssm-card-details {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-card-details span {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Progress bar */
.ssm-course-card .ssm-card-progress {
    width: 100%;
    margin-bottom: 16px;
    text-align: left;
}

.ssm-card-progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--ssm-text-muted);
    margin-bottom: 6px;
}

.ssm-course-card .ssm-card-progress-bar {
    height: 8px;
    background: var(--ssm-border-light);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}

.ssm-course-card .ssm-card-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.ssm-course-card .ssm-card-progress-text {
    font-size: 11px;
    color: var(--ssm-text-light);
    text-align: right;
}

/* Instructor */
.ssm-card-instructor {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ssm-text-muted);
    margin-bottom: 16px;
    padding: 8px 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
}

.ssm-card-instructor i {
    color: var(--ssm-warning);
}

/* Button */
.ssm-course-card .ssm-card-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 24px;
    background: rgba(59, 130, 246, 0.1);
    color: var(--ssm-primary);
    font-size: 14px;
    font-weight: 600;
    border-radius: var(--ssm-radius);
    text-decoration: none;
    transition: all 0.2s ease;
    margin-top: auto;
}

.ssm-course-card .ssm-card-btn:hover {
    background: var(--ssm-primary);
    color: white;
}

/* ========================================
   SZCZEGÓŁOWY WIDOK KURSU
   ======================================== */

.ssm-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--ssm-text-muted);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 20px;
    transition: color 0.2s;
}

.ssm-back-link:hover {
    color: var(--ssm-primary);
}

.ssm-course-detail-header {
    display: flex;
    align-items: center;
    gap: 24px;
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    padding: 24px;
    margin-bottom: 24px;
}

.ssm-course-detail-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--ssm-primary) 0%, #6366f1 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: white;
    flex-shrink: 0;
}

.ssm-course-detail-info {
    flex: 1;
}

.ssm-course-detail-info h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    color: var(--ssm-text);
}

.ssm-course-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    color: var(--ssm-text-muted);
    font-size: 14px;
}

.ssm-course-detail-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ssm-status-badge.ssm-badge-active {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

/* Progress circle */
.ssm-course-detail-progress {
    text-align: center;
    flex-shrink: 0;
}

.ssm-progress-circle-large {
    width: 100px;
    height: 100px;
    position: relative;
}

.ssm-progress-circle-large svg {
    width: 100%;
    height: 100%;
}

.ssm-progress-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 22px;
    font-weight: 700;
    color: var(--ssm-text);
}

.ssm-progress-label {
    font-size: 12px;
    color: var(--ssm-text-muted);
    margin-top: 8px;
    display: block;
}

/* Course details grid */
.ssm-course-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.ssm-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
}

.ssm-detail-icon {
    width: 44px;
    height: 44px;
    background: var(--ssm-bg-white);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--ssm-primary);
    flex-shrink: 0;
}

.ssm-detail-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ssm-detail-label {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-detail-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-detail-sub {
    font-size: 12px;
    color: var(--ssm-text-light);
}

/* Upcoming sessions */
.ssm-upcoming-sessions-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ssm-session-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
    transition: all 0.2s ease;
}

.ssm-session-item:hover {
    background: rgba(59, 130, 246, 0.05);
}

.ssm-session-item.is-today {
    background: var(--ssm-success-light);
    border-left: 4px solid var(--ssm-success);
}

.ssm-session-date {
    width: 50px;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.ssm-session-date .day {
    font-size: 22px;
    font-weight: 700;
    color: var(--ssm-text);
    line-height: 1;
}

.ssm-session-date .month {
    font-size: 11px;
    color: var(--ssm-text-muted);
    text-transform: uppercase;
}

.ssm-session-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ssm-session-day {
    font-size: 14px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-session-time {
    font-size: 13px;
    color: var(--ssm-text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-session-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.ssm-session-badge.today {
    background: var(--ssm-success);
    color: white;
}

/* Widget header link */
.ssm-widget-header-link {
    font-size: 13px;
    color: var(--ssm-primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-widget-header-link:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-courses-cards {
        grid-template-columns: 1fr;
    }

    .ssm-course-detail-header {
        flex-direction: column;
        text-align: center;
    }

    .ssm-course-detail-meta {
        justify-content: center;
    }

    .ssm-course-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>
