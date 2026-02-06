<?php
/**
 * Panel Instruktora - Moje zajecia (Fila Style)
 */
if (!defined('ABSPATH')) exit;

// Filtry
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d', strtotime('+14 days'));
$show_past = isset($_GET['show_past']) ? 1 : 0;

if ($show_past) {
    $date_from = date('Y-m-d', strtotime('-30 days'));
}

// Pobierz wszystkie zajecia instruktora
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

// Grupuj zajecia po dniach
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
    <h1><i class="ri-calendar-todo-line"></i> <?php echo ssm_t('instr_my_classes'); ?></h1>
    <p><?php echo ssm_t('instr_schedule_description'); ?></p>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid ssm-stats-4">
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon blue">
            <i class="ri-calendar-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $total_sessions; ?></h3>
            <p><?php echo ssm_t('instr_all_classes'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon green">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $completed_attendance; ?></h3>
            <p><?php echo ssm_t('instr_attendance_checked'); ?></p>
        </div>
    </div>

    <div class="ssm-stat-card">
        <div class="ssm-stat-icon orange">
            <i class="ri-error-warning-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $total_sessions - $completed_attendance; ?></h3>
            <p><?php echo ssm_t('instr_to_check'); ?></p>
        </div>
    </div>

    <?php if ($unavailable_count > 0): ?>
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon purple">
            <i class="ri-user-unfollow-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $unavailable_count; ?></h3>
            <p><?php echo ssm_t('instr_unavailabilities'); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Filtry -->
<div class="ssm-section ssm-filters-section">
    <form method="get" class="ssm-filter-form">
        <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
        <input type="hidden" name="instructor_page" value="schedule">

        <div class="ssm-filter-row">
            <div class="ssm-filter-group">
                <label class="ssm-form-label"><?php echo ssm_t('date_from'); ?></label>
                <div class="ssm-input-icon">
                    <i class="ri-calendar-line"></i>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="ssm-form-input">
                </div>
            </div>

            <div class="ssm-filter-group">
                <label class="ssm-form-label"><?php echo ssm_t('date_to'); ?></label>
                <div class="ssm-input-icon">
                    <i class="ri-calendar-line"></i>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="ssm-form-input">
                </div>
            </div>

            <div class="ssm-filter-group ssm-filter-checkbox">
                <label class="ssm-checkbox-label">
                    <input type="checkbox" name="show_past" value="1" <?php checked($show_past, 1); ?>>
                    <span><?php echo ssm_t('instr_show_past'); ?></span>
                </label>
            </div>

            <div class="ssm-filter-actions">
                <button type="submit" class="ssm-btn ssm-btn-primary">
                    <i class="ri-search-line"></i>
                    <?php echo ssm_t('filter'); ?>
                </button>
                <a href="?instructor_page=schedule" class="ssm-btn ssm-btn-outline">
                    <i class="ri-refresh-line"></i>
                    <?php echo ssm_t('reset'); ?>
                </a>
            </div>
        </div>
    </form>

    <!-- Szybki dostep -->
    <div class="ssm-quick-filters">
        <span class="ssm-quick-label"><?php echo ssm_t('quick_access'); ?>:</span>
        <?php
        $quick_periods = array(
            array('label' => ssm_t('today'), 'from' => date('Y-m-d'), 'to' => date('Y-m-d')),
            array('label' => ssm_t('this_week'), 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+7 days'))),
            array('label' => ssm_t('two_weeks'), 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+14 days'))),
            array('label' => ssm_t('month'), 'from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+30 days'))),
        );

        foreach ($quick_periods as $period):
            $is_selected = ($date_from == $period['from'] && $date_to == $period['to']);
        ?>
            <a href="?instructor_page=schedule&date_from=<?php echo $period['from']; ?>&date_to=<?php echo $period['to']; ?>"
               class="ssm-quick-btn <?php echo $is_selected ? 'active' : ''; ?>">
                <?php echo $period['label']; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Lista zajec pogrupowana po dniach -->
<?php if (!empty($sessions_by_date)): ?>
<div class="ssm-schedule-timeline">
    <?php foreach ($sessions_by_date as $date => $day_sessions):
        $date_obj = new DateTime($date);
        $is_today = ($date == date('Y-m-d'));
        $is_past_date = ($date < date('Y-m-d'));
    ?>
    <div class="ssm-timeline-day <?php echo $is_today ? 'is-today' : ''; ?> <?php echo $is_past_date ? 'is-past' : ''; ?>">
        <div class="ssm-timeline-header">
            <div class="ssm-timeline-date">
                <span class="ssm-date-day"><?php echo $date_obj->format('d'); ?></span>
                <div class="ssm-date-info">
                    <span class="ssm-date-weekday"><?php echo ssm_get_day_name_pl($date_obj); ?></span>
                    <span class="ssm-date-month"><?php echo $date_obj->format('m.Y'); ?></span>
                </div>
            </div>
            <?php if ($is_today): ?>
                <span class="ssm-badge ssm-badge-info"><?php echo ssm_t('today'); ?></span>
            <?php endif; ?>
            <span class="ssm-timeline-count"><?php echo count($day_sessions); ?> <?php echo ssm_t('classes_count'); ?></span>
        </div>

        <div class="ssm-timeline-sessions">
            <?php foreach ($day_sessions as $session):
                $now = new DateTime();
                $session_start = new DateTime($session->session_date . ' ' . $session->time_start);
                $session_end = new DateTime($session->session_date . ' ' . $session->time_end);

                $is_ongoing = ($now >= $session_start && $now <= $session_end);
                $is_upcoming = ($now < $session_start);
                $is_past = ($now > $session_end);
                $has_unavailability = ($session->unavailability_id !== null);
            ?>
            <div class="ssm-session-card <?php echo $is_ongoing ? 'ssm-session-ongoing' : ''; ?> <?php echo $has_unavailability ? 'ssm-session-unavailable' : ''; ?>">
                <div class="ssm-session-time">
                    <span class="ssm-time-start"><?php echo substr($session->time_start, 0, 5); ?></span>
                    <span class="ssm-time-separator">-</span>
                    <span class="ssm-time-end"><?php echo substr($session->time_end, 0, 5); ?></span>
                </div>

                <div class="ssm-session-info">
                    <h4>
                        <?php echo esc_html($session->class_name); ?>
                        <?php if ($is_ongoing): ?>
                            <span class="ssm-badge ssm-badge-warning"><?php echo ssm_t('instr_in_progress'); ?></span>
                        <?php elseif ($is_past && $session->attendance_filled): ?>
                            <span class="ssm-badge ssm-badge-success"><?php echo ssm_t('instr_done'); ?></span>
                        <?php elseif ($is_past): ?>
                            <span class="ssm-badge ssm-badge-muted"><?php echo ssm_t('instr_finished'); ?></span>
                        <?php endif; ?>

                        <?php if ($has_unavailability): ?>
                            <?php if ($session->unavailability_status == 'covered'): ?>
                                <span class="ssm-badge ssm-badge-success">
                                    <i class="ri-user-shared-line"></i>
                                    <?php echo esc_html($session->replacement_name); ?>
                                </span>
                            <?php else: ?>
                                <span class="ssm-badge ssm-badge-danger">
                                    <i class="ri-user-unfollow-line"></i>
                                    <?php echo ssm_t('instr_unavailable'); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </h4>

                    <div class="ssm-session-meta">
                        <span><i class="ri-map-pin-line"></i> <?php echo esc_html($session->facility_name); ?></span>
                        <span><i class="ri-group-line"></i> <?php echo $session->enrolled_count; ?> <?php echo ssm_t('enrolled'); ?></span>
                        <?php if ($session->attendance_filled): ?>
                            <span class="ssm-meta-success"><i class="ri-checkbox-circle-fill"></i> <?php echo ssm_t('instr_attendance_done'); ?></span>
                        <?php elseif (!$has_unavailability): ?>
                            <span class="ssm-meta-warning"><i class="ri-error-warning-fill"></i> <?php echo ssm_t('instr_attendance_pending'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ssm-session-actions">
                    <?php if (!$has_unavailability && $is_upcoming): ?>
                        <button type="button" class="ssm-btn ssm-btn-outline ssm-btn-danger ssm-btn-sm ssm-report-unavailability-btn"
                                data-session-id="<?php echo $session->id; ?>"
                                data-class="<?php echo esc_attr($session->class_name); ?>"
                                data-date="<?php echo date('d.m.Y', strtotime($session->session_date)); ?>"
                                data-time="<?php echo substr($session->time_start, 0, 5); ?>">
                            <i class="ri-user-unfollow-line"></i>
                            <?php echo ssm_t('instr_report_unavailability'); ?>
                        </button>
                    <?php endif; ?>

                    <?php if (!$has_unavailability): ?>
                        <?php if ($session->attendance_filled): ?>
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
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="ssm-empty-state">
    <i class="ri-calendar-line"></i>
    <p><?php echo ssm_t('instr_no_classes_period'); ?></p>
</div>
<?php endif; ?>

<style>
/* ========================================
   INSTRUCTOR SCHEDULE STYLES
   ======================================== */

/* Stats 4 columns */
.ssm-stats-4 {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

/* Filters section */
.ssm-filters-section {
    margin-bottom: 24px;
}

.ssm-filter-form {
    margin-bottom: 20px;
}

.ssm-filter-row {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.ssm-filter-group {
    flex: 1;
    min-width: 180px;
}

.ssm-filter-checkbox {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    padding-bottom: 8px;
}

.ssm-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
    color: var(--ssm-text);
}

.ssm-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.ssm-filter-actions {
    display: flex;
    gap: 8px;
    flex: 0 0 auto;
}

/* Quick filters */
.ssm-quick-filters {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding-top: 16px;
    border-top: 1px solid var(--ssm-border);
}

.ssm-quick-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--ssm-text-muted);
}

.ssm-quick-btn {
    padding: 6px 14px;
    background: var(--ssm-bg);
    border-radius: 20px;
    color: var(--ssm-text-muted);
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.ssm-quick-btn:hover {
    background: var(--ssm-primary);
    color: white;
}

.ssm-quick-btn.active {
    background: var(--ssm-primary);
    color: white;
}

/* Schedule timeline */
.ssm-schedule-timeline {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.ssm-timeline-day {
    background: var(--ssm-bg-white);
    border: 1px solid var(--ssm-border);
    border-radius: var(--ssm-radius-lg);
    overflow: hidden;
}

.ssm-timeline-day.is-today {
    border-color: var(--ssm-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ssm-timeline-day.is-past {
    opacity: 0.8;
}

.ssm-timeline-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: var(--ssm-bg);
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-timeline-day.is-today .ssm-timeline-header {
    background: linear-gradient(135deg, var(--ssm-primary) 0%, #6366f1 100%);
    color: white;
    border-bottom: none;
}

.ssm-timeline-day.is-today .ssm-timeline-header .ssm-date-weekday,
.ssm-timeline-day.is-today .ssm-timeline-header .ssm-date-month,
.ssm-timeline-day.is-today .ssm-timeline-header .ssm-timeline-count {
    color: rgba(255,255,255,0.9);
}

.ssm-timeline-date {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-date-day {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.ssm-date-info {
    display: flex;
    flex-direction: column;
}

.ssm-date-weekday {
    font-size: 14px;
    font-weight: 600;
    text-transform: capitalize;
}

.ssm-date-month {
    font-size: 12px;
    color: var(--ssm-text-muted);
}

.ssm-timeline-count {
    margin-left: auto;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-timeline-sessions {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Session unavailable */
.ssm-session-unavailable {
    opacity: 0.6;
    border-left-color: var(--ssm-danger) !important;
}

/* Badge danger */
.ssm-badge-danger {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

/* Button danger outline */
.ssm-btn-danger {
    color: var(--ssm-danger);
    border-color: var(--ssm-danger);
}

.ssm-btn-danger:hover {
    background: var(--ssm-danger);
    color: white;
}

/* Meta colors */
.ssm-meta-success {
    color: var(--ssm-success);
}

.ssm-meta-warning {
    color: var(--ssm-warning);
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-filter-row {
        flex-direction: column;
    }

    .ssm-filter-group {
        width: 100%;
    }

    .ssm-filter-actions {
        width: 100%;
    }

    .ssm-filter-actions .ssm-btn {
        flex: 1;
    }

    .ssm-session-actions {
        flex-direction: column;
        width: 100%;
    }

    .ssm-session-actions .ssm-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

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
            '<?php echo ssm_t('instr_unavailability_prompt'); ?>\n\n' +
            '<?php echo ssm_t('course'); ?>: ' + className + '\n' +
            '<?php echo ssm_t('date'); ?>: ' + date + ' <?php echo ssm_t('at'); ?> ' + time + '\n\n' +
            '<?php echo ssm_t('instr_reason_optional'); ?>:',
            ''
        );

        if (reason === null) {
            return;
        }

        btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> <?php echo ssm_t('instr_reporting'); ?>...');

        $.post(ajaxurl, {
            action: 'ssm_report_unavailability',
            session_id: sessionId,
            reason: reason
        })
        .done(function(response) {
            if (response.success) {
                alert('<?php echo ssm_t('instr_unavailability_success'); ?>');
                location.reload();
            } else {
                alert('<?php echo ssm_t('error'); ?>: ' + response.data);
                btn.prop('disabled', false).html('<i class="ri-user-unfollow-line"></i> <?php echo ssm_t('instr_report_unavailability'); ?>');
            }
        })
        .fail(function() {
            alert('<?php echo ssm_t('error'); ?>');
            btn.prop('disabled', false).html('<i class="ri-user-unfollow-line"></i> <?php echo ssm_t('instr_report_unavailability'); ?>');
        });
    });
});
</script>
