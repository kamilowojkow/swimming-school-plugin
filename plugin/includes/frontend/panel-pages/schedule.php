<?php
// Harmonogram - szczegółowy harmonogram zajęć wszystkich dzieci
if (!defined('ABSPATH')) exit;

// Pobierz wszystkie przyszłe zajęcia
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, c.name as class_name, c.time_start, c.time_end,
            f.name as facility_name,
            CONCAT(ch.first_name, ' ', ch.last_name) as child_name,
            ch.id as child_id,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            e.id as enrollment_id
     FROM {$wpdb->prefix}ssm_sessions s
     JOIN {$wpdb->prefix}ssm_enrollments e ON s.class_id = e.class_id
     JOIN {$wpdb->prefix}ssm_classes c ON s.class_id = c.id
     JOIN {$wpdb->prefix}ssm_children ch ON e.child_id = ch.id
     JOIN {$wpdb->prefix}ssm_facilities f ON c.facility_id = f.id
     JOIN {$wpdb->prefix}ssm_instructors i ON c.instructor_id = i.id
     WHERE e.client_id = %d
     AND s.session_date >= CURDATE()
     AND s.status = 'scheduled'
     AND e.status = 'active'
     ORDER BY s.session_date ASC, s.time_start ASC",
    $client->id
));

// Sprawdź czy tabela absences istnieje
$absences_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ssm_absences'") ? true : false;

// Dla każdej sesji dodaj informacje o nieobecnościach
if ($absences_table_exists && $sessions) {
    foreach ($sessions as $session) {
        $session->is_absent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
             WHERE session_id = %d AND child_id = %d",
            $session->id, $session->child_id
        ));

        $session->used_absences = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ssm_absences
             WHERE enrollment_id = %d AND can_makeup = 1",
            $session->enrollment_id
        ));

        $class_settings = $wpdb->get_row($wpdb->prepare(
            "SELECT max_absences, allow_makeups FROM {$wpdb->prefix}ssm_classes WHERE id = (
                SELECT class_id FROM {$wpdb->prefix}ssm_sessions WHERE id = %d
            )",
            $session->id
        ));

        $session->max_absences = $class_settings ? ($class_settings->max_absences ?? 2) : 2;
        $session->allow_makeups = $class_settings ? ($class_settings->allow_makeups ?? 1) : 1;
    }
} else {
    foreach ($sessions as $session) {
        $session->is_absent = 0;
        $session->used_absences = 0;
        $session->max_absences = 2;
        $session->allow_makeups = 1;
    }
}

// Grupuj sesje według daty
$sessions_by_date = array();
foreach ($sessions as $session) {
    $date_key = $session->session_date;
    if (!isset($sessions_by_date[$date_key])) {
        $sessions_by_date[$date_key] = array();
    }
    $sessions_by_date[$date_key][] = $session;
}

// Statystyki
$total_sessions = count($sessions);
$today_sessions = 0;
$this_week_sessions = 0;
$week_end = date('Y-m-d', strtotime('sunday this week'));

foreach ($sessions as $session) {
    if ($session->session_date == date('Y-m-d')) {
        $today_sessions++;
    }
    if ($session->session_date <= $week_end) {
        $this_week_sessions++;
    }
}
?>

<div class="ssm-page-header">
    <h1><i class="ri-calendar-todo-line"></i> <?php echo ssm_t('schedule'); ?></h1>
    <p><?php echo ssm_t('schedule_description'); ?></p>
</div>

<!-- Statystyki -->
<div class="ssm-stats-grid ssm-stats-small">
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon blue">
            <i class="ri-calendar-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $total_sessions; ?></h3>
            <p><?php echo ssm_t('all_sessions'); ?></p>
        </div>
    </div>
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon orange">
            <i class="ri-sun-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $today_sessions; ?></h3>
            <p><?php echo ssm_t('today'); ?></p>
        </div>
    </div>
    <div class="ssm-stat-card">
        <div class="ssm-stat-icon green">
            <i class="ri-calendar-check-line"></i>
        </div>
        <div class="ssm-stat-content">
            <h3><?php echo $this_week_sessions; ?></h3>
            <p><?php echo ssm_t('this_week'); ?></p>
        </div>
    </div>
</div>

