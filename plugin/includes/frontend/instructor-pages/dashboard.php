<?php
/**
 * Panel Instruktora - Dashboard (Fila Style)
 */
if (!defined('ABSPATH')) exit;

// Statystyki
$today = date('Y-m-d');
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

// Zajecia dzisiaj
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

// Statystyki miesiaca
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

// Nadchodzace zajecia
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

<!-- Welcome Banner -->
<div class="ssm-welcome-banner ssm-welcome-instructor">
    <div class="ssm-welcome-decoration">
        <div class="ssm-deco-circle ssm-deco-1"></div>
        <div class="ssm-deco-circle ssm-deco-2"></div>
        <div class="ssm-deco-circle ssm-deco-3"></div>
        <div class="ssm-deco-wave">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,60 C150,120 350,0 600,60 C850,120 1050,0 1200,60 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.1)"></path>
            </svg>
        </div>
    </div>
    <div class="ssm-welcome-content">
        <div class="ssm-welcome-greeting">
            <span class="ssm-greeting-icon"><i class="ri-hand-heart-line"></i></span>
            <span class="ssm-greeting-text"><?php echo ssm_t('welcome'); ?></span>
        </div>
        <h1><?php echo esc_html($instructor->first_name); ?> <?php echo esc_html($instructor->last_name); ?></h1>
        <p class="ssm-welcome-subtitle"><?php echo ssm_t('instr_dashboard_subtitle'); ?></p>
        <div class="ssm-welcome-date">
            <i class="ri-calendar-line"></i>
            <span><?php echo date_i18n('l, j F Y'); ?></span>
        </div>
    </div>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid">
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon blue">
            <i class="ri-calendar-check-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo count($today_sessions); ?></h3>
            <p><?php echo ssm_t('instr_today_classes'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon green">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $month_stats->completed_sessions; ?></h3>
            <p><?php echo ssm_t('instr_completed_month'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon purple">
            <i class="ri-group-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $month_stats->total_present ?: 0; ?></h3>
            <p><?php echo ssm_t('instr_children_attended'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card ssm-stat-salary">
        <div class="ssm-stat-icon">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo number_format($salary, 0, ',', ' '); ?> PLN</h3>
            <p><?php echo ssm_t('instr_salary_month'); ?> (<?php echo date('m/Y'); ?>)</p>
        </div>
    </div>
</div>

<!-- Dzisiejsze zajecia -->
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-calendar-todo-line"></i>
            <?php echo ssm_t('instr_today_schedule'); ?>
        </h3>
        <?php if (!empty($today_sessions)): ?>
        <span class="ssm-section-count"><?php echo count($today_sessions); ?> <?php echo ssm_t('classes_count'); ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($today_sessions)): ?>
    <div class="ssm-sessions-list">
        <?php foreach ($today_sessions as $session):
            $now = new DateTime();
            $session_start = new DateTime($session->session_date . ' ' . $session->time_start);
            $session_end = new DateTime($session->session_date . ' ' . $session->time_end);

            $is_ongoing = ($now >= $session_start && $now <= $session_end);
            $is_upcoming = ($now < $session_start);
            $is_past = ($now > $session_end);

            // Sprawdz czy wypelniona frekwencja
            $attendance_filled = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_attendance WHERE session_id = %d",
                $session->id
            ));

            $status_class = $is_ongoing ? 'ongoing' : ($is_past ? 'completed' : 'upcoming');
        ?>
        <div class="ssm-session-card ssm-session-<?php echo $status_class; ?>">
            <div class="ssm-session-time">
                <span class="ssm-time-start"><?php echo substr($session->time_start, 0, 5); ?></span>
                <span class="ssm-time-separator">-</span>
                <span class="ssm-time-end"><?php echo substr($session->time_end, 0, 5); ?></span>
            </div>

            <div class="ssm-session-info">
                <h4><?php echo esc_html($session->class_name); ?></h4>
                <div class="ssm-session-meta">
                    <span><i class="ri-map-pin-line"></i> <?php echo esc_html($session->facility_name); ?></span>
                    <span><i class="ri-group-line"></i> <?php echo $session->enrolled_count; ?> <?php echo ssm_t('enrolled'); ?></span>
                </div>
            </div>

            <div class="ssm-session-status">
                <?php if ($is_ongoing): ?>
                    <span class="ssm-badge ssm-badge-warning"><?php echo ssm_t('instr_in_progress'); ?></span>
                <?php elseif ($is_past): ?>
                    <?php if ($attendance_filled): ?>
                        <span class="ssm-badge ssm-badge-success"><?php echo ssm_t('instr_done'); ?></span>
                    <?php else: ?>
                        <span class="ssm-badge ssm-badge-muted"><?php echo ssm_t('instr_finished'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="ssm-session-action">
                <?php if ($attendance_filled): ?>
                    <a href="?instructor_page=attendance&session_id=<?php echo $session->id; ?>" class="ssm-btn ssm-btn-outline ssm-btn-sm">
                        <i class="ri-eye-line"></i>
                        <?php echo ssm_t('instr_view_list'); ?>
                    </a>
                <?php else: ?>
                    <a href="?instructor_page=attendance&session_id=<?php echo $session->id; ?>" class="ssm-btn ssm-btn-primary ssm-btn-sm">
                        <i class="ri-checkbox-line"></i>
                        <?php echo ssm_t('instr_check_attendance'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="ssm-empty-state ssm-empty-success">
        <i class="ri-checkbox-circle-line"></i>
        <p><?php echo ssm_t('instr_no_classes_today'); ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Nadchodzace zajecia -->
<?php if (!empty($upcoming_sessions)): ?>
<div class="ssm-section">
    <div class="ssm-section-header">
        <h3 class="ssm-section-title">
            <i class="ri-calendar-schedule-line"></i>
            <?php echo ssm_t('instr_upcoming_classes'); ?>
        </h3>
        <a href="?instructor_page=schedule" class="ssm-btn ssm-btn-outline ssm-btn-sm">
            <?php echo ssm_t('view_all'); ?>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>

    <div class="ssm-upcoming-table-container">
        <table class="ssm-schedule-table">
            <thead>
                <tr>
                    <th><?php echo ssm_t('date'); ?></th>
                    <th><?php echo ssm_t('time'); ?></th>
                    <th><?php echo ssm_t('course'); ?></th>
                    <th><?php echo ssm_t('location'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming_sessions as $session):
                    $date = new DateTime($session->session_date);
                ?>
                <tr>
                    <td>
                        <div class="ssm-date-cell">
                            <strong><?php echo $date->format('d.m.Y'); ?></strong>
                            <span class="ssm-text-muted"><?php echo ssm_get_day_name_pl($date); ?></span>
                        </div>
                    </td>
                    <td><?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?></td>
                    <td><strong><?php echo esc_html($session->class_name); ?></strong></td>
                    <td><?php echo esc_html($session->facility_name); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
/* ========================================
   INSTRUCTOR DASHBOARD STYLES
   ======================================== */

/* Welcome banner - instructor green gradient */
.ssm-welcome-instructor {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
}

/* Salary card - green gradient */
.ssm-stat-salary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    border: none !important;
}

.ssm-stat-salary .ssm-stat-icon {
    background: rgba(255,255,255,0.2) !important;
    color: white !important;
}

.ssm-stat-salary .ssm-stat-content h3,
.ssm-stat-salary .ssm-stat-content p {
    color: white !important;
}

.ssm-stat-salary .ssm-stat-content p {
    opacity: 0.9;
}

/* Sessions list */
.ssm-sessions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ssm-session-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius-lg);
    border-left: 4px solid var(--ssm-primary);
    transition: all 0.2s ease;
}

.ssm-session-card:hover {
    box-shadow: var(--ssm-shadow-md);
}

.ssm-session-ongoing {
    border-left-color: var(--ssm-warning);
    background: var(--ssm-warning-light);
}

.ssm-session-completed {
    border-left-color: var(--ssm-success);
}

.ssm-session-time {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 70px;
    padding: 10px;
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius);
    box-shadow: var(--ssm-shadow-sm);
}

.ssm-time-start {
    font-size: 18px;
    font-weight: 700;
    color: var(--ssm-text);
}

.ssm-time-separator {
    font-size: 12px;
    color: var(--ssm-text-light);
}

.ssm-time-end {
    font-size: 14px;
    color: var(--ssm-text-muted);
}

.ssm-session-info {
    flex: 1;
}

.ssm-session-info h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-session-meta {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-session-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ssm-session-status {
    flex-shrink: 0;
}

.ssm-session-action {
    flex-shrink: 0;
}

/* Badges */
.ssm-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ssm-badge i {
    font-size: 14px;
}

.ssm-badge-success {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-badge-warning {
    background: var(--ssm-warning-light);
    color: var(--ssm-warning);
}

.ssm-badge-muted {
    background: var(--ssm-border-light);
    color: var(--ssm-text-muted);
}

.ssm-badge-info {
    background: var(--ssm-info-light);
    color: var(--ssm-info);
}

/* Empty state success */
.ssm-empty-success {
    background: var(--ssm-success-light);
    border-radius: var(--ssm-radius-lg);
    color: var(--ssm-success);
}

.ssm-empty-success i {
    color: var(--ssm-success);
}

/* Button sizes */
.ssm-btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Section count */
.ssm-section-count {
    font-size: 13px;
    color: var(--ssm-text-muted);
    font-weight: 500;
}

/* Date cell */
.ssm-date-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ssm-date-cell strong {
    font-size: 14px;
}

.ssm-date-cell .ssm-text-muted {
    font-size: 12px;
}

/* Upcoming table container */
.ssm-upcoming-table-container {
    overflow-x: auto;
}

/* Welcome banner (shared with parent panel) */
.ssm-welcome-banner {
    position: relative;
    border-radius: var(--ssm-radius-lg);
    padding: 32px;
    margin-bottom: 24px;
    overflow: hidden;
    color: white;
}

.ssm-welcome-decoration {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.ssm-deco-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
}

.ssm-deco-1 {
    width: 200px;
    height: 200px;
    top: -100px;
    right: -50px;
}

.ssm-deco-2 {
    width: 150px;
    height: 150px;
    bottom: -75px;
    right: 100px;
}

.ssm-deco-3 {
    width: 80px;
    height: 80px;
    top: 50%;
    left: 10%;
}

.ssm-deco-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
}

.ssm-deco-wave svg {
    width: 100%;
    height: 100%;
}

.ssm-welcome-content {
    position: relative;
    z-index: 1;
}

.ssm-welcome-greeting {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 14px;
    opacity: 0.9;
}

.ssm-greeting-icon {
    font-size: 20px;
}

.ssm-welcome-content h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
    color: white;
}

.ssm-welcome-subtitle {
    margin: 0 0 16px 0;
    font-size: 15px;
    opacity: 0.9;
}

.ssm-welcome-date {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-session-card {
        flex-wrap: wrap;
    }

    .ssm-session-time {
        min-width: auto;
    }

    .ssm-session-info {
        flex: 1 1 calc(100% - 90px);
    }

    .ssm-session-status,
    .ssm-session-action {
        flex: 1 1 50%;
    }

    .ssm-session-meta {
        flex-direction: column;
        gap: 4px;
    }

    .ssm-welcome-content h1 {
        font-size: 22px;
    }
}
</style>