<?php if ($sessions_by_date): ?>

    <div class="ssm-schedule-timeline">
        <?php foreach ($sessions_by_date as $date_key => $day_sessions):
            $date = new DateTime($date_key);
            $is_today = ($date_key == date('Y-m-d'));
            $is_tomorrow = ($date_key == date('Y-m-d', strtotime('+1 day')));
        ?>

        <div class="ssm-timeline-day <?php echo $is_today ? 'is-today' : ''; ?>">
            <!-- Data header -->
            <div class="ssm-timeline-date">
                <div class="ssm-date-box <?php echo $is_today ? 'today' : ''; ?>">
                    <span class="ssm-date-day"><?php echo $date->format('d'); ?></span>
                    <span class="ssm-date-month"><?php echo ssm_get_month_name_pl($date); ?></span>
                </div>
                <div class="ssm-date-info">
                    <span class="ssm-date-weekday"><?php echo $days_pl[$date->format('N')]; ?></span>
                    <?php if ($is_today): ?>
                        <span class="ssm-badge-today"><?php echo ssm_t('today'); ?></span>
                    <?php elseif ($is_tomorrow): ?>
                        <span class="ssm-badge-tomorrow"><?php echo ssm_t('tomorrow'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zajęcia tego dnia -->
            <div class="ssm-timeline-sessions">
                <?php foreach ($day_sessions as $session):
                    $session_datetime = new DateTime($session->session_date . ' ' . $session->time_start);
                    $now = new DateTime();
                    $hours_until = ($session_datetime->getTimestamp() - $now->getTimestamp()) / 3600;
                    $can_report = ($hours_until >= 24);
                    $is_absent = ($session->is_absent > 0);
                    $absences_left = max(0, $session->max_absences - $session->used_absences);
                ?>

                <div class="ssm-session-card <?php echo $is_absent ? 'is-absent' : ''; ?>">
                    <div class="ssm-session-time">
                        <i class="ri-time-line"></i>
                        <?php echo substr($session->time_start, 0, 5); ?> - <?php echo substr($session->time_end, 0, 5); ?>
                    </div>

                    <div class="ssm-session-main">
                        <div class="ssm-session-header">
                            <h4 class="ssm-session-title"><?php echo esc_html($session->class_name); ?></h4>
                            <?php if ($is_absent): ?>
                                <span class="ssm-badge ssm-badge-danger">
                                    <i class="ri-close-circle-line"></i> <?php echo ssm_t('absent'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="ssm-session-details">
                            <span class="ssm-session-child">
                                <i class="ri-user-line"></i> <?php echo esc_html($session->child_name); ?>
                            </span>
                            <span class="ssm-session-facility">
                                <i class="ri-map-pin-line"></i> <?php echo esc_html($session->facility_name); ?>
                            </span>
                            <span class="ssm-session-instructor">
                                <i class="ri-user-star-line"></i> <?php echo esc_html($session->instructor_name); ?>
                            </span>
                        </div>
                    </div>

                    <div class="ssm-session-action">
                        <?php if ($is_absent): ?>
                            <span class="ssm-action-reported">
                                <i class="ri-check-line"></i> <?php echo ssm_t('reported'); ?>
                            </span>
                        <?php elseif ($session->allow_makeups && $can_report && $absences_left > 0): ?>
                            <button class="ssm-btn ssm-btn-absence"
                                    data-session-id="<?php echo $session->id; ?>"
                                    data-enrollment-id="<?php echo $session->enrollment_id; ?>"
                                    data-child-name="<?php echo esc_attr($session->child_name); ?>"
                                    data-class-name="<?php echo esc_attr($session->class_name); ?>"
                                    data-date="<?php echo $date->format('d.m.Y'); ?>">
                                <i class="ri-calendar-close-line"></i>
                                <?php echo ssm_t('report_absence'); ?>
                            </button>
                            <span class="ssm-absences-left">
                                <?php echo sprintf(ssm_t('absences_left'), $absences_left); ?>
                            </span>
                        <?php elseif ($absences_left == 0): ?>
                            <span class="ssm-action-limit">
                                <i class="ri-forbid-line"></i> <?php echo ssm_t('limit_reached'); ?>
                            </span>
                        <?php elseif (!$can_report): ?>
                            <span class="ssm-action-late">
                                <i class="ri-time-line"></i> <?php echo ssm_t('too_late'); ?>
                            </span>
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
        <p><?php echo ssm_t('no_sessions'); ?></p>
        <small><?php echo ssm_t('no_sessions_hint'); ?></small>
    </div>
<?php endif; ?>

<!-- Modal zgłaszania nieobecności -->
<div class="ssm-modal" id="absence-modal">
    <div class="ssm-modal-backdrop"></div>
    <div class="ssm-modal-content">
        <div class="ssm-modal-header">
            <h3><i class="ri-calendar-close-line"></i> <?php echo ssm_t('report_absence'); ?></h3>
            <button type="button" class="ssm-modal-close" id="modal-close">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="ssm-modal-body">
            <div class="ssm-absence-info" id="absence-info"></div>

            <div class="ssm-form-group">
                <label for="absence-reason"><?php echo ssm_t('absence_reason'); ?></label>
                <textarea id="absence-reason" rows="3" class="ssm-textarea"
                          placeholder="<?php echo ssm_t('absence_reason_placeholder'); ?>"></textarea>
            </div>

            <div class="ssm-notice ssm-notice-warning">
                <i class="ri-information-line"></i>
                <span><?php echo ssm_t('absence_notice'); ?></span>
            </div>
        </div>
        <div class="ssm-modal-footer">
            <button type="button" class="ssm-btn ssm-btn-outline" id="cancel-absence">
                <?php echo ssm_t('cancel'); ?>
            </button>
            <button type="button" class="ssm-btn ssm-btn-danger" id="confirm-absence">
                <i class="ri-calendar-close-line"></i>
                <?php echo ssm_t('confirm_absence'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* ========================================
   SCHEDULE TIMELINE - FILA STYLE
   ======================================== */

.ssm-stats-small {
    grid-template-columns: repeat(3, 1fr);
    margin-bottom: 24px;
}

.ssm-stats-small .ssm-stat-card {
    padding: 16px 20px;
}

.ssm-stats-small .ssm-stat-icon {
    width: 44px;
    height: 44px;
    font-size: 20px;
}

.ssm-stats-small .ssm-stat-content h3 {
    font-size: 24px;
}

.ssm-stats-small .ssm-stat-content p {
    font-size: 12px;
}

/* Timeline */
.ssm-schedule-timeline {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.ssm-timeline-day {
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    border: 1px solid var(--ssm-border);
    overflow: hidden;
}

.ssm-timeline-day.is-today {
    border-color: var(--ssm-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Date header */
.ssm-timeline-date {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: var(--ssm-bg);
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-date-box {
    width: 56px;
    height: 56px;
    background: var(--ssm-bg-white);
    border: 2px solid var(--ssm-border);
    border-radius: var(--ssm-radius);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ssm-date-box.today {
    background: var(--ssm-primary);
    border-color: var(--ssm-primary);
    color: white;
}

.ssm-date-day {
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}

.ssm-date-month {
    font-size: 11px;
    text-transform: uppercase;
    opacity: 0.8;
}

.ssm-date-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ssm-date-weekday {
    font-size: 16px;
    font-weight: 600;
    color: var(--ssm-text);
}

.ssm-badge-today {
    padding: 4px 12px;
    background: var(--ssm-primary);
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
}

.ssm-badge-tomorrow {
    padding: 4px 12px;
    background: var(--ssm-warning);
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 20px;
    text-transform: uppercase;
}

/* Sessions list */
.ssm-timeline-sessions {
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ssm-session-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--ssm-bg);
    border-radius: var(--ssm-radius);
    transition: all 0.2s ease;
}

.ssm-session-card:hover {
    background: rgba(59, 130, 246, 0.05);
}

.ssm-session-card.is-absent {
    opacity: 0.6;
    background: var(--ssm-danger-light);
}

.ssm-session-time {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius);
    font-size: 13px;
    font-weight: 600;
    color: var(--ssm-text);
    white-space: nowrap;
    flex-shrink: 0;
}

.ssm-session-time i {
    color: var(--ssm-primary);
}

.ssm-session-main {
    flex: 1;
    min-width: 0;
}

.ssm-session-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 6px;
}

.ssm-session-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--ssm-text);
    margin: 0;
}

.ssm-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.ssm-badge-danger {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

.ssm-session-details {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 13px;
    color: var(--ssm-text-muted);
}

.ssm-session-details span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.ssm-session-child {
    color: var(--ssm-primary);
    font-weight: 500;
}

/* Action buttons */
.ssm-session-action {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}

.ssm-btn-absence {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: transparent;
    border: 1px solid var(--ssm-danger);
    color: var(--ssm-danger);
    font-size: 13px;
    font-weight: 500;
    border-radius: var(--ssm-radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.ssm-btn-absence:hover {
    background: var(--ssm-danger);
    color: white;
}

.ssm-absences-left {
    font-size: 11px;
    color: var(--ssm-text-light);
}

.ssm-action-reported,
.ssm-action-limit,
.ssm-action-late {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    padding: 6px 12px;
    border-radius: var(--ssm-radius);
}

.ssm-action-reported {
    background: var(--ssm-success-light);
    color: var(--ssm-success);
}

.ssm-action-limit {
    background: var(--ssm-danger-light);
    color: var(--ssm-danger);
}

.ssm-action-late {
    background: var(--ssm-border-light);
    color: var(--ssm-text-light);
}

/* Modal */
.ssm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.ssm-modal.active {
    display: flex;
}

.ssm-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
}

.ssm-modal-content {
    position: relative;
    background: var(--ssm-bg-white);
    border-radius: var(--ssm-radius-lg);
    box-shadow: var(--ssm-shadow-lg);
    width: 90%;
    max-width: 480px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ssm-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--ssm-border);
}

.ssm-modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--ssm-text);
}

.ssm-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--ssm-text-muted);
    cursor: pointer;
    padding: 4px;
    line-height: 1;
}

.ssm-modal-close:hover {
    color: var(--ssm-danger);
}

.ssm-modal-body {
    padding: 24px;
    overflow-y: auto;
}

.ssm-absence-info {
    background: var(--ssm-bg);
    padding: 16px;
    border-radius: var(--ssm-radius);
    margin-bottom: 20px;
}

.ssm-absence-info p {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: var(--ssm-text);
}

.ssm-absence-info p:last-child {
    margin-bottom: 0;
}

.ssm-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--ssm-border);
    background: var(--ssm-bg);
}

.ssm-btn-danger {
    background: var(--ssm-danger);
    color: white;
}

.ssm-btn-danger:hover {
    background: #dc2626;
}

/* Responsive */
@media (max-width: 768px) {
    .ssm-stats-small {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .ssm-stats-small .ssm-stat-card {
        padding: 12px;
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }

    .ssm-session-card {
        flex-direction: column;
        align-items: flex-start;
    }

    .ssm-session-time {
        align-self: flex-start;
    }

    .ssm-session-action {
        align-self: flex-start;
        align-items: flex-start;
        margin-top: 8px;
    }

    .ssm-session-details {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    let currentSessionId = 0;
    let currentEnrollmentId = 0;

    // Otwórz modal
    $('.ssm-btn-absence').on('click', function() {
        currentSessionId = $(this).data('session-id');
        currentEnrollmentId = $(this).data('enrollment-id');

        $('#absence-info').html(`
            <p><strong><?php echo ssm_t('child'); ?>:</strong> ${$(this).data('child-name')}</p>
            <p><strong><?php echo ssm_t('course'); ?>:</strong> ${$(this).data('class-name')}</p>
            <p><strong><?php echo ssm_t('date'); ?>:</strong> ${$(this).data('date')}</p>
        `);

        $('#absence-reason').val('');
        $('#absence-modal').addClass('active');
    });

    // Zamknij modal
    $('#modal-close, #cancel-absence, .ssm-modal-backdrop').on('click', function() {
        $('#absence-modal').removeClass('active');
    });

    // Potwierdź nieobecność
    $('#confirm-absence').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> <?php echo ssm_t('loading'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ssm_report_absence',
                session_id: currentSessionId,
                enrollment_id: currentEnrollmentId,
                reason: $('#absence-reason').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                    btn.prop('disabled', false).html('<i class="ri-calendar-close-line"></i> <?php echo ssm_t('confirm_absence'); ?>');
                }
            },
            error: function() {
                alert('<?php echo ssm_t('error'); ?>');
                btn.prop('disabled', false).html('<i class="ri-calendar-close-line"></i> <?php echo ssm_t('confirm_absence'); ?>');
            }
        });
    });
});
</script>
